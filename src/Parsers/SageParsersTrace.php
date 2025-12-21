<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersTrace implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        // if we're explicitly passed a trace (i.e. something like Sage::dumpTrace() was invoked)...
        if ($variable instanceof SageTrace) {
            $result        = new SageParsedVariable();
            $result->trace = $variable;

            return $result;
        }

        // ...otherwise just check if the provided array is a trace
        if (! is_array($variable)) {
            return null;
        }

        if (! SageHelper::isValidTrace($variable)) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = 'Trace';
        $result->size = count($variable);
        if (SageHelper::isRichMode()) {
            $result->addTabView__Trace(SageTraceBuilder::minimalWithRaw($variable), 'Trace view');
        } else {
            $result->trace = SageTraceBuilder::minimal($variable);
        }

        return $result;
    }
}
