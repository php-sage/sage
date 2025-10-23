<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersMicrotime implements SageCustomParserInterface
{
    private static $times = array();
    private static $laps = array();

    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (
            ! is_string($variable)
            || ! preg_match('/^0\.\d{8} \d{10}$/', $variable)
        ) {
            return null;
        }

        list($usec, $sec) = explode(' ', $variable);
        $time          = (float) $usec + (float) $sec;
        $size          = memory_get_usage(true);
        $numberOfCalls = count(self::$times);
        $output        = new SageParsedVariableContents(
            SageParsedVariableContents::PLAIN_TEXT_ROWS,
            'Benchmark'
        );

        if ($numberOfCalls > 0) {
            $lap          = $time - end(self::$times);
            self::$laps[] = $lap;

            $sinceLast = $this->formatSeconds($lap);
            if (SageHelper::isRichMode()) {
                $sinceLast = new SageHtmlable('<b class="_sage-microtime">' . $sinceLast . '</b>');
            }
            $output->addRow($sinceLast, 'Since last such call');

            if ($numberOfCalls > 1) {
                $output->addRow($this->formatSeconds($time - self::$times[0]), 'Since start');
                $output->addRow($this->formatSeconds(array_sum(self::$laps) / $numberOfCalls), 'Average duration');
            }
        } else {
            $output->addRow(@date('Y-m-d H:i:s', (int) $sec) . substr($usec, 1), 'Time (from microtime)');
        }

        self::$times[] = $time;

        $unit = array('B', 'KB', 'MB', 'GB', 'TB');
        $output->addRow(round($size / pow(1024, ($i = floor(log($size, 1024)))), 3) . $unit[$i], 'PHP memory usage');
        $result = new SageParsedVariable();
        $result->addTabView($output);

        return $result;
    }

    private function formatSeconds($seconds)
    {
        $hours   = floor($seconds / 3600);
        $minutes = floor(((int) $seconds % 3600) / 60);
        $secs    = fmod($seconds, 60);

        $parts = array();
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }
        if ($secs > 0 || ! $parts) {
            $parts[] = round($secs, 4) . 's';
        }

        return implode(' ', $parts);
    }

}
