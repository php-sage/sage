<?php

/**
 * @internal
 */
interface SageCustomParserInterface
{
    /**
     * @return bool if self::parse() produces any output, stop looking for alternatives and only display this.
     *
     * [!] If this returns true - type, size and all other properties of {@see SageParsedVariable} must be set in
     * {@see self::parse()}, or will be left empty.
     */
    public function replacesAllOtherParsers();

    /**
     * @param mixed &$variable literal copy of the variable sent to dump
     *
     * @return ?SageParsedVariable create a new instance to return - any properties you write will override generic
     * values, for example the computed object size.
     */
    public function parse(&$variable);
}
