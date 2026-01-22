<?php

/**
 * {@see SageSettings::enabledParsers} to enable/disable.
 *
 * @internal
 */
class SageParsersDateTime implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (! $variable instanceof DateTimeInterface) {
            return null;
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

        $result        = new SageParsedVariable();
        $result->value = $variable->format($format);
        $result->type  = get_class($variable);

        return $result;
    }
}
