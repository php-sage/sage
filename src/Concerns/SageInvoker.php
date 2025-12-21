<?php

/**
 * @internal
 */
class SageInvoker
{
    /**
     * @var array $parameterNames parameter names/expressions which were passed to be dumped
     */
    public $parameterNames = array();
    /**
     * @var array $miniTrace full trace up to sage without arguments and objects
     */
    public $miniTrace = array();
    public $sageMethodCalled = '';

    /**
     * Fetches the public properties defined above.
     *
     * @param array $rawTrace
     *
     * @return self
     */
    public static function from($rawTrace)
    {
        $self                   = new self();
        $insideTemplateDetected = null;

        // go from back of trace forward to find first occurrence of call to Sage or its wrappers
        while ($step = array_pop($rawTrace)) {
            if (
                isset($step['args'][0])
                && is_string($step['args'][0])
                && substr($step['args'][0], -strlen('.blade.php')) === '.blade.php'
            ) {
                $insideTemplateDetected = $step['args'][0];
            }

            if (isset($step['file'], $step['line'])) {
                unset($step['object'], $step['args']);
                array_unshift($self->miniTrace, $step);
            }

            if (SageHelper::stepIsInternal($step)) {
                $self->sageMethodCalled = strtolower($step['function']);
                break;
            }
        }

        if (! isset($step['file']) || ! is_readable($step['file'])) {
            return $self;
        }

        SageHelper::detectProjectRoot($self->getUserLandInvoker('file'));

        if (SageHelper::php82orLater()) {
            $self->solveForPhp82();
        } else {
            $self->solveForEarlierVersions();
        }

        if ($insideTemplateDetected) {
            $self->miniTrace[1]['file'] = $insideTemplateDetected;
            $self->miniTrace[1]['line'] = null;
        }

        return $self;
    }

    /**
     * Gets the trace step where Sage was invoked.
     *
     * @param 'all'|'file'|'line'|'function'|'args'|'object' $whichElement fetch specific element not the whole step
     *
     * @return null|array|string|int trace step where sage was called from
     */
    public function getUserLandInvoker($whichElement = 'all')
    {
        $step = count($this->miniTrace) > 1 ? $this->miniTrace[1] : array();
        if ($whichElement === 'all') {
            return $step;
        }

        if (array_key_exists($whichElement, $step)) {
            return $step[$whichElement];
        }

        return null;
    }

    /**
     * @param int $parameterIndex
     *
     * @return null|string
     */
    public function getParameterName($parameterIndex)
    {
        // when the dump arguments take long to generate output, user might have changed the file and
        // Sage might not parse the arguments correctly, so check if names are set and while the
        // displayed names might be wrong, at least don't throw an error
        $name = array_key_exists($parameterIndex, $this->parameterNames)
            ? $this->parameterNames[$parameterIndex]
            : '???';

        if (strlen($name) > 60) {
            $name =
                SageHelper::substr($name, 0, 27)
                . '...'
                . SageHelper::substr($name, -28, null);
        }

        return $name;
    }

    private function solveForPhp82()
    {
        $this->solveForEarlierVersions();

        return;
        // open the file and read it up to the position where the function call expression ended
        // TODO since PHP 8.2 backtrace reports the lineno of the function/method name!
        // https://github.com/php/php-src/pull/8818

        $userLandInvoker = $this->miniTrace[0];

        $file = new SplFileObject($userLandInvoker['file']);
        $line = $userLandInvoker['line'];
        do {
            $file->seek($line);
            $contents = $file->current(); // $contents would hold the data from line x

        } while (! $file->eof());

        $this->solveForEarlierVersions();
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
        $i              = 0;
        $c              = strlen($paramsString);
        $betweenQuotes  = $escaped = $openedBracket = $closingBracket = false;
        $inBrackets     = 0;
        $openedBrackets = array();
        $bracketPairs   = array('(' => ')', '[' => ']', '{' => '}');

        while ($i < $c) {
            $letter = $paramsString[$i];

            if (! $betweenQuotes) {
                if ($letter === "'" || $letter === '"') {
                    $betweenQuotes = $letter;
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
            } elseif ($letter === $betweenQuotes && ! $escaped) {
                $betweenQuotes = false;
            }

            // replace whatever was inside quotes or brackets with untypeable characters, we don't need that info.
            if ($inBrackets > 0) {
                if ($inBrackets > 1 || $letter !== $openedBracket) {
                    $paramsString[$i] = "\x07";
                }
            }
            if ($betweenQuotes) {
                if ($letter !== $betweenQuotes || $escaped) {
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
