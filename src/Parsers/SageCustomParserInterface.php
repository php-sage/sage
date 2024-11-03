<?php

/**
 * @internal
 */
interface SageCustomParserInterface
{
    /**
     * @return bool if self::parse() produces any output, stop looking for alternatives and only display this.
     */
    public function replacesAllOtherParsers();

    /**
     * @param mixed &$variable literal copy of the variable sent to dump
     *
     * @return ?SageParsedVariable
     */
    public function parse(&$variable);
}
