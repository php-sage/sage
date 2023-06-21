<?php
/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */

class SageDecoratorsPlain
{
    public static $firstRun = true;
    private static $_enableColors;

    public static function decorate(SageVariableData $varData, $level = 0)
    {
        $output = '';
        if ($level === 0) {
            $name          = $varData->name ? $varData->name : '';
            $varData->name = null;

            $output .= self::_title($name);
        }

        $space  = str_repeat($s = '    ', $level);
        $output .= $space . self::_drawHeader($varData);

        if (isset($varData->extendedValue)) {
            $output .= ' ' . ($varData->type === 'array' ? '[' : '(') . PHP_EOL;

            if (is_array($varData->extendedValue)) {
                foreach ($varData->extendedValue as $k => $v) {
                    if (is_string($v)) {
                        $output .= $space . $s
                            . self::_colorize($k, 'key', false) . ': '
                            . self::_colorize($v, 'value');
                    } else {
                        $output .= self::decorate($v, $level + 1);
                    }
                }
            } elseif (is_string($varData->extendedValue)) {
                $output .= $space . $s . self::_colorize($varData->extendedValue, 'value');
            } else {
                // throw new RuntimeException();
                // $output .= self::decorate($varData->extendedValue, $level + 1); // it's SageVariableData
            }
            $output .= $space . ($varData->type === 'array' ? ']' : ')') . PHP_EOL;
        } else {
            $output .= PHP_EOL;
        }

        return $output;
    }

    public static function decorateTrace($traceData, $pathsOnly = false)
    {
        // if we're dealing with a framework stack, lets verbosely display last few steps only, and not hang the browser
        $optimizeOutput = count($traceData) >= 10 && Sage::$maxLevels !== false;
        $maxLevels      = Sage::$maxLevels;

        $output   = self::_title($pathsOnly ? 'QUICK TRACE' : 'TRACE');
        $lastStep = count($traceData);
        foreach ($traceData as $stepNo => $step) {
            if ($optimizeOutput) {
                if ($stepNo > 2) {
                    Sage::$maxLevels = 3;
                }
            }

            $output .= str_pad(++$stepNo . ': ', 4, ' ');

            $output .= self::_colorize(
                (
                isset($step['file'])
                    ? SageHelper::ideLink($step['file'], $step['line'])
                    : 'PHP internal call'
                ),
                'header'
            );

            $appendDollar = $step['function'] === '{closure}' ? '' : '$';

            if (! empty($step['function'])) {
                $output .= '    ' . $step['function'];
                if (isset($step['args'])) {
                    $output .= '(';
                    if (empty($step['args'])) {
                        $output .= ')';
                    } else {
                        $output .= $appendDollar . implode(', ' . $appendDollar, array_keys($step['args'])) . ')';
                    }
                }
                $output .= PHP_EOL;
            }

            if (! $pathsOnly && ! empty($step['args'])) {
                $output .= self::_colorize(
                    '    ┌' . str_repeat('─', 26) . ' Arguments ' . str_repeat('─', 37) . '┐',
                    'header'
                );

                $i = 0;
                foreach ($step['args'] as $name => $argument) {
                    $argument           = SageParser::process(
                        $argument,
                        $name ? $appendDollar . $name : '#' . ++$i
                    );
                    $argument->operator = $name ? ' =' : ':';
                    $maxLevels          = Sage::$maxLevels;
                    if ($maxLevels) {
                        Sage::$maxLevels = $maxLevels + 2;
                    }
                    $output .= self::decorate($argument, 2);
                    if ($maxLevels) {
                        Sage::$maxLevels = $maxLevels;
                    }
                }

                $output .= '    ' . self::_colorize('└' . str_repeat('─', 74) . '┘', 'header');
            }

            if (! $pathsOnly && ! empty($step['object'])) {
                $output .= self::_colorize(
                    '    ┌' . str_repeat('─', 26) . ' Callee object ' . str_repeat('─', 33) . '┐',
                    'header'
                );

                $maxLevels = Sage::$maxLevels;
                if ($maxLevels) {
                    // in cli the terminal window is filled too quickly to display huge objects
                    Sage::$maxLevels = Sage::enabled() === Sage::MODE_CLI
                        ? 2
                        : $maxLevels + 1;
                }
                $output .= self::decorate(SageParser::process($step['object']), 2);
                if ($maxLevels) {
                    Sage::$maxLevels = $maxLevels;
                }

                $output .= '    ' . self::_colorize('└' . str_repeat('─', 74) . '┘', 'header');
            }

            if ($stepNo !== $lastStep) {
                $output .= self::_colorize(str_repeat('─', 80), 'header');
            }
        }

        Sage::$maxLevels = $maxLevels;

        return $output;
    }

    private static function _colorize($text, $type, $nlAfter = true)
    {
        $nl = $nlAfter ? PHP_EOL : '';

        switch (Sage::enabled()) {
            case Sage::MODE_PLAIN:
                if (! self::$_enableColors) {
                    return $text . $nl;
                }

                switch ($type) {
                    case 'value':
                        $text = "<i>{$text}</i>";
                        break;
                    case 'key':
                        // $text = $text;
                        break;
                    case 'type':
                        $text = "<b>{$text}</b>";
                        break;
                    case 'header':
                        $text = "<header>{$text}</header>";
                        break;
                }

                return $text . $nl;
                break;
            case Sage::MODE_CLI:
                if (! self::$_enableColors) {
                    return $text . $nl;
                }

                $optionsMap = array(
                    'key'   => "\x1b[33m",   // yellow
                    'header' => "\x1b[36m",   // cyan
                    'type'  => "\x1b[35;1m", // magenta bold
                    'value' => "\x1b[32m",   // green
                );

                return $optionsMap[$type] . $text . "\x1b[0m" . $nl;
                break;
            case Sage::MODE_TEXT_ONLY:
            default:
                return $text . $nl;
                break;
        }
    }

    private static function _title($text)
    {
        $escaped          = SageHelper::esc($text);
        $lengthDifference = strlen($escaped) - strlen($text);

        $ret = '┌' . str_repeat('─', 78) . '┐' . PHP_EOL;
        if ($text) {
            $ret .= '│' . str_pad($escaped, 78 + $lengthDifference, ' ', STR_PAD_BOTH) . '│' . PHP_EOL;
        }
        $ret .= '└' . str_repeat('─', 78) . '┘';

        return self::_colorize($ret, 'header');
    }

    public static function wrapStart()
    {
        if (Sage::enabled() === Sage::MODE_PLAIN) {
            return '<pre class="_sage_plain">';
        }

        return '';
    }

    public static function wrapEnd($callee, $miniTrace, $prevCaller)
    {
        $lastLine     = self::_colorize(str_repeat('═', 80), 'header');
        $isHtml       = Sage::enabled() === Sage::MODE_PLAIN;
        $lastChar     = $isHtml ? '</pre>' : '';
        $traceDisplay = '';

        if (! Sage::$displayCalledFrom) {
            return $lastLine . $lastChar;
        }

        if (! empty($miniTrace)) {
            $traceDisplay = $isHtml ? '<ol>' : PHP_EOL;
            $i            = 0;
            foreach ($miniTrace as $step) {
                $traceDisplay .= $isHtml ? '<li>' : '           ';
                $traceDisplay .= SageHelper::ideLink($step['file'], $step['line']);
                $traceDisplay .= $isHtml ? '' : PHP_EOL;
                if ($i++ > 2) {
                    break;
                }
            }
            $traceDisplay .= $isHtml ? '</ol>' : '';
        }

        return $lastLine
            . self::_colorize(
                'Call stack ' . SageHelper::ideLink($callee['file'], $callee['line']) . $traceDisplay,
                'header'
            )
            . $lastChar;
    }

    private static function _drawHeader(SageVariableData $varData)
    {
        $output = '';

        if ($varData->access) {
            $output .= ' ' . $varData->access;
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= ' ' . self::_colorize(SageHelper::esc($varData->name), 'key', false);
        }

        if ($varData->operator) {
            $output .= ' ' . $varData->operator;
        }

        $output .= ' ' . self::_colorize($varData->type, 'type', false);

        if ($varData->size !== null) {
            $output .= ' (' . $varData->size . ')';
        }

        if ($varData->value !== null && $varData->value !== '') {
            $output .= ' ' . self::_colorize($varData->value, 'value', false);
        }

        return ltrim($output);
    }

    public static function init()
    {
        if (! Sage::$cliColors) {
            self::$_enableColors = false;
        } elseif (isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR') !== false) {
            self::$_enableColors = false;
        } elseif (getenv('TERM_PROGRAM') === 'Hyper') {
            self::$_enableColors = true;
        } elseif (DIRECTORY_SEPARATOR === '\\') {
            self::$_enableColors =
                function_exists('sapi_windows_vt100_support')
                || getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        } else {
            self::$_enableColors = true;
        }

        if (Sage::enabled() !== Sage::MODE_PLAIN) {
            return '';
        }

        return <<<'HTML'
<style>._sage_plain i{color:#d00;font-style:normal}._sage_plain header{font-weight:bold;display:inline} ._sage_plain ol{padding-left:6em;margin:0}</style>
HTML;
    }
}
