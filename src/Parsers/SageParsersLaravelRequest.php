<?php

/**
 * {@see SageSettings::enabledParsers} to enable/disable.
 *
 * @internal
 */
class SageParsersLaravelRequest implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (! is_a($variable, '\Illuminate\Http\Request')) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = 'Illuminate\Http\Request';
        $result->size = count($variable->all());
        $result->hash = SageHelper::getObjectHash($variable);

        $result->addTabView__UnwrappedDump($variable->all());

        return $result;
    }
}
