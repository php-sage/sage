<?php

/**
 * @internal
 */
class SageParsersDateTime extends SageParser
{
    public static $replacesAllOtherParsers = true;

    protected static function parse(&$variable, $varData)
    {
        if (! $variable instanceof DateTimeInterface) {
            return false;
        }

        $format = 'Y-m-d H:i:s';

        $ms = $variable->format('u');
        if (rtrim($ms, '0')) {
            $format .= '.' . $ms;
        } else {
            $format .= '.0';
        }

        if ($variable->getTimezone()->getLocation()) {
            $format .= ' e';
        }
        $format .= ' (P)';

        $varData->value = $variable->format($format);
        $varData->type  = get_class($variable);
    }
}
