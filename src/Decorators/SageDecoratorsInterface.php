<?php

/**
 * @internal
 */
interface SageDecoratorsInterface
{
    /**
     * @return string
     */
    public function decorate(SageParsedVariable $varData);

    /**
     * called for each dump, opens the html tag
     *
     * @return string
     */
    public function wrapStart();

    /**
     * Closes wrapStart() and displays callee information
     *
     * @param SageInvoker $caller caller information taken from debug backtrace
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
