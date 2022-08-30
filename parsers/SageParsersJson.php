<?php

/**
 * @internal
 */
class SageParsersJson extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (! SageHelper::isRichMode()
            || ! SageHelper::php53orLater()
            || ! is_string($variable)
            || ! isset($variable[0])
            || ($variable[0] !== '{' && $variable[0] !== '[')
            || ($json = json_decode($variable, true)) === null
        ) {
            return false;
        }

        $val = (array)$json;

        if (empty($val)) {
            return false;
        }

        $varData->addTabToView($variable, 'Json', $val);
    }
}
