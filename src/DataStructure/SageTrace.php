<?php

/**
 * @internal
 */
class SageTrace
{
    /** @var SageParsedTraceStep[] */
    public $steps = array();
    /** @var bool */
    public $full = false;

    /**
     * Displayed with code snippet, steps are ignored according to blacklist.
     *
     * @param array $trace
     *
     * @return self
     */
    public static function full($trace)
    {
        $self       = new self();
        $self->full = true;

        return $self->buildTrace($trace, 'full');
    }

    /**
     * Only files and lines, no blacklist.
     *
     * @param array $trace
     *
     * @return self
     */
    public static function minimal($trace)
    {
        $self = new self();

        return $self->buildTrace($trace, 'minimal');
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
     * @param array $trace
     *
     * @return self
     */
    public static function minimalWithRaw($trace)
    {
        $self = new self();

        return $self->buildTrace($trace, 'minimalWithRaw');
    }

    /**
     * @param array $trace
     * @param string $mode
     *
     * @return $this
     */
    private function buildTrace($trace, $mode)
    {
        $lastStep = array();
        foreach ($trace as $step) {
            if ($step['function'] === 'spl_autoload_call') { // meaningless
                continue;
            }

            if (SageHelper::stepIsInternal($step)) {
                // take first step from the top that is not inside Sage already
                if (isset($step['file'], $step['line'])) {
                    $lastStep = $step;
                }

                continue;
            }

            $this->steps[] = $this->parseStep($step, $mode);
        }

        if ($lastStep) {
            array_unshift($this->steps, $this->parseStep($lastStep, $mode));
        }

        return $this;
    }

    /**
     * @param array $step
     * @param 'full'|'minimal'|'minimalWithRaw' $mode
     *
     * @return SageParsedTraceStep|void
     */
    private function parseStep($step, $mode)
    {
        switch ($mode) {
            case 'full':
                return SageParsedTraceStep::full($step);
            case 'minimal':
                return SageParsedTraceStep::minimal($step);
            case 'minimalWithRaw':
                return SageParsedTraceStep::minimalWithRaw($step);
        }
    }
}
