<?php

/**
 * Alter {@see Sage::$enabledParsers} to enable/disable.
 *
 * @internal
 */
class SageParsersJson implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (! SageHelper::isRichMode()
            || ! SageHelper::php53orLater()
            || ! is_string($variable)
            || ! isset($variable[0])
            || ($variable[0] !== '{' && $variable[0] !== '[')
        ) {
            return null;
        }

        $json = json_decode($variable, true);

        if ($json === null) {
            return null;
        }

        if (! is_array($json)) {
            return null;
        }

        $result = new SageParsedVariable();
        $result->addTabView__UnwrappedDump($json, 'Json');

        return $result;
    }
}
