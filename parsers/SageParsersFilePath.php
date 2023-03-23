<?php

/**
 * @internal
 */
require_once __DIR__ . '/SageParsersSplFileInfo.php';

class SageParsersFilePath extends SageParsersSplFileInfo
{
    public static $replacesAllOtherParsers = false;

    protected static function parse(&$variable, $varData)
    {
        if (! SageHelper::php53orLater()
            || ! is_string($variable)
            || ($strlen = strlen($variable)) > 2048
            || $strlen < 3
            || ! preg_match('#[\\\\/]#', $variable)
            || preg_match('/[?<>"*|]/', $variable)
            || ! @is_readable($variable) // PHP and its random warnings
        ) {
            return false;
        }

        return self::run($variable, $varData, new SplFileInfo($variable));
    }
}
