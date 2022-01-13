<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersTimestamp extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (! self::_fits($variable)) {
            return false;
        }

        $var = strlen($variable) === 13 ? substr($variable, 0, -3) : $variable;

        // avoid dreaded "Timezone must be set" error
        $varData->addTabToView('Timestamp', @date('Y-m-d H:i:s', $var));
    }


    private static function _fits($variable)
    {
        if (! SageHelper::isRichMode()) {
            return false;
        }

        if (! is_string($variable) && ! is_int($variable)) {
            return false;
        }

        $len = strlen((int)$variable);

        return
            (
                $len === 9 || $len === 10 // a little naive
                || ($len === 13 && substr($variable, -3) === '000') // also handles javascript micro timestamps
            )
            && ((string)(int)$variable == $variable);
    }
}