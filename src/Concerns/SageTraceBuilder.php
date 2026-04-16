<?php

/** @internal */
class SageTraceBuilder
{
    public static function build($rawTrace, $getSourceSnippet = false, $getObject = false, $getArguments = false)
    {
        $self = new self();

        return $self->doBuildTrace($rawTrace, $getSourceSnippet, $getObject, $getArguments);
    }

    private function doBuildTrace($rawTrace, $getSourceSnippet, $getObject, $getArguments)
    {
        $trace    = new SageTrace();
        $lastStep = array();
        foreach ($rawTrace as $step) {
            if ($step['function'] === 'spl_autoload_call') { // meaningless
                continue;
            }

            if (SageHelper::isStepInternal($step)) {
                // take first step from the top that is not inside Sage already
                if (isset($step['file'], $step['line'])) {
                    $lastStep = $step;
                }

                continue;
            }

            $trace->steps[] = $this->parseStep($step, $getSourceSnippet, $getObject, $getArguments);
        }

        if ($lastStep) {
            array_unshift($trace->steps, $this->parseStep($lastStep, $getSourceSnippet, $getObject, $getArguments));
        }

        return $trace;
    }

    private function parseStep($rawTraceStep, $getSourceSnippet, $getObject, $getArguments)
    {
        $step = new SageTraceStep();

        $step->fileLine      = $this->getFileAndLine($rawTraceStep);
        $step->argumentNames = $this->getStepArgumentNames($rawTraceStep);
        $step->functionName  = $this->getStepFunctionName($rawTraceStep, $step->argumentNames);

        if ($this->isStepBlacklisted($rawTraceStep)) {
            $step->isBlackListed = true;

            return $step;
        }

        if ($getObject) {
            // todo it's possible to parse the object name out from the source!!!
            $step->object = $this->getObject($rawTraceStep);
        }
        if ($getSourceSnippet) {
            $step->sourceSnippet = $this->getSourceSnippet($rawTraceStep);
        }
        if ($getArguments) {
            $step->arguments = $this->getArguments($rawTraceStep, $step->argumentNames);
        }

        return $step;
    }

    private function isStepBlacklisted($step)
    {
        if (! Sage::settings()->maxLevels) {
            return false;
        }

        if (! isset($step['file'])) {
            return false;
        }

        foreach (Sage::settings()->traceBlacklist as $blacklistedPath) {
            if (preg_match($blacklistedPath, $step['file'])) {
                return true;
            }
        }

        return false;
    }

    private function getFileAndLine($rawTraceStep)
    {
        if (! isset($rawTraceStep['file'])) {
            return 'PHP internal call';
        }

        return SageHelper::getIdeLink($rawTraceStep['file'], $rawTraceStep['line']);
    }

    private function getStepArgumentNames($rawTraceStep)
    {
        if (empty($rawTraceStep['args']) || empty($rawTraceStep['function'])) {
            return array();
        }

        $function = $rawTraceStep['function'];
        if (in_array($function, array('include', 'include_once', 'require', 'require_once'))) {
            return array('<file>');
        }

        $reflection = null;

        if (isset($rawTraceStep['class'])) {
            if (method_exists($rawTraceStep['class'], $function)) {
                $reflection = new ReflectionMethod($rawTraceStep['class'], $function);
            }
        } elseif (function_exists($function)) {
            $reflection = new ReflectionFunction($function);
        }

        $params = $reflection ? $reflection->getParameters() : null;

        $names = array();
        if ($params) {
            foreach ($params as $param) {
                $name = '$' . $param->name;
                if (method_exists($param, 'isVariadic') && $param->isVariadic()) {
                    $name = '...' . $name;
                }
                $names[] = $name;
            }
        }

        return $names;
    }

    private function getStepFunctionName($rawTraceStep, $functionNames)
    {
        if (empty($rawTraceStep['function'])) {
            return '';
        }

        $function = $rawTraceStep['function'];
        if ($function && isset($rawTraceStep['class'])) {
            $function = $rawTraceStep['class'] . $rawTraceStep['type'] . $function;
        }

        return $function . '(' . implode(', ', $functionNames) . ')';
    }

    private function getObject($rawTraceStep)
    {
        if (! isset($rawTraceStep['object'])) {
            return null;
        }

        return SageParser::parse($rawTraceStep['object']);
    }

    private function getSourceSnippet($rawTraceStep)
    {
        if (
            empty($rawTraceStep['file'])
            || ! isset($rawTraceStep['line'])
            || Sage::enabled() !== Sage::MODE_RICH
            || ! is_readable($rawTraceStep['file'])
        ) {
            return null;
        }

        // open the file and set the line position
        $file        = fopen($rawTraceStep['file'], 'r');
        $line        = $rawTraceStep['line'];
        $readingLine = 0;

        // Set the reading range
        $range = array(
            'start' => $line - 7,
            'end'   => $line + 7,
        );

        // set the zero-padding amount for line numbers
        $format = '% ' . strlen($range['end']) . 'd';

        $source = '';
        while (($row = fgets($file)) !== false) {
            // increment the line number
            if (++$readingLine > $range['end']) {
                break;
            }

            if ($readingLine >= $range['start']) {
                $row = SageHelper::esc($row);

                $row = '<span>' . sprintf($format, $readingLine) . '</span> ' . $row;

                if ($readingLine === (int) $line) {
                    // apply highlighting to this row
                    $row = '<div class="_sage-highlight">' . $row . '</div>';
                } else {
                    $row = '<div>' . $row . '</div>';
                }

                $source .= $row;
            }
        }

        fclose($file);

        return $source;
    }

    private function getArguments($rawTraceStep, $argumentNames)
    {
        $result        = array();
        $i             = 0;
        $variadicIndex = 0;
        foreach ($this->getSanitizedArgs($rawTraceStep) as $argument) {
            $name = isset($argumentNames[$i]) ? $argumentNames[$i] : '';
            // variadic parameters are always last
            if (strpos($name, '...') === 0) {
                $name .= '[' . $variadicIndex++ . ']';
            } else {
                $i++;
            }

            if (SageHelper::isKeyBlacklisted($name)) {
                $parsed = SageParsedVariable::erroneous(SageHelper::trans('key_blacklisted'));
            } else {
                $parsed           = SageParser::parse($argument, $name);
                $parsed->operator = substr($name, 0, 1) === '$' ? '=' : ':';
            }

            $result[] = $parsed;
        }

        return $result;
    }

    private function getSanitizedArgs($rawTraceStep)
    {
        if (
            ! empty($rawTraceStep['args'])
            && empty($rawTraceStep['class'])
            && in_array($rawTraceStep['function'], array('include', 'include_once', 'require', 'require_once'), true)
        ) {
            // sanitize the included file path
            return array(SageHelper::shortenPath($rawTraceStep['args'][0]));
        }

        return isset($rawTraceStep['args']) ? $rawTraceStep['args'] : array();
    }
}
