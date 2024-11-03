<?php

/**
 * @internal
 */
interface SageDecoratorsInterface
{
    public function decorate(SageParsedVariable $varData);

    /** @param SageParsedTraceStep[] $traceData */
    public function decorateTrace(SageParsedVariable $trace, $pathsOnly = false);

    /**
     * called for each dump, opens the html tag
     *
     * @return string
     */
    public function wrapStart();

    /**
     * Closes wrapStart() and displays callee information
     *
     * @param SageCallerData $caller caller information taken from debug backtrace
     *
     * @return string
     */
    public function wrapEnd($caller);

    public function init();

    public function areAssetsNeeded();

    /**
     * @param bool $on
     */
    public function setAssetsNeeded($on);
}
