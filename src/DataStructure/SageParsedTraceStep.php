<?php

/**
 * @internal
 */
class SageParsedTraceStep
{
    public $functionName = null;
    public $isBlackListed = false;
    public $fileLine = null;
    public $sourceSnippet = null;
    public $arguments = array();
    public $argumentNames = array();
    /** @var SageParsedVariable|null */
    public $object = null;
    public $rawStep = array();
}
