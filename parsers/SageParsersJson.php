<?php

/**
 * @internal
 */
class SageParsersJson extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (! SageHelper::isRichMode()
            || ! SageHelper::php53()
            || ! is_string($variable)
            || ! isset($variable[0]) || ($variable[0] !== '{' && $variable[0] !== '[')
            || ($json = json_decode($variable, true)) === null
        ) {
            return false;
        }

        $val = (array)$json;

        if (empty($val)) {
            return false;
        }
        $val = SageParser::process($val)->extendedValue;

        $varData->addTabToView('Json', $val);
    }
}