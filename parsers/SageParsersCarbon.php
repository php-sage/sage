<?php


/**
 * @internal
 */
class SageParsersCarbon extends SageParser
{
    public static $replacesAllOtherParsers = true;

    protected static function parse(&$variable, $varData)
    {
        $class = 'Carbon\CarbonInterface'; // pre-namespace php support
        if (! $variable instanceof $class) {
            return false;
        }

        $varData->value = $variable->toDateTimeString();
        $varData->type  = get_class($variable);
    }
}
