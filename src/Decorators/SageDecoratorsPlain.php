<?php

/**
 * @internal
 */
class SageDecoratorsPlain implements SageDecoratorsInterface
{
    protected static $needsAssets = true;

    // todo disable it now, remove it someday
    private static $onlySimpleTraces = true;

    // repeated methods due to the way old PHP versions handle static variables on dynamic classnames :)
    public function areAssetsNeeded()
    {
        return self::$needsAssets;
    }

    public function setAssetsNeeded($on)
    {
        self::$needsAssets = $on;
    }

    private static $enableColors;
    private static $outputWidth = 120;
    const FALLBACK_TERMINAL_WIDTH = 120;
    const MIN_TERMINAL_WIDTH = 120;

    /**
     * Enhance cli mode by marking each level with a different color so we can visually spot when we scrolled passed
     * the current variable.
     */
    private static $levelColors = array();

    public function decorate(SageParsedVariable $varData, $level = 0, $prefix = '', $skipHeader = false)
    {
        $output = '';
        $space  = $prefix . $this->getIndentation($level);

        if (! $skipHeader) {
            if ($level === 0) {
                $output .= $this->drawBigTitle($varData->name);
                // We'll put the name in the title, don't repeat it in the header
                $varData->name = null;
            }

            $header = $this->drawHeader($varData);
            $output .= $space . $header;

            if ($header && $varData->trace) {
                $output .= PHP_EOL;
            }
        }

        if ($varData->trace) {
            return $output . $this->decorateTrace($varData->trace, $level + 1);
        }

        // todo make sure there is max one extended representation if we're dumping in plain mode
        $extendedView = reset($varData->alternativeViews);
        if ($extendedView) {
            if (! $skipHeader) {
                $output .= ' ' . ($varData->type === 'array' ? '[' : '(') . PHP_EOL;
            }

            switch ($extendedView->displayType) {
                case SageParsedVariableContents::STRING:
                    $output .= $this->value($extendedView->contents);
                    break;
                case SageParsedVariableContents::PLAIN_TEXT_ROWS:
                    $maxKeyLength = 0;
                    foreach ($extendedView->contents as $row) {
                        $maxKeyLength = max($maxKeyLength, strlen($row['name']));
                    }
                    foreach ($extendedView->contents as $row) {
                        $output .=
                            $space . '  '
                            . $this->key(str_pad($row['name'], $maxKeyLength) . ':')
                            . ' ' . $this->value($row['value']);
                    }
                    break;
                case SageParsedVariableContents::RICH_ROWS:
                    if ($extendedView->contents) {
                        foreach ($extendedView->contents as $row) {
                            $output .= $this->decorate($row, $level + 1, $prefix);
                        }
                    }
                    break;
                case SageParsedVariableContents::DUMP:
                    $output .= $this->decorate(SageParser::parse($extendedView->contents), $level + 1, $prefix);
                    break;
                case SageParsedVariableContents::DUMP_WITHOUT_TOP_PARENT:
                    $output .= $this->decorate($extendedView->contents, $level, $prefix, true);
                    break;
                case SageParsedVariableContents::TRACE:
                    $output .= $this->decorateTrace($extendedView->contents, $level + 1);
                    break;
                default:
                    throw new SageLogicException('unexpected variable content type', $extendedView->displayType);
            }

            if (! $skipHeader) {
                $output .= $space . ($varData->type === 'array' ? ']' : ')');
            }
        }

        if (! $skipHeader) {
            $output .= PHP_EOL;
        }

        return $output;
    }

    # region trace

    /**
     * @param int $level
     *
     * @return string
     */
    private function decorateTrace(SageTrace $trace, $level)
    {
        $blacklistedStepsInARow = 0;
        $output                 = '';
        foreach ($trace->steps as $stepNumber => $step) {
            if (
                ! self::$onlySimpleTraces
                && $step->isBlackListed
                && $stepNumber !== 0
            ) {
                $blacklistedStepsInARow++;
                continue;
            }

            if ($blacklistedStepsInARow) {
                if ($blacklistedStepsInARow <= 5) {
                    for ($j = $blacklistedStepsInARow; $j > 0; $j--) {
                        $output .= $this->drawTraceStep(
                            $stepNumber - $j,
                            $trace[$stepNumber - $j],
                            $level
                        );
                    }
                } else {
                    $output .= $this->drawTraceStep(
                        $stepNumber,
                        "...\n[{$blacklistedStepsInARow} steps skipped]\n...\n",
                        $level
                    );
                }

                $blacklistedStepsInARow = 0;
            }
            $output .= $this->drawTraceStep($stepNumber, $step, $level);
        }

        if ($blacklistedStepsInARow > 1) {
            $output .= "...\n[{$blacklistedStepsInARow} steps skipped]\n";
        }

        return $output;
    }

    # region draw

    /**
     * @param int $stepNumber
     * @param SageTraceStep|string $step
     * @param int $level
     *
     * @return string
     */
    private function drawTraceStep($stepNumber, $step, $level)
    {
        $output = '';
        // Just looks better in all scenarios
        if ($level === 1) {
            $level = 0;
        }
        $lineIndentation = $this->getIndentation($level);

        // ASCII art 🎨
        $_________________ = '────────────────────────────────────────────────────────────────────────────────';
        $____Arguments____ = '  ┌────────────────────────── Arguments ─────────────────────────────────┐';
        $__Callee_Object__ = '  ┌───────────────────────── Callee Object ──────────────────────────────┐';
        $L________________ = '  └──────────────────────────────────────────────────────────────────────┘';

        $_________________ = $this->colorize($lineIndentation . $_________________, 'header');
        $____Arguments____ = $this->colorize($lineIndentation . $____Arguments____, 'header');
        $__Callee_Object__ = $this->colorize($lineIndentation . $__Callee_Object__, 'header');
        $L________________ = $this->colorize($lineIndentation . $L________________, 'header');

        // Hack to display "N steps skipped" instead of the step
        if (is_string($step)) {
            $output .= $lineIndentation . $step;

            $output .= $_________________;

            return $output;
        }

        if ($stepNumber === 0) {
            $output .= $_________________;
        }

        $output .= $lineIndentation . str_pad($stepNumber . ': ', 4, ' ');
        $output .= $this->colorize($step->fileLine, 'header');

        if ($step->functionName) {
            $output .= $lineIndentation . '    ' . $step->functionName;
            $output .= PHP_EOL;
        }

        if ($step->arguments && ! self::$onlySimpleTraces) {
            $output .= $____Arguments____;

            foreach ($step->arguments as $argument) {
                $output .= $this->decorate($argument, $level, '  ');
            }

            $output .= $L________________;
        }

        if ($step->object && ! self::$onlySimpleTraces) {
            $output .= $__Callee_Object__;

            $output .= $this->decorate($step->object, $level + 1, '  ');

            $output .= $L________________;
        }

        $output .= $_________________;

        return $output;
    }

    /**
     * @param int $level
     *
     * @return string
     */
    private function getIndentation($level)
    {
        if (Sage::enabled() === Sage::MODE_CLI) {
            $space             = '';
            $s                 = '  ';
            self::$levelColors = array_slice(self::$levelColors, 0, $level);

            for ($i = 0; $i < $level; $i++) {
                if (! array_key_exists($i, self::$levelColors)) {
                    self::$levelColors[$i] = rand(1, 231);
                }
                $color = self::$levelColors[$i];
                $space .= "\x1b[38;5;{$color}m┆\x1b[0m" . $s;
            }
        } else {
            $s     = '    ';
            $space = str_repeat($s, $level);
        }

        return $space;
    }

    /**
     * @return string
     */
    private function drawHeader(SageParsedVariable $varData)
    {
        $output = '';

        if ($varData->access) {
            $output .= ' ' . $this->colorize(
                    SageHelper::esc($varData->access),
                    'access',
                    false
                );
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= ' ' . $this->key(SageHelper::esc($varData->name));
        }

        if ($varData->operator) {
            $output .= ' ' . $varData->operator;
        }

        $type = $varData->type;
        if ($varData->subtype !== null && $varData->subtype !== '') {
            $type .= $varData->subtype;
        }
        if ($varData->size !== null && $varData->size !== '') {
            $type .= ' (' . $varData->size . ')';
        }
        if (! $varData->error && $varData->hash !== null && $varData->hash !== '') {
            $type .= ' [' . $varData->hash . ']';
        }

        if ($type) {
            $output .= ' ' . $this->colorize($type, 'type', false);
        }

        if ($varData->value !== null && $varData->value !== '') {
            $output .= ' ' . $this->value($varData->value, false);
        }

        if ($varData->error !== null && $varData->error !== '') {
            $output .= ' ' . $this->colorize($varData->error, 'error', false);
        }

        return ltrim($output);
    }

    private function drawBigTitle($text)
    {
        $line = str_repeat('─', self::$outputWidth - 2);
        $ret  = '╭' . $line . '┐' . PHP_EOL;
        if ($text) {
            $ret .= '│' . self::strPadBoth(SageHelper::esc($text), self::$outputWidth - 2) . '│' . PHP_EOL;
        }
        $ret .= '└' . $line . '╯';

        return $this->colorize($ret, 'header');
    }

    private static function strPadBoth($input, $pad_length, $pad_string = ' ')
    {
        if (function_exists('mb_str_pad')) {
            return mb_str_pad($input, $pad_length, $pad_string, STR_PAD_BOTH);
        }

        return str_pad($input, $pad_length, $pad_string, STR_PAD_BOTH);
    }

    private function colorize($text, $type, $nlAfter = true)
    {
        $nl = $nlAfter ? PHP_EOL : '';

        switch (Sage::enabled()) {
            case Sage::MODE_PLAIN_HTML:
                if (! self::$enableColors) {
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
                    case 'error':
                        $text = "<em>{$text}</em>";
                        break;
                }

                return $text . $nl;
            case Sage::MODE_CLI:
                if (! self::$enableColors) {
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
                 *   \x1b[[light;][color];[font]m
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
                    'error'  => "\x1b[1;35;4m",
                );

                return $optionsMap[$type] . $text . "\x1b[0m" . $nl;
            case Sage::MODE_TEXT_ONLY:
            default:
                return $text . $nl;
        }
    }

    private function key($text, $nlAfter = false)
    {
        return $this->colorize($text, 'key', $nlAfter);
    }

    private function value($text, $nlAfter = true)
    {
        //        $n = strpos($text, "\n");
        //        if ($n && $n !== strlen($text) - 1) { // todo - test string and closure with source
        //            $text = '"""' . "\n" . $text . "\n" . '"""';
        //            $text = "\n" . $text;
        //        }

        return $this->colorize($text, 'value', $nlAfter);
    }

    # region init

    public function init()
    {
        if (! Sage::$cliColors) {
            self::$enableColors = false;
        } elseif (isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR') !== false) {
            self::$enableColors = false;
        } elseif (getenv('TERM_PROGRAM') === 'Hyper') {
            self::$enableColors = true;
        } elseif (DIRECTORY_SEPARATOR === '\\') {
            self::$enableColors =
                function_exists('sapi_windows_vt100_support')
                || getenv('ANSICON') !== false
                || getenv('ConEmuANSI') === 'ON'
                || getenv('TERM') === 'xterm';
        } else {
            self::$enableColors = true;
        }

        if (self::$enableColors) {
            self::$outputWidth = min(self::getTerminalWidth(), self::MIN_TERMINAL_WIDTH);
        } else {
            self::$outputWidth = self::FALLBACK_TERMINAL_WIDTH;
        }

        if (Sage::enabled() !== Sage::MODE_PLAIN_HTML) {
            return '';
        }

        return '<style>' . file_get_contents(SAGE_DIR . 'src/resources/compiled/plain-html.css') . '</style>'
            . '<script>window.onload=function(){document.querySelectorAll("._sage_plain a").forEach(el=>el.addEventListener("click",e=>{e.preventDefault();let X=new XMLHttpRequest;X.open("GET",e.target.href);X.send()}))}</script>';
    }

    public function wrapStart()
    {
        if (Sage::enabled() === Sage::MODE_PLAIN_HTML) {
            return '<pre class="_sage_plain">';
        }

        return '';
    }

    public function wrapEnd($caller)
    {
        $lastLine     = str_repeat('═', self::$outputWidth);
        $lastChar     = Sage::enabled() === Sage::MODE_PLAIN_HTML ? '</pre>' : '';
        $traceDisplay = '';

        if (! Sage::$displayCalledFrom) {
            return $this->colorize($lastLine . $lastChar, 'header');
        }

        foreach ($caller->trace as $i => $step) {
            if ($i === 0) {
                $traceDisplay .= PHP_EOL
                    . 'Call stack ' . SageHelper::getIdeLink($step['file'], $step['line'])
                    . PHP_EOL;
                continue;
            }

            $traceDisplay .= '        ' . ($i + 1) . '. ';
            $traceDisplay .= SageHelper::getIdeLink($step['file'], $step['line']);
            $traceDisplay .= PHP_EOL;
            if ($i > 3) {
                break;
            }
        }

        return $this->colorize($lastLine . $traceDisplay, 'header')
            . $lastChar;
    }

    private static function getTerminalWidth()
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return self::getSttyColumns();
        }

        $ansicon = getenv('ANSICON');
        if ($ansicon !== false && preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim($ansicon), $matches)) {
            // extract [w, H] from "wxh (WxH)"
            return (int) $matches[1];
        }

        if (
            function_exists('sapi_windows_vt100_support')
            && ! sapi_windows_vt100_support(fopen('php://stdout', 'w'))
            && self::hasSttyAvailable()
        ) {
            // only use stty on Windows if the terminal does not support vt100 (e.g. Windows 7 + git-bash)
            // testing for stty in a Windows 10 vt100-enabled console will implicitly disable vt100 support on STDOUT
            return self::getSttyColumns();
        }

        return self::getWindowsTerminalWidth();
    }

    private static function hasSttyAvailable()
    {
        // skip check if shell_exec function is disabled
        if (! function_exists('shell_exec')) {
            return false;
        }

        $str = \DIRECTORY_SEPARATOR === '\\'
            ? 'NUL'
            : '/dev/null';

        return (bool) shell_exec('stty 2> ' . $str);
    }

    private static function getSttyColumns()
    {
        if (! function_exists('shell_exec')) {
            return self::FALLBACK_TERMINAL_WIDTH;
        }

        $size = shell_exec('stty size');
        if ($size) {
            $dimensions = explode(' ', trim($size));
            if (isset($dimensions[1])) {
                return (int) $dimensions[1];
            }
        }

        return self::FALLBACK_TERMINAL_WIDTH;
    }

    private static function getWindowsTerminalWidth()
    {
        if (preg_match('/Columns:\s+(\d+)/', shell_exec('mode con'), $matches)) {
            return (int) $matches[1];
        }

        return self::FALLBACK_TERMINAL_WIDTH;
    }
}
