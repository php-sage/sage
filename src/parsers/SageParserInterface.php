<?php

/**
 * @internal
 */
interface SageParserInterface
{
    public function replacesAllOtherParsers();

    public function parse(&$variable, $varData);
}
