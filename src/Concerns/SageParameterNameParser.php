<?php

/**
 * @internal
 */
class SageParameterNameParser
{
    public static function fetch($step, $sageMethodCalled)
    {
        $file = new SplFileObject($step['file']);
        $file->seek($step['line'] - 1);

        $contents = '';
        while (! $file->eof()) {
            $contents .= trim($file->current());
            $file->next();
        }
        $pureCode = self::removeAllButCode($contents);
        $pureCode = self::removeBefore($pureCode, $sageMethodCalled);

        $i             = 0;
        $c             = strlen($pureCode);
        $bracketsLevel = 0;
        $parameters    = array();
        $stringSoFar   = '';
        while ($i < $c) {
            $char = $pureCode[$i];

            if ($bracketsLevel === 0) {
                $stringSoFar .= $char;
            }

            if ($char === '(') {
                $bracketsLevel++;
            } elseif ($char === ')') {
                if ($bracketsLevel === 0) {
                    break;
                }
                $bracketsLevel--;

                if ($bracketsLevel === 0) {
                    $stringSoFar .= '…)';
                }
            } elseif ($char === ',') {
                if ($bracketsLevel === 0) {
                    $parameters[] = substr($stringSoFar, 0, -1); // zap ","
                    $stringSoFar  = '';
                }
            }

            $i++;
        }
        $parameters[] = substr($stringSoFar, 0, -1); // last ")"

        return $parameters;
    }

    private static function removeBefore($code, $sageMethodCalled)
    {
        // Get everything before the first instance of $sageMethodCalled
        $prefix = strstr($code, $sageMethodCalled, true);

        if ($prefix !== false) {
            // Replace the prefix found with your new string
            $code = str_replace($prefix . $sageMethodCalled . '(', '', $code);
        }

        return $code;
    }

    /**
     * @param string $source
     *
     * @return string
     */
    private static function removeAllButCode($source)
    {
        if (substr($source, 0, 2) !== '<?') {
            $source = '<?php ' . $source;
        }

        $commentTokens = array(
            T_COMMENT            => true,
            T_INLINE_HTML        => true,
            T_DOC_COMMENT        => true,
            T_OPEN_TAG           => true,
            T_WHITESPACE         => true,
            T_CLOSE_TAG          => true,
            T_OPEN_TAG_WITH_ECHO => true,
        );
        $stringTokens  = array(
            T_CONSTANT_ENCAPSED_STRING => true,
            T_ENCAPSED_AND_WHITESPACE  => true,
        );

        $cleanedSource = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (isset($commentTokens[$token[0]])) {
                    $token = '';
                } elseif (isset($stringTokens[$token[0]])) {
                    $token = '"…"';
                } elseif ($token[0] === T_LNUMBER) {
                    $token = '…';
                } else {
                    $token = $token[1];
                }
            } elseif ($token === ';') {
                $token = '';
            }

            $cleanedSource .= $token;
        }

        return $cleanedSource;
    }
}
