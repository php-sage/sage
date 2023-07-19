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
        $extendedPresent    = ! empty($allRepresentations);

        if ($extendedPresent) {
            $class = '_sage-parent';
            if (Sage::$expandedByDefault) {
                $class .= ' _sage-show';
            }
            $output .= '<dt class="' . $class . '">';
        } else {
            $output .= '<dt>';
        }

        if ($extendedPresent) {
            $output .= '<span class="_sage-popup-trigger" title="Open in new window">&rarr;</span><nav></nav>';
        }

        $output .= self::_drawHeader($varData) . $varData->value . '</dt>';

        if ($extendedPresent) {
            $output .= '<dd>';
        }

        // hm, this data structure sucks, but I can't come up with a better one:
        // the $varData->extendedValue holds organic representation of the inbuilt type.
        // the $varData->alternativeRepresentations are custom views into the data as prepared by various parsers.
        // We don't want to show the tab 'Contents' if $varData->extendedValue is the only tab
        if (count($allRepresentations) === 1 && ! empty($varData->extendedValue)) {
            $extendedValue = reset($allRepresentations);
            $output        .= self::decorateAlternativeView($extendedValue);
        } elseif ($extendedPresent) {
            $output .= "<ul class=\"_sage-tabs\">";

            $isFirst = true;
            foreach ($allRepresentations as $tabName => $_) {
                $active  = $isFirst ? ' class="_sage-active-tab"' : '';
                $isFirst = false;
                $output  .= "<li{$active}>" . SageHelper::esc($tabName) . '</li>';
            }

            $output .= '</ul><ul>';

            foreach ($allRepresentations as $alternative) {
                $output .= '<li>';
                $output .= self::decorateAlternativeView($alternative);
                $output .= '</li>';
            }

            $output .= '</ul>';
        }
        if ($extendedPresent) {
            $output .= '</dd>';
        }

        $output .= '</dl>';

        return $output;
    }

    public static function decorateTrace($traceData, $namesOnly = false)
    {
        // if we're dealing with a framework stack, lets verbosely display last few steps only, and not hang the browser
        $optimizeOutput = count($traceData) >= 10 && Sage::$maxLevels !== false;
        $maxLevels      = Sage::$maxLevels;

        $output = '<dl class="_sage-trace">';

        foreach ($traceData as $i => $step) {
            if ($optimizeOutput && $i > 2) {
                Sage::$maxLevels = 3;
            }

            $class = '_sage-parent';
            if (Sage::$expandedByDefault) {
                $class .= ' _sage-show';
            }

            if (empty($step['source']) && empty($step['args']) && empty($step['object'])) {
                $class .= ' _sage-childless';
            }

            $output .= '<dt class="' . $class . '">'
                . '<b>' . ($i + 1) . '</b> '
                . '<nav></nav>'
                . '<var>';

            if (isset($step['file'])) {
                $output .= SageHelper::ideLink($step['file'], $step['line']);
            } else {
                $output .= 'PHP internal call';
            }

            $output .= '</var> ';

            $output .= $step['function'];

            if (isset($step['args'])) {
                $output .= '(' . implode(', ', array_keys($step['args'])) . ')';
            }
            $output   .= '</dt><dd>';
            $firstTab = ' class="_sage-active-tab"';
            $output   .= '<ul class="_sage-tabs">';

            if (! empty($step['source'])) {
                $output   .= "<li{$firstTab}>Source</li>";
                $firstTab = '';
            }

            if (! $namesOnly && ! empty($step['args'])) {
                $output   .= "<li{$firstTab}>Arguments</li>";
                $firstTab = '';
            }

            if (! $namesOnly && ! empty($step['object'])) {
                SageParser::reset();

                $calleeDump = SageParser::process($step['object']);

                $output .= "<li{$firstTab}>Callee object [{$calleeDump->type}]</li>";
            }

            $output .= '</ul><ul>';

            if (! empty($step['source'])) {
                $output .= "<li><pre class=\"_sage-source\">{$step['source']}</pre></li>";
            }

            if (! $namesOnly && ! empty($step['args'])) {
                $output .= '<li>';
                foreach ($step['args'] as $k => $arg) {
                    SageParser::reset();
                    $output .= self::decorate(SageParser::process($arg, $k));
                }
                $output .= '</li>';
            }

            if (! $namesOnly && ! empty($step['object'])) {
                $output .= '<li>' . self::decorate($calleeDump) . '</li>';
            }

            $output .= '</ul></dd>';
        }

        $output .= '</dl>';

        Sage::$maxLevels = $maxLevels;

        return $output;
    }

    /**
     * called for each dump, opens the html tag
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
        $calleeInfo      = '';
        $traceDisplay    = '';
        if (isset($prevCaller['class'])) {
            $callingFunction = $prevCaller['class'];
        }
        if (isset($prevCaller['type'])) {
            $callingFunction .= $prevCaller['type'];
        }
        if (isset($prevCaller['function'])
            && ! in_array($prevCaller['function'], array('include', 'include_once', 'require', 'require_once'))
        ) {
            $callingFunction .= $prevCaller['function'] . '()';
        }
        $callingFunction and $callingFunction = " [{$callingFunction}]";

        if (isset($callee['file'])) {
            $calleeInfo .= 'Called from ' . SageHelper::ideLink($callee['file'], $callee['line']);
        }

        if (! empty($miniTrace)) {
            $traceDisplay = '<ol>';
            foreach ($miniTrace as $step) {
                $traceDisplay .= '<li>' . SageHelper::ideLink($step['file'], $step['line']); // closing tag not required
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
                    $classString  .= $step['function'] . '()]';
                    $traceDisplay .= $classString;
                }
            }
            $traceDisplay .= '</ol>';

            $calleeInfo = '<nav></nav>' . $calleeInfo;
        }

        $callingFunction .= ' @ ' . date('Y-m-d H:i:s');

        return '<footer>'
            . '<span class="_sage-popup-trigger" title="Open in new window">&rarr;</span> '
            . "{$calleeInfo}{$callingFunction}{$traceDisplay}"
            . '</footer></div>';
    }

    private static function _drawHeader(SageVariableData $varData)
    {
        $output = '';
        if ($varData->access !== null) {
            $output .= "<var>{$varData->access}</var> ";
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= '<dfn>' . SageHelper::esc($varData->name) . '</dfn> ';
        }

        if ($varData->operator !== null) {
            $output .= $varData->operator . ' ';
        }

        if ($varData->type !== null) {
            // tyoe output is unescaped as it is set internally and contains links to user class
            $output .= "<var>{$varData->type}</var> ";
        }

        if ($varData->size !== null) {
            $output .= '(' . $varData->size . ') ';
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
        $baseDir = SAGE_DIR . 'resources/compiled/';

        if (! is_readable($cssFile = $baseDir . Sage::$theme . '.css')) {
            $cssFile = $baseDir . 'original.css';
        }

        return
            '<script class="_sage-js">' . file_get_contents($baseDir . 'sage.js') . '</script>'
            . '<style class="_sage-css">' . file_get_contents($cssFile) . "</style>\n";
    }

    private static function decorateAlternativeView($alternative)
    {
        if (empty($alternative)) {
            return '';
        }

        $output = '';
        if (is_array($alternative)) {
            // we either get a prepared array of SageVariableData or a raw array of anything
            $parse = reset($alternative) instanceof SageVariableData
                ? $alternative
                : SageParser::process($alternative)->extendedValue; // convert into SageVariableData[]

            foreach ($parse as $v) {
                $output .= self::decorate($v);
            }
        } elseif (is_string($alternative)) {
            // the browser does not render leading new line in <pre>
            if ($alternative[0] === "\n" || $alternative[0] === "\r") {
                $alternative = "\n" . $alternative;
            }
            $output .= "<pre>{$alternative}</pre>";
        } elseif (isset($alternative)) {
            // error in custom parser, but don't let the user know
        }

        return $output;
    }
}
