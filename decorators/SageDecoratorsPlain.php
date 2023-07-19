<?php
/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */

class SageDecoratorsPlain
{
    public static $firstRun = true;
    private static $_enableColors;
    private static $levelColors = [];

    public static function decorate(SageVariableData $varData, $level = 0)
    {
        $output = '';
        if ($level === 0) {
            $name          = $varData->name ? $varData->name : '';
            $varData->name = null;

            $output .= self::_title($name);
        }

        // make each level different-color
        self::$levelColors = array_slice(self::$levelColors, 0, $level);
        $s                 = '    ';
        $space             = '';
        if (Sage::enabled() === Sage::MODE_CLI) {
            for ($i = 0; $i < $level; $i++) {
                if (! array_key_exists($i, self::$levelColors)) {
                    self::$levelColors[$i] = rand(1, 231);
                }
                $color = self::$levelColors[$i];
                $space .= "\x1b[38;5;{$color}m┆\x1b[0m   ";
            }
        } else {
            $space = str_repeat($s, $level);
        }

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
            $output .= $space . ($varData->type === 'array' ? ']' : ')');
        }

        $output .= PHP_EOL;

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
                    case 'key':
                        $text = "<dfn>{$text}</dfn>";
                        break;
                    case 'access':
                        $text = "<i>{$text}</i>";
                        break;
                    case 'value':
                        $text = "<var>{$text}</var>";
                        break;
                    case 'type':
                        $text = "<b>{$text}</b>";
                        break;
                    case 'header':
                        $text = "<h1>{$text}</h1>";
                        break;
                }

                return $text . $nl;
                break;
            case Sage::MODE_CLI:
                if (! self::$_enableColors) {
                    return $text . $nl;
                }

                /*
                 * Black       0;30     Dark Gray     1;30
                 * Red         0;31     Light Red     1;31
                 * Green       0;32     Light Green   1;32
                 * Brown       0;33     Yellow        1;33
                 * Blue        0;34     Light Blue    1;34
                 * Purple      0;35     Light Purple  1;35
                 * Cyan        0;36     Light Cyan    1;36
                 * Light Gray  0;37     White         1;37
                 *
                 * Format:
                 *   \x1b[[light];[color];[font]m
                 *  light: 1/0
                 *  color: 30-37
                 *  font: 1 - bold, 3 - italic, 4 - underline, 7 - invert, 9 - strikethrough
                 *
                 * https://misc.flogisoft.com/bash/tip_colors_and_formatting
                 */

                $optionsMap = array(
                    'key'    => "\x1b[32m",
                    'access' => "\x1b[3m",
                    'header' => "\x1b[38;5;75m",
                    'type'   => "\x1b[1m",
                    'value'  => "\x1b[31m",
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
        $lastLine     = str_repeat('═', 80);
        $lastChar     = Sage::enabled() === Sage::MODE_PLAIN ? '</pre>' : '';
        $traceDisplay = '';

        if (! Sage::$displayCalledFrom) {
            return self::_colorize($lastLine . $lastChar, 'header');
        }

        if (! empty($miniTrace)) {
            $traceDisplay = PHP_EOL;
            $i            = 0;
            foreach ($miniTrace as $step) {
                $traceDisplay .= '        ' . $i + 2 . '. ';
                $traceDisplay .= SageHelper::ideLink($step['file'], $step['line']);
                $traceDisplay .= PHP_EOL;
                if ($i++ > 2) {
                    break;
                }
            }
            $traceDisplay .= '';
        }

        return self::_colorize(
                $lastLine . PHP_EOL
                . 'Call stack ' . SageHelper::ideLink($callee['file'], $callee['line'])
                . $traceDisplay,
                'header',
            )
            . $lastChar;
    }

    private static function _drawHeader(SageVariableData $varData)
    {
        $output = '';

        if ($varData->access) {
            $output .= ' ' . self::_colorize(SageHelper::esc($varData->access), 'access', false);
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= ' ' . self::_colorize(SageHelper::esc($varData->name), 'key', false);
        }

        if ($varData->operator) {
            $output .= ' ' . $varData->operator;
        }

        $type = $varData->type;
        if ($varData->size !== null) {
            $type .= ' (' . $varData->size . ')';
        }

        $output .= ' ' . self::_colorize($type, 'type', false);

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
<style>._sage_plain{text-shadow: #eee 0 0 7px;}._sage_plain *{display: inline;margin: 0;font-size: 1em}._sage_plain h1{color:#5aF}._sage_plain var{color:#d11}._sage_plain dfn{color:#3d3}._sage_plain a{color: inherit;filter: brightness(0.85);}</style>
HTML
            . <<<HTML
<script>window.onload=function(){document.querySelectorAll('._sage_plain a').forEach(el=>el.addEventListener('click',e=>{e.preventDefault();let X=new XMLHttpRequest;X.open('GET',e.target.href);X.send()}))}</script>

HTML;
    }
}
