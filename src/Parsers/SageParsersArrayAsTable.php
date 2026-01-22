<?php

/**
 * @internal
 * {@see SageSettings::enabledParsers} to enable/disable
 */
// todo
class SageParsersArrayAsTable implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        // todo
    }
}
