<?php

/** @internal */
class SageDecoratorsRich implements SageDecoratorsInterface
{
    protected static $needsAssets = true;

    public function areAssetsNeeded()
    {
        return self::$needsAssets;
    }

    public function setAssetsNeeded($on)
    {
        self::$needsAssets = $on;
    }

    public function decorate(SageParsedVariable $varData, $skipHeader = false)
    {
        if ($varData->trace) {
            return $this->decorateTrace($varData->trace);
        }

        $output = '';
        if (! $skipHeader) {
            $output .= '<dl>';

            if ($varData->alternativeViews) {
                $class = '_sage-parent';
                if (Sage::$expandedByDefault) {
                    $class .= ' _sage-show';
                }
                $output .= '<dt class="' . $class . '">';
            } else {
                $output .= '<dt>';
            }

            if ($varData->alternativeViews) {
                $output .= '<span class="_sage-popup-trigger">&rarr;</span><nav title="[Ctrl+click] Expand all children"></nav>';
            }

            $output .= $this->drawHeader($varData);
        }

        $output .= '</dt>';

        if ($varData->alternativeViews) {
            if (! $skipHeader) {
                $output .= '<dd>';
            }

            $firstTab = reset($varData->alternativeViews);
            if (count($varData->alternativeViews) === 1 && $firstTab->name === '') {
                // don't need tabs!
                $output .= $this->drawAlternativeView($firstTab);
            } else {
                $output .= "<ul class=\"_sage-tabs\">";

                $isFirst = true;
                foreach ($varData->alternativeViews as $alternative) {
                    $active  = $isFirst ? ' class="_sage-active-tab"' : '';
                    $isFirst = false;
                    $name    = $alternative->name;
                    if (! $name) {
                        $name = 'Contents';
                    }
                    $output .= "<li{$active}>" . SageHelper::esc($name) . '</li>';
                }

                $output .= '</ul><ul>';

                foreach ($varData->alternativeViews as $alternative) {
                    $output .= '<li>';
                    $output .= $this->drawAlternativeView($alternative);
                    $output .= '</li>';
                }

                $output .= '</ul>';
            }
        }

        if (! $skipHeader) {
            if ($varData->alternativeViews) {
                $output .= '</dd>';
            }

            $output .= "</dl>\n";
        }

        return $output;
    }

    private function decorateTrace(SageTrace $trace)
    {
        $output                 = '<dl class="_sage-trace">';
        $blacklistedStepsInARow = 0;
        foreach ($trace->steps as $stepNumber => $step) {
            if (
                $step->isBlackListed
                && $stepNumber !== 0 // always display the first step (even if it's stripped down to just the file:line)
            ) {
                $blacklistedStepsInARow++;
                continue;
            }

            if ($blacklistedStepsInARow) {
                // if there were fewer than 5 blacklisted steps in a row, display them (they only contain file:line tho)
                if ($blacklistedStepsInARow <= 5) {
                    for ($j = $blacklistedStepsInARow; $j > 0; $j--) {
                        $output .= $this->drawTraceStep($stepNumber - $j, $trace->steps[$stepNumber - $j]);
                    }
                } else {
                    $output .= "<dt><b></b>[{$blacklistedStepsInARow} steps skipped]</dt>";
                }

                $blacklistedStepsInARow = 0;
            }

            $output .= $this->drawTraceStep($stepNumber, $step);
        }

        if ($blacklistedStepsInARow === count($trace->steps) - 1) {
            $drawThisMany = min(count($trace->steps), 10);
            for ($i = 1; $i < $drawThisMany; $i++) {
                $output .= $this->drawTraceStep($i, $trace->steps[$i]);
                $blacklistedStepsInARow--;
            }
        }

        if ($blacklistedStepsInARow > 1) {
            $output .= "<dt><b></b>[{$blacklistedStepsInARow} steps skipped]</dt>";
        }

        $output .= '</dl>';

        return $output;
    }

    /**
     * @param int $i
     * @param SageParsedTraceStep $step
     *
     * @return string
     */
    private function drawTraceStep($i, $step)
    {
        $isChildless = ! $step->sourceSnippet && ! $step->arguments && ! $step->object && ! $step->rawStep;

        $class = '';

        if ($step->isBlackListed) {
            $class .= ' _sage-blacklisted';
        } elseif ($isChildless) {
            $class .= ' _sage-childless';
        } else {
            $class .= '_sage-parent';

            if (Sage::$expandedByDefault) {
                $class .= ' _sage-show';
            }
        }

        $output = $class ? '<dt class="' . $class . '">' : '<dt>';
        $output .= '<b>' . ($i + 1) . '</b>';
        if (! $isChildless) {
            $output .= '<nav></nav>';
        }
        $output .= '<var>' . $step->fileLine . '</var> ';
        $output .= $step->functionName;
        $output .= '</dt>';

        if ($isChildless) {
            return $output;
        }

        $output        .= '<dd><ul class="_sage-tabs">';
        $firstTabClass = ' class="_sage-active-tab"';

        if ($step->sourceSnippet) {
            $output        .= "<li{$firstTabClass}>Source</li>";
            $firstTabClass = '';
        }

        if ($step->arguments) {
            $output        .= "<li{$firstTabClass}>Arguments</li>";
            $firstTabClass = '';
        }

        if ($step->object) {
            $output        .= "<li{$firstTabClass}>Callee object [{$step->object->type} <s>{$step->object->hash}</s>]</li>";
            $firstTabClass = '';
        }

        if ($step->rawStep) {
            $output        .= "<li{$firstTabClass}>Raw step data</li>";
            $firstTabClass = '';
        }

        $output .= '</ul><ul>';

        if ($step->sourceSnippet) {
            $output .= "<li><pre class=\"_sage-source\">{$step->sourceSnippet}</pre></li>";
        }

        if ($step->arguments) {
            $output .= '<li>';
            foreach ($step->arguments as $argument) {
                $output .= $this->decorate($argument);
            }
            $output .= '</li>';
        }

        if ($step->object) {
            $output .= '<li>' . $this->decorate($step->object) . '</li>';
        }

        if ($step->rawStep) {
            $output .= '<li>' . $this->decorate($step->rawStep, true) . '</li>';
        }

        $output .= '</ul></dd>';

        return $output;
    }

    /**
     * called for each dump, opens the html tag
     *
     * @return string
     */
    public function wrapStart()
    {
        return "<div class=\"_sage\">";
    }

    public function wrapEnd($caller)
    {
        if (! Sage::$displayCalledFrom) {
            return '</div>';
        }

        $callingFunction = '';
        $calledFrom      = '';
        $traceDisplay    = '';
        $userLandInvoker = $caller->getUserLandInvoker();
        if (isset($userLandInvoker['class'])) {
            $callingFunction = $userLandInvoker['class'];
        }
        if (isset($userLandInvoker['type'])) {
            $callingFunction .= $userLandInvoker['type'];
        }
        if (
            isset($userLandInvoker['function'])
            && ! in_array(
                $userLandInvoker['function'],
                array('include', 'include_once', 'require', 'require_once')
            )
        ) {
            $callingFunction .= $userLandInvoker['function'] . '()';
        }
        $callingFunction and $callingFunction = " [{$callingFunction}]";

        if ($caller->miniTrace) {
            foreach ($caller->miniTrace as $i => $step) {
                if ($i === 0) {
                    $traceDisplay = 'Called from '
                        . SageHelper::ideLink($caller->miniTrace[0]['file'], $caller->miniTrace[0]['line']);

                    continue;
                }

                if ($i === 1) {
                    $traceDisplay = '<nav></nav>' . $traceDisplay . '<ol>';
                }

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
            if ($i > 0) {
                $traceDisplay .= '</ol>';
            }
        }

        $callingFunction .= ' @ ' . date('Y-m-d H:i:s');

        return '<footer>'
            . '<span class="_sage-popup-trigger" title="Open in new window">&rarr;</span> '
            . "{$calledFrom}{$traceDisplay}{$callingFunction}"
            . '</footer></div>';
    }

    private function drawHeader(SageParsedVariable $varData)
    {
        $output = '';
        if ($varData->access !== null && $varData->access !== '') {
            $output .= "<var>{$varData->access}</var> ";
        }

        if ($varData->name !== null && $varData->name !== '') {
            $output .= '<dfn>' . SageHelper::esc($varData->name) . '</dfn> ';
        }

        if ($varData->operator !== null && $varData->operator !== '') {
            $output .= $varData->operator . ' ';
        }

        if ($varData->type !== null && $varData->type !== '') {
            $output .= '<var>' . SageHelper::esc($varData->type) . '</var>';
        }

        if ($varData->subtype !== null && $varData->subtype !== '') {
            $output .= '<s>' . SageHelper::esc($varData->subtype) . '</s>'; // todo separate style
        }

        $output .= ' ';

        if ($varData->hash !== null && $varData->hash !== '') {
            $output .= '<s>' . SageHelper::esc($varData->hash) . '</s> ';
        }

        if ($varData->size !== null && $varData->size !== '') {
            $output .= '(' . SageHelper::esc($varData->size) . ') ';
        }

        $output .= $varData->value;

        // todo
        if ($varData->error !== null && $varData->error !== '') {
            $output .= ' <u>' . $varData->error . '</u>';
        }

        return $output;
    }

    /**
     * Produces css and js required for display. May be called multiple times, will only produce output once per
     * pageload or per output capture group.
     *
     * @return string
     */
    public function init()
    {
        $baseDir = SAGE_DIR . 'src/resources/compiled/';

        if (! is_readable($cssFile = $baseDir . Sage::$theme . '.css')) {
            $cssFile = $baseDir . 'original.css';
        }

        return
            '<script class="_sage-js">' . file_get_contents($baseDir . 'sage.js') . '</script>'
            . '<style class="_sage-css">' . file_get_contents($cssFile) . "</style>\n";
    }

    /** @return string */
    private function drawAlternativeView(SageParsedVariableContents $variableContents)
    {
        switch ($variableContents->displayType) {
            case SageParsedVariableContents::STRING:
                return SageHelper::pre($variableContents->contents);
            case SageParsedVariableContents::PLAIN_TEXT_ROWS:
                $output       = '<pre>';
                $maxKeyLength = 0;
                foreach ($variableContents->contents as $row) {
                    $maxKeyLength = max($maxKeyLength, strlen($row['name']));
                }
                foreach ($variableContents->contents as $k => $row) {
                    $name   = $row['name'] ? $row['name'] : $k;
                    $output .= str_pad($name, $maxKeyLength) . ': ' . $row['value'] . "\n";
                }
                $output .= '</pre>';

                return $output;
            case SageParsedVariableContents::RICH_ROWS:
                $output = '';
                if ($variableContents->contents) {
                    foreach ($variableContents->contents as $row) {
                        $output .= $this->decorate($row);
                    }
                }

                return $output;
            case SageParsedVariableContents::DUMP:
                return $this->decorate($variableContents->contents);
            case SageParsedVariableContents::DUMP_WITHOUT_TOP_PARENT:
                return $this->decorate($variableContents->contents, true);
            case SageParsedVariableContents::TRACE:
                return $this->decorateTrace($variableContents->contents);
            default:
                throw new SageLogicException('unexpected variable content type: ' . $variableContents->displayType);
        }
    }
}
