<?php

/** @internal */
class SageParsersFilePath implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (
            ! SageHelper::php53orLater()
            || ! is_string($variable)
            || ($strlen = strlen($variable)) > 512
            || $strlen < 12
            || ! preg_match('#[\\\\/]#', $variable)
            || preg_match('/[?<>"*|]/', $variable)
            || ! @is_readable($variable) // PHP and its random warnings
        ) {
            return null;
        }

        return SageParsersSplFileInfo::inspect(new SplFileInfo($variable), new SageParsedVariable());
    }
}
