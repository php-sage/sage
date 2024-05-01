<?php

/**
 * @internal
 */
interface SageDecoratorsInterface
{
    public function decorate(SageVariableData $varData);

    /** @param SageTraceStep[] $traceData */
    public function decorateTrace(array $traceData, $pathsOnly = false);

    /**
     * called for each dump, opens the html tag
     *
     * @return string
     */
    public function wrapStart();

    /**
     * Closes wrapStart() and displays callee information
     *
     * @param SageCallee $calleeInfo caller information taken from debug backtrace
     *
     * @return string
     */
    public function wrapEnd($calleeInfo);

    public function init();
}
