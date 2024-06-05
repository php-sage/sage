<?php

/**
 * @internal
 */
class SageParsersMicrotime implements SageCustomParserInterface
{
    private static $times = array();
    private static $laps = array();

    public function replacesAllOtherParsers()
    {
        return false;
    }

    /** @return false|void */
    public function parse(&$variable, $varData)
    {
        if (! is_string($variable)
            || ! preg_match('/^0\.[\d]{8} [\d]{10}$/', $variable)) {
            return false;
        }

        list($usec, $sec) = explode(' ', $variable);
        $time          = (float)$usec + (float)$sec;
        $size          = memory_get_usage(true);
        $numberOfCalls = count(self::$times);
        $result        = new SageVariableExtendedView(SageVariableExtendedView::CONTENT_TYPE_PLAIN_TEXT_ROWS, 'Benchmark');

        if ($numberOfCalls > 0) {
            $lap          = $time - end(self::$times);
            self::$laps[] = $lap;

            $sinceLast = round($lap, 4) . 's.';
            if (SageHelper::isRichMode()) {
                $sinceLast = new SageHtmlable('<b class="_sage-microtime">' . $sinceLast . '</b>');
            }
            $result->addRow($sinceLast . 's.', 'Since last such call');

            if ($numberOfCalls > 1) {
                $result->addRow(round($time - self::$times[0], 4) . 's.', 'Since start');
                $result->addRow(round(array_sum(self::$laps) / $numberOfCalls, 4) . 's.', 'Average duration');
            }
        } else {
            $result->addRow(@date('Y-m-d H:i:s', (int)$sec) . substr($usec, 1), 'Time (from microtime)');
        }

        $unit = array('B', 'KB', 'MB', 'GB', 'TB');
        $result->addRow(round($size / pow(1024, ($i = floor(log($size, 1024)))), 3) . $unit[$i], 'PHP memory usage');

        $varData->addAlternativeView($result);

        self::$times[] = $time;
    }
}
