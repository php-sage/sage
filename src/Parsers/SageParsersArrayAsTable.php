<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
// todo
class SageParsersArrayAsTable implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable, $varData)
    {
        // todo
    }
}
