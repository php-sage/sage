<?php

/**
 * @internal
 */
interface SageCustomParserInterface
{
    public function replacesAllOtherParsers();

    /**
     * @param                  $variable
     * @param SageVariableData $varData receives the full variable representation to alter it as needed
     *
     * @return false|void return false if the parser doesn't handle the current variable
     */
    public function parse(&$variable, $varData);
}
