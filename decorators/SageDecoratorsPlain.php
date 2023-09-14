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

    public function setAssetsNeeded($added)
    {
        self::$needsAssets = $added;
    }

    private static $_enableColors;
    private static $levelColors = array();

    public function decorate(SageVariableData $varData, $level = 0)
    {
        $output = '';
        if ($level === 0) {
            $name          = $varData->name ? $varData->name : '';
            $varData->name = null;

            $output .= $this->title($name);
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

        $output .= $space . $this->drawHeader($varData);

        if (isset($varData->extendedValue)) {
            $output .= ' ' . ($varData->type === 'array' ? '[' : '(') . PHP_EOL;

            if (is_array($varData->extendedValue)) {
                foreach ($varData->extendedValue as $k => $v) {
                    if (is_string($v)) {
                        $output .= $space . $s
                            . $this->colorize($k, 'key', false) . ': '
                            . $this->colorize($v, 'value');
                    } else {
                        $output .= $this->decorate($v, $level + 1);
                    }
                }
            } elseif (is_string($varData->extendedValue)) {
                $output .= $space . $s . $this->colorize($varData->extendedValue, 'value');
            } else {
                // throw new RuntimeException();
                // $output .= self::decorate($varData->extendedValue, $level + 1); // it's SageVariableData
            }
            $output .= $space . ($varData->type === 'array' ? ']' : ')');
        }

        $output .= PHP_EOL;

        return $output;
    }

    /** @param SageTraceStep[] $traceData */
    public function decorateTrace(array $traceData, $pathsOnly = false)
    {
        $lastStepNumber = count($traceData);
        $stepNumber     = 1;
        $output         = $this->title($pathsOnly ? 'QUICK TRACE' : 'TRACE');

        // ASCII art 🎨
        $_________________ = '────────────────────────────────────────────────────────────────────────────────';
        $____Arguments____ = '    ┌────────────────────────── Arguments ─────────────────────────────────┐';
        $__Callee_Object__ = '    ┌───────────────────────── Callee Object ──────────────────────────────┐';
        $L________________ = '    └──────────────────────────────────────────────────────────────────────┘';
        $_________________ = $this->colorize($_________________, 'header');
        $____Arguments____ = $this->colorize($____Arguments____, 'header');
        $__Callee_Object__ = $this->colorize($__Callee_Object__, 'header');
        $L________________ = $this->colorize($L________________, 'header');

        foreach ($traceData as $step) {
            $output .= str_pad($stepNumber++ . ': ', 4, ' ');
            $output .= $this->colorize($step->fileLine, 'header');

            if ($step->functionName) {
                $output .= '    ' . $step->functionName;
                $output .= PHP_EOL;
            }

            if (! $pathsOnly && $step->arguments) {
                $output .= $____Arguments____;

                foreach ($step->arguments as $argument) {
                    $output .= $this->decorate($argument, 2);
                }

                $output .= $L________________;
            }

            if (! $pathsOnly && $step->object) {
                $output .= $__Callee_Object__;

                $output .= $this->decorate($step->object, 2);

                $output .= $L________________;
            }

            if ($stepNumber !== $lastStepNumber) {
                $output .= $_________________;
            }
        }

        return $output;
    }

    private function colorize($text, $type, $nlAfter = true)
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
            case Sage::MODE_TEXT_ONLY:
            default:
                return $text . $nl;
        }
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

    public function wrapStart()
    {
        if (Sage::enabled() === Sage::MODE_PLAIN) {
            return '<pre class="_sage_plain">';
        }

        return '';
    }

    public function wrapEnd($callee, $miniTrace, $prevCaller)
    {
        $lastLine     = '════════════════════════════════════════════════════════════════════════════════';
        $lastChar     = Sage::enabled() === Sage::MODE_PLAIN ? '</pre>' : '';
        $traceDisplay = '';

        if (! Sage::$displayCalledFrom) {
            return $this->colorize($lastLine . $lastChar, 'header');
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

        return $this->colorize(
                $lastLine . PHP_EOL
                . 'Call stack ' . SageHelper::ideLink($callee['file'], $callee['line'])
                . $traceDisplay,
                'header'
            )
            . $lastChar;
    }

    private function drawHeader(SageVariableData $varData)
    {
        $output = '';

        if ($varData->access) {
            $output .= ' ' . $this->colorize(SageHelper::esc($varData->access), 'access', false);
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= ' ' . $this->colorize(SageHelper::esc($varData->name), 'key', false);
        }

        if ($varData->operator) {
            $output .= ' ' . $varData->operator;
        }

        $type = $varData->type;
        if ($varData->size !== null) {
            $type .= ' (' . $varData->size . ')';
        }

        $output .= ' ' . $this->colorize($type, 'type', false);

        if ($varData->value !== null && $varData->value !== '') {
            $output .= ' ' . $this->colorize($varData->value, 'value', false);
        }

        return ltrim($output);
    }

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

        if (Sage::enabled() !== Sage::MODE_PLAIN) {
            return '';
        }

        return '<style>._sage_plain{text-shadow: #eee 0 0 7px;}._sage_plain *{display: inline;margin: 0;font-size: 1em}._sage_plain h1{color:#5aF}._sage_plain var{color:#d11}._sage_plain dfn{color:#3d3}._sage_plain a{color: inherit;filter: brightness(0.85);}</style>'
            . '<script>window.onload=function(){document.querySelectorAll("._sage_plain a").forEach(el=>el.addEventListener("click",e=>{e.preventDefault();let X=new XMLHttpRequest;X.open("GET",e.target.href);X.send()}))}</script>';
    }
}
