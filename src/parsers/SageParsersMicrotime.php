<?php

/**
 * @internal
 */
class SageParsersMicrotime implements SageParserInterface
{
    private static $times = array();
    private static $laps = array();

    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable, $varData)
    {
        if (! is_string($variable)
            || ! preg_match('/^0\.[\d]{8} [\d]{10}$/', $variable)) {
            return false;
        }

        list($usec, $sec) = explode(' ', $variable);

        $time = (float)$usec + (float)$sec;

        $size = memory_get_usage(true);

        $unit        = array('B', 'KB', 'MB', 'GB', 'TB');
        $memoryUsage = round($size / pow(1024, ($i = floor(log($size, 1024)))), 3) . $unit[$i];

        $numberOfCalls = count(self::$times);
        if ($numberOfCalls > 0) {
            $lap          = $time - end(self::$times);
            self::$laps[] = $lap;

            $sinceLast = round($lap, 4) . 's.';
            if ($numberOfCalls > 1) {
                $sinceStart      = round($time - self::$times[0], 4) . 's.';
                $averageDuration = round(array_sum(self::$laps) / $numberOfCalls, 4) . 's.';
            } else {
                $sinceStart      = null;
                $averageDuration = null;
            }

            if (SageHelper::isRichMode()) {
                $tabContents = "<b>SINCE LAST SUCH CALL:</b> <b class=\"_sage-microtime\">" . round($lap, 4) . '</b>s.';
                if ($numberOfCalls > 1) {
                    $tabContents .= "\n<b>SINCE START:</b> {$sinceStart}";
                    $tabContents .= "\n<b>AVERAGE DURATION:</b> {$averageDuration}";
                }
                $tabContents .= "\n<b>PHP MEMORY USAGE:</b> {$memoryUsage}";

                $varData->addTabToView($variable, 'Benchmark', $tabContents);
            } else {
                $varData->extendedValue = array(
                    'Since last such call' => $sinceLast
                );

                if ($sinceStart !== null) {
                    $varData->extendedValue['Since start']      = $sinceStart;
                    $varData->extendedValue['Average duration'] = $averageDuration;
                }

                $varData->extendedValue['Memory usage'] = $memoryUsage;
            }
        } else {
            $varData->extendedValue = array(
                'Time (from microtime)' => @date('Y-m-d H:i:s', (int)$sec) . substr($usec, 1),
                'PHP MEMORY USAGE'      => $memoryUsage
            );
        }

        self::$times[] = $time;
    }
}
