<?php

/** @internal  */
class SageTraceBuilder
{

    /**
     * Displayed with code snippet, steps are ignored according to blacklist.
     *
     * @param array $rawTrace full debug backtrace array
     *
     * @return SageTrace
     */
    public static function full($rawTrace)
    {
        $self = new self();

        return $self->buildTrace($rawTrace, 'full');
    }

    /**
     * Only files and lines, no blacklist.
     *
     * @param array $rawTrace
     *
     * @return SageTrace
     */
    public static function minimal($rawTrace)
    {
        $self = new self();

        return $self->buildTrace($rawTrace, 'minimal');
    }

    /**
     * Like minimal, but each line expands to reveal raw step data.
     *
     * Used when a trace is dumped as non-first-class-citizen.
     *
     * Can't use full mode in that context because
     * 1. It uses blacklist
     * 2. It really really lags if not using blacklist - cause unknown :(
     *
     * @param array $rawTrace
     *
     * @return SageTrace
     */
    public static function minimalWithRaw($rawTrace)
    {
        $self = new self();

        return $self->buildTrace($rawTrace, 'minimalWithRaw');
    }

    /**
     * @param array $rawTrace
     * @param 'full'|'minimal'|'minimalWithRaw' $mode
     *
     * @return SageTrace
     */
    private function buildTrace($rawTrace, $mode)
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

            $trace->steps[] = $this->parseStep($step, $mode);
        }

        if ($lastStep) {
            array_unshift($trace->steps, $this->parseStep($lastStep, $mode));
        }

        return $trace;
    }

    /**
     * @param array $step
     * @param 'full'|'minimal'|'minimalWithRaw' $mode
     *
     * @return SageTraceStep|void
     */
    private function parseStep($step, $mode)
    {
        switch ($mode) {
            case 'full':
                return $this->step__full($step);
            case 'minimal':
                return $this->step__minimal($step);
            case 'minimalWithRaw':
                return $this->step__minimalWithRaw($step);
        }
    }

    /**
     * @param array $rawTraceStep - individual step from internal debug backtrace array
     *
     * @return SageTraceStep
     */
    private function step__full($rawTraceStep)
    {
        $step = new SageTraceStep();

        $step->fileLine      = $this->getFileAndLine($rawTraceStep);
        $step->argumentNames = $this->getStepArgumentNames($rawTraceStep);
        $step->functionName  = $this->getStepFunctionName($rawTraceStep, $step->argumentNames);

        if ($this->isStepBlacklisted($rawTraceStep)) {
            $step->isBlackListed = true;

            return $step;
        }

        // todo it's possible to parse the object name out from the source!!!
        $step->object        = $this->getObject($rawTraceStep);
        $step->sourceSnippet = $this->getSourceSnippet($rawTraceStep);
        $step->arguments     = $this->getArguments($rawTraceStep, $step->argumentNames);

        return $step;
    }

    private function step__minimal($rawTraceStep)
    {
        $self                = new SageTraceStep();
        $self->fileLine      = $this->getFileAndLine($rawTraceStep);
        $self->argumentNames = $this->getStepArgumentNames($rawTraceStep);
        $self->functionName  = $this->getStepFunctionName($rawTraceStep, $self->argumentNames);

        return $self;
    }

    private function step__minimalWithRaw($rawTraceStep)
    {
        $self           = new SageTraceStep();
        $self->fileLine = $this->getFileAndLine($rawTraceStep);
        $self->rawStep  = SageParser::parse($rawTraceStep);

        return $self;
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
