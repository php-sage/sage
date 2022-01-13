<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */

class SageDecoratorsRich
{
    public static $firstRun = true;
    // make calls to Sage::dump() from different places in source coloured differently.
    private static $_usedColors = array();

    public static function decorate(SageVariableData $varData)
    {
        $output = '<dl>';

        $allRepresentations = $varData->getAllRepresentations();
        $extendedPresent = ! empty($allRepresentations);

        if ($extendedPresent) {
            $class = '_sage-parent';
            if (Sage::$expandedByDefault) {
                $class .= ' _sage-show';
            }
            $output .= '<dt class="'.$class.'">';
        } else {
            $output .= '<dt>';
        }

        if ($extendedPresent) {
            $output .= '<span class="_sage-popup-trigger" title="Open in new window">&rarr;</span><nav></nav>';
        }

        $output .= self::_drawHeader($varData).$varData->value.'</dt>';


        if ($extendedPresent) {
            $output .= '<dd>';
        }

        if (count($allRepresentations) === 1 && !empty($varData->extendedValue)) {
            $extendedValue = reset($allRepresentations);

            if (is_array($extendedValue)) {
                foreach ($extendedValue as $v) {
                    $output .= self::decorate($v);
                }
            } elseif (is_string($extendedValue)) {
                $output .= "<pre>{$extendedValue}</pre>";
            } else {
                throw new RuntimeException();
//                $output .= self::decorate($varData->extendedValue); // it's SageVariableData
            }

        } elseif ($extendedPresent) {
            $output .= "<ul class=\"_sage-tabs\">";

            $isFirst = true;
            foreach ($allRepresentations as $tabName => $_) {
                $active = $isFirst ? ' class="_sage-active-tab"' : '';
                $isFirst = false;
                $output .= "<li{$active}>".SageHelper::decodeStr($tabName).'</li>';
            }

            $output .= "</ul><ul>";

            foreach ($allRepresentations as $alternative) {
                $output .= "<li>";

                if (is_array($alternative)) {
                    foreach ($alternative as $v) {
                        if (is_string($v)) {
                            $output .= "<pre>{$v}</pre>";
                        } else {
                            $output .= self::decorate($v);
                        }
                    }
                } elseif (is_string($alternative)) {
                    $output .= "<pre>{$alternative}</pre>";
                } elseif (isset($alternative)) {
                    throw new ErrorException();
                    // error in custom parser
                }

                $output .= "</li>";
            }

            $output .= "</ul>";
        }
        if ($extendedPresent) {
            $output .= '</dd>';
        }

        $output .= '</dl>';

        return $output;
    }

    public static function decorateTrace($traceData)
    {
        $output = '<dl class="_sage-trace">';

        foreach ($traceData as $i => $step) {
            $class = '_sage-parent';
            if (Sage::$expandedByDefault) {
                $class .= ' _sage-show';
            }

            $output .= '<dt class="'.$class.'">'
                .'<b>'.($i + 1).'</b> '
                .'<nav></nav>'
                .'<var>';

            if (isset($step['file'])) {
                $output .= SageHelper::ideLink($step['file'], $step['line']);
            } else {
                $output .= 'PHP internal call';
            }

            $output .= '</var>';

            $output .= $step['function'];

            if (isset($step['args'])) {
                $output .= '('.implode(', ', array_keys($step['args'])).')';
            }
            $output .= '</dt><dd>';
            $firstTab = ' class="_sage-active-tab"';
            $output .= '<ul class="_sage-tabs">';

            if (! empty($step['source'])) {
                $output .= "<li{$firstTab}>Source</li>";
                $firstTab = '';
            }

            if (! empty($step['args'])) {
                $output .= "<li{$firstTab}>Arguments</li>";
                $firstTab = '';
            }

            if (! empty($step['object'])) {
                SageParser::reset();
                $calleeDump = SageParser::process($step['object']);

                $output .= "<li{$firstTab}>Callee object [{$calleeDump->type}]</li>";
            }


            $output .= '</ul><ul>';


            if (! empty($step['source'])) {
                $output .= "<li><pre class=\"_sage-source\">{$step['source']}</pre></li>";
            }

            if (! empty($step['args'])) {
                $output .= "<li>";
                foreach ($step['args'] as $k => $arg) {
                    SageParser::reset();
                    $output .= self::decorate(SageParser::process($arg, $k));
                }
                $output .= "</li>";
            }
            if (! empty($step['object'])) {
                $output .= "<li>".self::decorate($calleeDump)."</li>";
            }

            $output .= '</ul></dd>';
        }
        $output .= '</dl>';

        return $output;
    }


    /**
     * called for each dump, opens the html tag
     *
     * @param array $callee caller information taken from debug backtrace
     *
     * @return string
     */
    public static function wrapStart()
    {
        return "<div class=\"_sage\">";
    }


    /**
     * closes Sage::_wrapStart() started html tags and displays callee information
     *
     * @param array $callee     caller information taken from debug backtrace
     * @param array $miniTrace  full path to Sage call
     * @param array $prevCaller previous caller information taken from debug backtrace
     *
     * @return string
     */
    public static function wrapEnd($callee, $miniTrace, $prevCaller)
    {
        if (! Sage::$displayCalledFrom) {
            return '</div>';
        }

        $callingFunction = '';
        $calleeInfo = '';
        $traceDisplay = '';
        if (isset($prevCaller['class'])) {
            $callingFunction = $prevCaller['class'];
        }
        if (isset($prevCaller['type'])) {
            $callingFunction .= $prevCaller['type'];
        }
        if (isset($prevCaller['function'])
            && ! in_array($prevCaller['function'], array('include', 'include_once', 'require', 'require_once'))
        ) {
            $callingFunction .= $prevCaller['function'].'()';
        }
        $callingFunction and $callingFunction = " [{$callingFunction}]";


        if (isset($callee['file'])) {
            $calleeInfo .= 'Called from '.SageHelper::ideLink($callee['file'], $callee['line']);
        }

        if (! empty($miniTrace)) {
            $traceDisplay = '<ol>';
            foreach ($miniTrace as $step) {
                $traceDisplay .= '<li>'.SageHelper::ideLink($step['file'], $step['line']); // closing tag not required
                if (isset($step['function'])
                    && ! in_array($step['function'], array('include', 'include_once', 'require', 'require_once'))
                ) {
                    $classString = ' [';
                    if (isset($step['class'])) {
                        $classString .= $step['class'];
                    }
                    if (isset($step['type'])) {
                        $classString .= $step['type'];
                    }
                    $classString .= $step['function'].'()]';
                    $traceDisplay .= $classString;
                }
            }
            $traceDisplay .= '</ol>';

            $calleeInfo = '<nav></nav>'.$calleeInfo;
        }


        return "<footer>"
            .'<span class="_sage-popup-trigger" title="Open in new window">&rarr;</span> '
            ."{$calleeInfo}{$callingFunction}{$traceDisplay}"
            ."</footer></div>";
    }

    private static function _drawHeader(SageVariableData $varData)
    {
        $output = '';
        if ($varData->access !== null) {
            $output .= "<var>{$varData->access}</var> ";
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= "<dfn>"
                .SageHelper::decodeStr($varData->name)
                ."</dfn> ";
        }

        if ($varData->operator !== null) {
            $output .= $varData->operator." ";
        }

        if ($varData->type !== null) {
            // tyoe output is unescaped as it is set internally and contains links to user class
            $output .= "<var>{$varData->type}</var>";
        }

        if ($varData->size !== null) {
            $output .= "(".$varData->size.") ";
        }

        return $output;
    }


    /**
     * produces css and js required for display. May be called multiple times, will only produce output once per
     * pageload or until `-` or `@` modifier is used
     *
     * @return string
     */
    public static function init()
    {
        $baseDir = SAGE_DIR.'resources/compiled/';

        if (! is_readable($cssFile = $baseDir.Sage::$theme.'.css')) {
            $cssFile = $baseDir.'original.css';
        }

        return
            '<script class="-_sage-js">'.file_get_contents($baseDir.'sage.js').'</script>'
            .'<style class="-_sage-css">'.file_get_contents($cssFile)."</style>\n";
    }
}
