<?php

/**
 * @internal
 */
class SageCaller
{
    /**
     * @var array $parameterNames parameter names/expressions which were passed to be dumped
     */
    public $parameterNames = array();
    /**
     * @var array $miniTrace full trace up to sage without arguments and objects
     */
    public $miniTrace = array();

    public function __construct(
        $names = array(),
        $miniTrace = array()
    ) {
        $this->parameterNames = $names;
        $this->miniTrace      = $miniTrace;
    }

    public function getUserLandInvoker($key = null)
    {
        $step = count($this->miniTrace) > 1 ? $this->miniTrace[1] : array();
        if ($key === null) {
            return $step;
        }

        if (array_key_exists($key, $step)) {
            return $step[$key];
        }

        return null;
    }

    /**
     * returns parameter names that the function was passed, as well as any predefined symbols before function
     * call (modifiers)
     *
     * @return self
     */
    public static function getCalleeInfo($trace)
    {
        $result                 = new self();
        $insideTemplateDetected = null;

        // go from back of trace forward to find first occurrence of call to Sage or its wrappers
        while ($step = array_pop($trace)) {
            if (
                isset($step['args'][0])
                && is_string($step['args'][0])
                && substr($step['args'][0], -strlen('.blade.php')) === '.blade.php'
            ) {
                $insideTemplateDetected = $step['args'][0];
            }

            if (isset($step['file'], $step['line'])) {
                unset($step['object'], $step['args']);
                array_unshift($result->miniTrace, $step);
            }

            if (SageHelper::stepIsInternal($step)) {
                break;
            }
        }

        if (! isset($step['file']) || ! is_readable($step['file'])) {
            return $result;
        }

        SageHelper::detectProjectRoot($result->getUserLandInvoker('file'));

        // open the file and read it up to the position where the function call expression ended
        // TODO since PHP 8.2 backtrace reports the lineno of the function/method name!
        // https://github.com/php/php-src/pull/8818
        //        $file = new SplFileObject($callee['file']);
        //        do {
        //            $file->seek($callee['line']);
        //            $contents = $file->current(); // $contents would hold the data from line x
        //
        //        } while (! $file->eof());

        if (SageHelper::php82orLater()) {
            $result->solveForPhp82();
        } else {
            $result->solveForEarlierVersions();
        }

        if ($insideTemplateDetected) {
            $result->miniTrace[1]['file'] = $insideTemplateDetected;
            $result->miniTrace[1]['line'] = null;
        }

        return $result;
    }

    private function solveForPhp82()
    {
        return $this->solveForEarlierVersions();
    }

    private function solveForEarlierVersions()
    {
        $userLandInvoker = $this->miniTrace[0];

        $file   = fopen($userLandInvoker['file'], 'r');
        $line   = 0;
        $source = '';
        while (($row = fgets($file)) !== false) {
            if (++$line > $userLandInvoker['line']) {
                break;
            }
            $source .= $row;
        }
        fclose($file);
        $source = self::_removeAllButCode($source);

        if (empty($userLandInvoker['class'])) {
            $codePattern = $userLandInvoker['function'];
        } else {
            $codePattern = "\w+\x07*" . $userLandInvoker['type'] . "\x07*" . $userLandInvoker['function'];
        }

        // get the position of the last call to the function
        preg_match_all(
            "
            /
            # beginning of statement
            [\x07{(]

            # spaces
            \x07*

            # possibly a namespace symbol
            \\\\?

            # spaces again
            \x07*

            # main call to Sage (group 1)
            ({$codePattern})

            # spaces everywhere
            \x07*

            # find the character where Sage's opening bracket resides (group 2)
            (\\()

            /ix",
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $callToSage = end($matches[1]);
        $bracket    = end($matches[2]);

        if (empty($callToSage)) {
            // if a wrapper is misconfigured, don't display the whole file as variable name
            return;
        }

        $paramsString = preg_replace("[\x07+]", ' ', substr($source, $bracket[1] + 1));
        // we now have a string like this:
        // <parameters passed>); <the rest of the last read line>

        // remove everything in brackets and quotes, we don't need nested statements nor literal strings which would
        // complicate separating individual arguments
        $c              = strlen($paramsString);
        $inString       = $escaped = $openedBracket = $closingBracket = false;
        $i              = 0;
        $inBrackets     = 0;
        $openedBrackets = array();
        $bracketPairs   = array('(' => ')', '[' => ']', '{' => '}');

        while ($i < $c) {
            $letter = $paramsString[$i];

            if (! $inString) {
                if ($letter === '\'' || $letter === '"') {
                    $inString = $letter;
                } elseif ($letter === '(' || $letter === '[' || $letter === '{') {
                    $inBrackets++;
                    $openedBrackets[] = $openedBracket = $letter;
                    $closingBracket   = $bracketPairs[$letter];
                } elseif ($inBrackets && $letter === $closingBracket) {
                    $inBrackets--;
                    array_pop($openedBrackets);
                    $openedBracket = end($openedBrackets);
                    if ($openedBracket) {
                        $closingBracket = $bracketPairs[$openedBracket];
                    }
                } elseif (! $inBrackets && $letter === ')') {
                    $paramsString = substr($paramsString, 0, $i);
                    break;
                }
            } elseif ($letter === $inString && ! $escaped) {
                $inString = false;
            }

            // replace whatever was inside quotes or brackets with untypeable characters, we don't
            // need that info.
            if ($inBrackets > 0) {
                if ($inBrackets > 1 || $letter !== $openedBracket) {
                    $paramsString[$i] = "\x07";
                }
            }
            if ($inString) {
                if ($letter !== $inString || $escaped) {
                    $paramsString[$i] = "\x07";
                }
            }

            $escaped = ! $escaped && ($letter === '\\');
            $i++;
        }

        $this->parameterNames = explode(',', preg_replace("[\x07+]", '...', $paramsString));
        $this->parameterNames = array_map('trim', $this->parameterNames);
    }

    /**
     * removes comments and zaps whitespace & < ?php tags from php code, makes for easier further parsing
     *
     * @param string $source
     *
     * @return string
     */
    private static function _removeAllButCode($source)
    {
        $commentTokens    = array(
            T_COMMENT     => true,
            T_INLINE_HTML => true,
            T_DOC_COMMENT => true,
        );
        $whiteSpaceTokens = array(
            T_WHITESPACE         => true,
            T_CLOSE_TAG          => true,
            T_OPEN_TAG           => true,
            T_OPEN_TAG_WITH_ECHO => true,
        );

        $cleanedSource = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (isset($commentTokens[$token[0]])) {
                    continue;
                }

                if (isset($whiteSpaceTokens[$token[0]])) {
                    $token = "\x07";
                } else {
                    $token = $token[1];
                }
            } elseif ($token === ';') {
                $token = "\x07";
            }

            $cleanedSource .= $token;
        }

        return $cleanedSource;
    }
}
