<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersSplFileInfo extends SageParsersFilePath
{
    public static $replacesAllOtherParsers = true;

    protected static function parse(&$variable, $varData)
    {
        if (! SageHelper::php53orLater()
            || ! $variable instanceof SplFileInfo
            || $variable instanceof SplFileObject
        ) {
            return false;
        }

        return SageParsersFilePath::run($variable, $varData, $variable);
    }
}
