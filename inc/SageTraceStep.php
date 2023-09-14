<?php

/**
 * @internal
 */
class SageTraceStep
{
    public $functionName = null;
    public $isBlackListed = false;
    public $fileLine = null;
    public $sourceSnippet = null;
    public $arguments = array();
    public $argumentNames = array();
    /** @var SageVariableData|null */
    public $object = null;

    public function __construct($step, $stepNumber)
    {
        $this->fileLine      = $this->getFileAndLine($step);
        $this->argumentNames = $this->getStepArgumentNames($step);
        $this->functionName  = $this->getStepFunctionName($step, $this->argumentNames);

        if ($this->isStepBlacklisted($step, $stepNumber)) {
            $this->isBlackListed = true;

            return;
        }

        // todo it's possible to parse the object name out from the source!!!
        $this->object        = $this->getObject($step);
        $this->sourceSnippet = $this->getSourceSnippet($step);
        $this->arguments     = $this->getArguments($step, $this->argumentNames);
    }

    private function isStepBlacklisted($step, $stepNumber)
    {
        if (! Sage::$maxLevels) {
            return false;
        }

        if (! isset($step['file'])) {
            return false;
        }

        if ($stepNumber < Sage::$minimumTraceStepsToShowFull) {
            return false;
        }

        foreach (Sage::$traceBlacklist as $blacklistedPath) {
            if (preg_match($blacklistedPath, $step['file'])) {
                return true;
            }
        }

        return false;
    }

    private function getFileAndLine($step)
    {
        if (! isset($step['file'])) {
            return 'PHP internal call';
        }

        return SageHelper::ideLink($step['file'], $step['line']);
    }

    private function getStepArgumentNames($step)
    {
        if (empty($step['args']) || empty($step['function'])) {
            return array();
        }

        $function = $step['function'];
        if (in_array($function, array('include', 'include_once', 'require', 'require_once'))) {
            return array('<file>');
        }

        $reflection = null;

        if (isset($step['class'])) {
            if (method_exists($step['class'], $function)) {
                $reflection = new ReflectionMethod($step['class'], $function);
            }
        } elseif (function_exists($function)) {
            $reflection = new ReflectionFunction($function);
        }

        $params = $reflection ? $reflection->getParameters() : null;

        $names = array();
        foreach ($step['args'] as $i => $arg) {
            if (isset($params[$i])) {
                $names[] = '$' . $params[$i]->name;
            } else {
                $names[] = '#' . ($i + 1);
            }
        }

        return $names;
    }

    private function getStepFunctionName($step, $functionNames)
    {
        if (empty($step['function'])) {
            return '';
        }

        $function = $step['function'];
        if ($function && isset($step['class'])) {
            $function = $step['class'] . $step['type'] . $function;
        }

        return $function . '(' . implode(', ', $functionNames) . ')';
    }

    private function getObject($step)
    {
        if (! isset($step['object'])) {
            return null;
        }

        return SageParser::process($step['object']);
    }

    private function getSourceSnippet($step)
    {
        if (
            empty($step['file'])
            || ! isset($step['line'])
            || Sage::enabled() !== Sage::MODE_RICH
            || ! is_readable($step['file'])
        ) {
            return null;
        }

        // open the file and set the line position
        $file        = fopen($step['file'], 'r');
        $line        = $step['line'];
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

                if ($readingLine === (int)$line) {
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

    private function getArguments($step, $argumentNames)
    {
        $result = array();
        foreach ($this->getRawArguments($step) as $k => $variable) {
            $name             = isset($argumentNames[$k]) ? $argumentNames[$k] : '';
            $parsed           = SageParser::process($variable, $argumentNames[$k]);
            $parsed->operator = substr($name, 0, 1) === '$' ? '=' : ':';
            $result[]         = $parsed;
        }

        return $result;
    }

    private function getRawArguments($step)
    {
        if (
            ! empty($step['args'])
            && in_array($step['function'], array('include', 'include_once', 'require', 'require_once'), true)
        ) {
            // sanitize the included file path
            return array(SageHelper::shortenPath($step['args'][0]));
        }

        return isset($step['args']) ? $step['args'] : array();
    }
}
