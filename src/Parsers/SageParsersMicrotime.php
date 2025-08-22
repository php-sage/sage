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
            || ! preg_match('/^0\.[\d]{8} [\d]{10}$/', $variable)
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

            $sinceLast = round($lap, 4) . 's.';
            if (SageHelper::isRichMode()) {
                $sinceLast = new SageHtmlable('<b class="_sage-microtime">' . $sinceLast . '</b>');
            }
            $output->addRow($sinceLast . 's.', 'Since last such call');

            if ($numberOfCalls > 1) {
                $output->addRow(round($time - self::$times[0], 4) . 's.', 'Since start');
                $output->addRow(round(array_sum(self::$laps) / $numberOfCalls, 4) . 's.', 'Average duration');
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
}
