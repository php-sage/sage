<?php

/**
 * @internal
 * {@see SageInstance::enabledParsers} to enable/disable
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
