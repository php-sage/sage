<?php

/**
 * @internal
 */

class SageDecoratorsPlain implements SageDecoratorsInterface
{
    protected static $needsAssets = true;

    // repeated methods due to the way old PHP versions handle static variables on dynamic classnames :)
    public function areAssetsNeeded()
    {
        return self::$needsAssets;
    }

    public function setAssetsNeeded($on)
    {
        self::$needsAssets = $on;
    }

    private static $_enableColors;
    /**
     * Enhance cli mode by marking each level with a different color so we can visually spot when we scrolled passed
     * the current variable.
     */
    private static $levelColors = array();

    public function decorate(SageParsedVariable $varData, $level = 0, $prefix = '')
    {
        if ($varData->traceSteps) {
            return $this->decorateTrace($varData);
        }

        $output = '';
        if ($level === 0) {
            $name          = $varData->name ? $varData->name : '';
            $varData->name = null;

            $output .= $this->title($name);
        }

        $s     = '  ';
        $space = $prefix;
        if (Sage::enabled() === Sage::MODE_CLI) {
            self::$levelColors = array_slice(self::$levelColors, 0, $level);

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

        $output .= $space . $this->drawRowHeader($varData);

        if ($varData->extendedView !== null) {
            $variableContents = $varData->extendedView;
            $output           .= ' ' . ($varData->type === 'array' ? '[' : '(') . PHP_EOL;

            switch ($variableContents->displayType) {
                case SageParsedVariableContents::CONTENT_TYPE_STRING:
                    $output .= $this->value($variableContents->contents);
                    break;
                case SageParsedVariableContents::CONTENT_TYPE_PLAIN_TEXT_ROWS:
                    $maxKeyLength = 0;
                    foreach ($variableContents->contents as $row) {
                        $maxKeyLength = max($maxKeyLength, strlen($row['name']));
                    }
                    foreach ($variableContents->contents as $row) {
                        $output .=
                            $space . $s
                            . $this->key(str_pad($row['name'], $maxKeyLength) . ':')
                            . ' ' . $this->value($row['value']);
                    }
                    break;
                case SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS:
                    if ($variableContents->contents) {
                        foreach ($variableContents->contents as $row) {
                            $output .= $this->decorate($row, $level + 1, $prefix);
                        }
                    }
                    break;
                case SageParsedVariableContents::CONTENT_TYPE_DUMP:
                    $output .= $this->decorate(SageParser::parse($variableContents->contents), $level + 1, $prefix);
                    break;
                default:
                    throw new SageLogicException('unexpected variable content type');
            }

            $output .= $space . ($varData->type === 'array' ? ']' : ')');
        }

        $output .= PHP_EOL;

        return $output;
    }

    # SECTION: trace

    public function decorateTrace(SageParsedVariable $trace, $pathsOnly = false)
    {
        $traceData = $trace->traceSteps;

        $lastStepNumber = count($traceData);
        $output         = $this->title($pathsOnly ? 'QUICK TRACE' : 'TRACE');

        $blacklistedStepsInARow = 0;
        foreach ($traceData as $stepNumber => $step) {
            if (
                $stepNumber >= Sage::$minimumTraceStepsToShowFull
                && $step->isBlackListed
            ) {
                $blacklistedStepsInARow++;
                continue;
            }

            if ($blacklistedStepsInARow) {
                if ($blacklistedStepsInARow <= 5) {
                    for ($j = $blacklistedStepsInARow; $j > 0; $j--) {
                        $output .= $this->drawTraceStep(
                            $stepNumber - $j,
                            $traceData[$stepNumber - $j],
                            $pathsOnly,
                            $lastStepNumber
                        );
                    }
                } else {
                    $output .= "...\n[{$blacklistedStepsInARow} steps skipped]\n...\n"
                        . $this->colorize(
                            '────────────────────────────────────────────────────────────────────────────────',
                            'header'
                        );
                }

                $blacklistedStepsInARow = 0;
            }
            $output .= $this->drawTraceStep($stepNumber, $step, $pathsOnly, $lastStepNumber);
        }

        if ($blacklistedStepsInARow > 1) {
            $output .= "...\n[{$blacklistedStepsInARow} steps skipped]\n";
        }

        return $output;
    }

    # SECTION: draw

    private function drawTraceStep($stepNumber, $step, $pathsOnly, $lastStepNumber)
    {
        $output = '';

        // ASCII art 🎨
        $_________________ = '────────────────────────────────────────────────────────────────────────────────';
        $____Arguments____ = '  ┌────────────────────────── Arguments ─────────────────────────────────┐';
        $__Callee_Object__ = '  ┌───────────────────────── Callee Object ──────────────────────────────┐';
        $L________________ = '  └──────────────────────────────────────────────────────────────────────┘';
        $_________________ = $this->colorize($_________________, 'header');
        $____Arguments____ = $this->colorize($____Arguments____, 'header');
        $__Callee_Object__ = $this->colorize($__Callee_Object__, 'header');
        $L________________ = $this->colorize($L________________, 'header');

        $output .= str_pad($stepNumber++ . ': ', 4, ' ');
        $output .= $this->colorize($step->fileLine, 'header');

        if ($step->functionName) {
            $output .= '    ' . $step->functionName;
            $output .= PHP_EOL;
        }

        if (! $pathsOnly && $step->arguments) {
            $output .= $____Arguments____;

            foreach ($step->arguments as $argument) {
                $output .= $this->decorate($argument, 2, '  ');
            }

            $output .= $L________________;
        }

        if (! $pathsOnly && $step->object) {
            $output .= $__Callee_Object__;

            $output .= $this->decorate($step->object, 2, '  ');

            $output .= $L________________;
        }

        if ($stepNumber !== $lastStepNumber) {
            $output .= $_________________;
        }

        return $output;
    }

    private function title($text)
    {
        $escaped          = SageHelper::esc($text);
        $lengthDifference = strlen($escaped) - strlen($text);

        $ret = '┌──────────────────────────────────────────────────────────────────────────────┐' . PHP_EOL;
        if ($text) {
            $ret .= '│' . str_pad($escaped, 78 + $lengthDifference, ' ', STR_PAD_BOTH) . '│' . PHP_EOL;
        }
        $ret .= '└──────────────────────────────────────────────────────────────────────────────┘';

        return $this->colorize($ret, 'header');
    }

    private function colorize($text, $type, $nlAfter = true)
    {
        $nl = $nlAfter ? PHP_EOL : '';

        switch (Sage::enabled()) {
            case Sage::MODE_PLAIN_HTML:
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
                    case 'error':
                        $text = "<em>{$text}</em>";
                        break;
                }

                return $text . $nl;
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

    private function drawRowHeader(SageParsedVariable $varData)
    {
        $output = '';

        if ($varData->access) {
            $output .= ' ' . $this->colorize(SageHelper::esc($varData->access), 'access', false);
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= ' ' . $this->key(SageHelper::esc($varData->name));
        }

        if ($varData->operator) {
            $output .= ' ' . $varData->operator;
        }

        $type = $varData->type;
        if ($varData->subtype !== null && $varData->subtype !== '') {
            $type .= ' ' . $varData->subtype;
        }
        if ($varData->size !== null && $varData->size !== '') {
            $type .= ' (' . $varData->size . ')';
        }

        $output .= ' ' . $this->colorize($type, 'type', false);

        if ($varData->value !== null && $varData->value !== '') {
            $output .= ' ' . $this->value($varData->value, false);
        }

        if ($varData->error !== null && $varData->error !== '') {
            $output .= ' ' . $this->colorize($varData->error, 'error', false);
        }

        return ltrim($output);
    }

    # SECTION: init

    public function init()
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
        $lastLine     = '════════════════════════════════════════════════════════════════════════════════';
        $lastChar     = Sage::enabled() === Sage::MODE_PLAIN_HTML ? '</pre>' : '';
        $traceDisplay = '';

        if (! Sage::$displayCalledFrom) {
            return $this->colorize($lastLine . $lastChar, 'header');
        }

        foreach ($caller->miniTrace as $i => $step) {
            if ($i === 0) {
                $traceDisplay .= PHP_EOL
                    . 'Call stack ' . SageHelper::ideLink($step['file'], $step['line'])
                    . PHP_EOL;
                continue;
            }

            $traceDisplay .= '        ' . ($i + 1) . '. ';
            $traceDisplay .= SageHelper::ideLink($step['file'], $step['line']);
            $traceDisplay .= PHP_EOL;
            if ($i > 3) {
                break;
            }
        }

        return $this->colorize($lastLine . $traceDisplay, 'header')
            . $lastChar;
    }

}
