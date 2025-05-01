<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersTrace implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
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

        $trace       = array();
        $traceFields = array('file', 'line', 'args', 'class');
        $fileFound   = false; // file element must exist in one of the steps
        $lastStep    = array();

        // validate whether a trace was indeed passed
        foreach ($variable as $step) {
            if (! is_array($step) || ! isset($step['function'])) {
                return null;
            }
            if (! $fileFound && isset($step['file']) && file_exists($step['file'])) {
                $fileFound = true;
            }

            $valid = false;
            foreach ($traceFields as $element) {
                if (isset($step[$element])) {
                    $valid = true;
                    break;
                }
            }
            if (! $valid) {
                return null;
            }

            if ($step['function'] === 'spl_autoload_call') { // meaningless
                continue;
            }

            if (SageHelper::stepIsInternal($step)) {
                // take first step from the top that is not inside Sage already
                if (isset($step['file'], $step['line'])) {
                    $lastStep = array(
                        'file'     => $step['file'],
                        'line'     => $step['line'],
                        'function' => '',
                    );
                }

                continue;
            }

            $trace[] = SageParsedTraceStep::full($step);
        }

        if (! $fileFound) {
            return null;
        }

        if ($lastStep) {
            array_unshift($trace, SageParsedTraceStep::full($lastStep));
        }

        $result        = new SageParsedVariable();
        $result->trace = $trace;

        return $result;
    }
}
