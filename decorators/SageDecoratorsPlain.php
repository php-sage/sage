<?php
/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */

class SageDecoratorsPlain
{
    public static $firstRun = true;
    private static $_enableColors;

    private static $_cliEffects = array(
        // effects
        'bold'             => '1',
        'dark'             => '2',
        'italic'           => '3',
        'underline'        => '4',
        'blink'            => '5',
        'reverse'          => '7',
        'concealed'        => '8',
        'default'          => '39',

        // colors
        'black'            => '30',
        'red'              => '31',
        'green'            => '32',
        'yellow'           => '33',
        'blue'             => '34',
        'magenta'          => '35',
        'cyan'             => '36',
        'light_gray'       => '37',
        'dark_gray'        => '90',
        'light_red'        => '91',
        'light_green'      => '92',
        'light_yellow'     => '93',
        'light_blue'       => '94',
        'light_magenta'    => '95',
        'light_cyan'       => '96',
        'white'            => '97',

        // backgrounds
        'bg_default'       => '49',
        'bg_black'         => '40',
        'bg_red'           => '41',
        'bg_green'         => '42',
        'bg_yellow'        => '43',
        'bg_blue'          => '44',
        'bg_magenta'       => '45',
        'bg_cyan'          => '46',
        'bg_light_gray'    => '47',
        'bg_dark_gray'     => '100',
        'bg_light_red'     => '101',
        'bg_light_green'   => '102',
        'bg_light_yellow'  => '103',
        'bg_light_blue'    => '104',
        'bg_light_magenta' => '105',
        'bg_light_cyan'    => '106',
        'bg_white'         => '107',
    );
    private static $_utfSymbols = array(
        '┌',
        '═',
        '┐',
        '│',
        '└',
        '─',
        '┘',
    );
    private static $_winShellSymbols = array(
        "\xda",
        "\xdc",
        "\xbf",
        "\xb3",
        "\xc0",
        "\xc4",
        "\xd9",
    );
    private static $_htmlSymbols = array(
        "&#9484;",
        "&#9604;",
        "&#9488;",
        "&#9474;",
        "&#9492;",
        "&#9472;",
        "&#9496;",
    );

    public static function decorate(SageVariableData $varData, $level = 0)
    {
        $output = '';
        if ($level === 0) {
            $name = $varData->name ? $varData->name : '';
            $varData->name = null;

            $output .= self::_title($name);
        }


        $space = str_repeat($s = '    ', $level);
        $output .= $space.self::_drawHeader($varData);


        if (isset($varData->extendedValue)) {
            $output .= ' '.($varData->type === 'array' ? '[' : '(').PHP_EOL;

            if (is_array($varData->extendedValue)) {
                foreach ($varData->extendedValue as $v) {
                    $output .= self::decorate($v, $level + 1);
                }
            } elseif (is_string($varData->extendedValue)) {
                $output .= $space.$s.$varData->extendedValue.PHP_EOL; // "depth too great" or similar
            } else {
//                throw new RuntimeException();
//                $output .= self::decorate($varData->extendedValue, $level + 1); // it's SageVariableData
            }
            $output .= $space.($varData->type === 'array' ? ']' : ')').PHP_EOL;
        } else {
            $output .= PHP_EOL;
        }

        return $output;
    }

    public static function decorateTrace($traceData)
    {
        $output = self::_title('TRACE');
        $lastStep = count($traceData);
        foreach ($traceData as $stepNo => $step) {
            $title = str_pad(++$stepNo.': ', 4, ' ');

            $title .= self::_colorize(
                (
                isset($step['file'])
                    ? SageHelper::ideLink($step['file'], $step['line'])
                    : 'PHP internal call'
                ),
                'title'
            );

            if (! empty($step['function'])) {
                $title .= '    '.$step['function'];
                if (isset($step['args'])) {
                    $title .= '(';
                    if (empty($step['args'])) {
                        $title .= ')';
                    }
                    $title .= PHP_EOL;
                }
            }

            $output .= $title;

            if (! empty($step['args'])) {
                $appendDollar = $step['function'] === '{closure}' ? '' : '$';

                $i = 0;
                foreach ($step['args'] as $name => $argument) {
                    $argument = SageParser::process(
                        $argument,
                        $name ? $appendDollar.$name : '#'.++$i
                    );
                    $argument->operator = $name ? ' =' : ':';
                    $maxLevels = Sage::$maxLevels;
                    if ($maxLevels) {
                        Sage::$maxLevels = $maxLevels + 2;
                    }
                    $output .= self::decorate($argument, 2);
                    if ($maxLevels) {
                        Sage::$maxLevels = $maxLevels;
                    }
                }
                $output .= '    )'.PHP_EOL;
            }

            if (! empty($step['object'])) {
                $output .= self::_colorize(
                    '    '.self::_char('─', 27).' Callee object '.self::_char('─', 34),
                    'title'
                );

                $maxLevels = Sage::$maxLevels;
                if ($maxLevels) {
                    // in cli the terminal window is filled too quickly to display huge objects
                    Sage::$maxLevels = Sage::enabled() === Sage::MODE_CLI
                        ? 1
                        : $maxLevels + 1;
                }
                $output .= self::decorate(SageParser::process($step['object']), 1);
                if ($maxLevels) {
                    Sage::$maxLevels = $maxLevels;
                }
            }

            if ($stepNo !== $lastStep) {
                $output .= self::_colorize(self::_char('─', 80), 'title');
            }
        }

        return $output;
    }


    private static function _colorize($text, $type, $nlAfter = true)
    {
        $nl = $nlAfter ? PHP_EOL : '';

        switch (Sage::enabled()) {
            case Sage::MODE_PLAIN:
                if (! self::$_enableColors) {
                    return $text.$nl;
                }

                switch ($type) {
                    case 'value':
                        $text = "<i>{$text}</i>";
                        break;
                    case 'type':
                        $text = "<b>{$text}</b>";
                        break;
                    case 'title':
                        $text = "<u>{$text}</u>";
                        break;
                }

                return $text.$nl;
                break;
            case Sage::MODE_CLI:
                if (! self::$_enableColors) {
                    return $text.$nl;
                }

                $optionsMap = array(
                    'title' => "\x1b[36m", // cyan
                    'type'  => "\x1b[35;1m", // magenta bold
                    'value' => "\x1b[32m", // green
                );

                return $optionsMap[$type].$text."\x1b[0m".$nl;
                break;
            case Sage::MODE_TEXT_ONLY:
            default:
                return $text.$nl;
                break;
        }
    }


    private static function _char($char, $repeat = null)
    {
        switch (Sage::enabled()) {
            case Sage::MODE_PLAIN:
                $char = self::$_htmlSymbols[array_search($char, self::$_utfSymbols, true)];
                break;
            case Sage::MODE_CLI:
                $inWindowsShell = PHP_SAPI === 'cli' && DIRECTORY_SEPARATOR !== '/';
                if ($inWindowsShell) {
                    $char = self::$_winShellSymbols[array_search($char, self::$_utfSymbols, true)];
                }
                break;
            case Sage::MODE_TEXT_ONLY:
            default:
                break;
        }

        return $repeat ? str_repeat($char, $repeat) : $char;
    }

    private static function _title($text)
    {
        $escaped = SageHelper::decodeStr($text);
        $lengthDifference = strlen($escaped) - strlen($text);

        return
            self::_colorize(
                self::_char('┌').self::_char('─', 78).self::_char('┐').PHP_EOL
                .self::_char('│'),
                'title',
                false
            )

            .self::_colorize(str_pad($escaped, 78 + $lengthDifference, ' ', STR_PAD_BOTH), 'title', false)

            .self::_colorize(self::_char('│').PHP_EOL
                .self::_char('└').self::_char('─', 78).self::_char('┘'),
                'title'
            );
    }

    public static function wrapStart()
    {
        if (Sage::enabled() === Sage::MODE_PLAIN) {
            return '<pre class="-_sage">';
        }

        return '';
    }

    public static function wrapEnd($callee, $miniTrace, $prevCaller)
    {
        $lastLine = self::_colorize(self::_char("═", 80), 'title');
        $lastChar = Sage::enabled() === Sage::MODE_PLAIN ? '</pre>' : '';


        if (! Sage::$displayCalledFrom) {
            return $lastLine.$lastChar;
        }


        return $lastLine
            .self::_colorize(
                'Called from '.SageHelper::ideLink($callee['file'], $callee['line']),
                'title'
            )
            .$lastChar;
    }


    private static function _drawHeader(SageVariableData $varData)
    {

        $output = '';

        if ($varData->access) {
            $output .= ' '.$varData->access;
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= ' '.SageHelper::decodeStr($varData->name);
        }

        if ($varData->operator) {
            $output .= ' '.$varData->operator;
        }

        $output .= ' '.self::_colorize($varData->type, 'type', false);

        if ($varData->size !== null) {
            $output .= ' ('.$varData->size.')';
        }


        if ($varData->value !== null && $varData->value !== '') {
            $output .= ' '.self::_colorize(
                    $varData->value, // escape shell
                    'value',
                    false
                );
        }

        return ltrim($output);
    }

    public static function init()
    {
        self::$_enableColors =
            Sage::$cliColors
            && (DIRECTORY_SEPARATOR === '/' || getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');

        return Sage::enabled() === Sage::MODE_PLAIN
            ? '<style>.-_sage i{color:#d00;font-style:normal}.-_sage u{color:#030;text-decoration:none;font-weight:bold}</style>'
            : '';
    }
}
