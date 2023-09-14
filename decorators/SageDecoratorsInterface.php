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

    public function wrapEnd($callee, $miniTrace, $prevCaller);

    public function init();
}
