<?php

/**
 * @internal
 *
 * todo: sage()->displaySimplest(123);
 */
class SageParameterNameParser
{
    public static function fetch($step, $sageMethodCalled)
    {
        $file = new SplFileObject($step['file']);
        $file->seek($step['line'] - 1);

        $code = self::getInvokerSource($file);
        $code = self::strRemoveBeforeSage($code, $sageMethodCalled);

        return self::parseSourceToParams($code);
    }

    /**
     * Cleans the line where sage was invoked by replacing everything up to and including the sage invoke statement with
     * `sage`
     */
    private static function strRemoveBeforeSage($code, $sageMethodCalled)
    {
        $position = strpos($code, $sageMethodCalled);
        if ($position !== false) {
            $code = substr_replace($code, 'sage', 0, $position + strlen($sageMethodCalled));
        }

        return $code;
    }

    private static function parseSourceToParams($source)
    {
        if (substr($source, 0, 2) !== '<?') {
            $source = '<?php ' . $source;
        }

        $uselessTokens = array(
            T_COMMENT            => true,
            T_INLINE_HTML        => true,
            T_DOC_COMMENT        => true,
            T_OPEN_TAG           => true,
            T_CLOSE_TAG          => true,
            T_OPEN_TAG_WITH_ECHO => true,
        );

        $tokensInCurrentParameter = array();
        $tokensInEachParameter    = array();
        $bracketsLevel            = 0;
        $curlyBracketsLevel       = 0;

        $tokens = token_get_all($source);
        unset( // we added these so PHP parser works, remove them now
            $tokens[0], // <?php
            $tokens[1] // sage
        );

        foreach ($tokens as $token) {
            if ($curlyBracketsLevel && $token !== '}') { // ignore everything inside {}
                continue;
            }

            if (! is_array($token)) {
                if ($token === '{') {
                    $curlyBracketsLevel++;
                    continue;
                }
                if ($token === '}') {
                    $curlyBracketsLevel--;
                    if ($curlyBracketsLevel === 0) {
                        $tokensInCurrentParameter[] = '...';
                    }
                    continue;
                }

                if ($token === '(') {
                    $bracketsLevel++;
                    if ($bracketsLevel === 1) { // main bracket
                        continue;
                    }
                }

                if ($token === ')') {
                    if ($bracketsLevel === 1) { // main bracket
                        $tokensInEachParameter[] = $tokensInCurrentParameter;
                        break;
                    }

                    $bracketsLevel--;
                }

                if ($token === ',') {
                    if ($bracketsLevel === 1) { // top level
                        $tokensInEachParameter[]  = $tokensInCurrentParameter;
                        $tokensInCurrentParameter = array();
                        continue;
                    }
                }

                $tokensInCurrentParameter[] = $token;
                continue;
            }

            if (isset($uselessTokens[$token[0]])) {
                continue;
            }

            if ($token[0] === T_WHITESPACE) {
                $token = ' ';
            }

            $tokensInCurrentParameter[] = $token;
        }

        $parameters = array();
        foreach ($tokensInEachParameter as $tokens) {
            $param = '';
            foreach ($tokens as $token) {
                $param .= trim(str_replace("\n", ' ', is_array($token) ? $token[1] : $token));
            }
            $parameters[] = $param;
        }

        return $parameters;
    }

    /**
     * @return string e.g. `sage($param, $param, 'etc'); // and the entire remainder of this file`
     */
    private static function getInvokerSource(SplFileObject $file)
    {
        $contents = '';
        while (! $file->eof()) {
            $contents .= $file->current();
            $file->next();
        }

        return $contents;
    }
}
