<?php

/**
 * @internal
 */
interface SageCustomParserInterface
{
    public function replacesAllOtherParsers();

    /**
     * Process and
     *
     * @param                  $variable
     * @param SageVariableData $varData
     *
     * @return false|void return false if the parser doesn't handle the current variable
     */
    public function parse(&$variable, $varData);
}
