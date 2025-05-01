<?php

/**
 * @internal
 */
class SageTrace
{
    public $steps = array();

    public static function full($trace)
    {
        $self = new self();

        return $self->buildTrace($trace, true);
    }

    /**
     * Only files and lines, ignores blacklist.
     *
     * @return self
     */
    public static function minimal($trace)
    {
        $self = new self();

        return $self->buildTrace($trace, false);
    }

    private function buildTrace($trace, $full)
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

            $this->steps[] = $full
                ? SageParsedTraceStep::full($step)
                : SageParsedTraceStep::minimal($step);
        }

        if ($lastStep) {
            $lastStep = $full
                ? SageParsedTraceStep::full($lastStep)
                : SageParsedTraceStep::minimal($lastStep);

            array_unshift($this->steps, $lastStep);
        }

        return $this;
    }
}
