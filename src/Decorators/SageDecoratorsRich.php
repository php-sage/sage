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

    public function decorate(SageParsedVariable $varData)
    {
        if ($varData->traceSteps) {
            return $this->decorateTrace($varData);
        }

        $output = '<dl>';

        $allRepresentations = $varData->getAllRepresentations();
        $isExtendedPresent  = count($allRepresentations) !== 0;

        if ($isExtendedPresent) {
            $class = '_sage-parent';
            if (Sage::$expandedByDefault) {
                $class .= ' _sage-show';
            }
            $output .= '<dt class="' . $class . '">';
        } else {
            $output .= '<dt>';
        }

        if ($isExtendedPresent) {
            $output .= '<span class="_sage-popup-trigger">&rarr;</span><nav></nav>';
        }

        $output .= $this->drawHeader($varData) . $varData->value . '</dt>';

        if ($isExtendedPresent) {
            $output .= '<dd>';
        }
        if ($isExtendedPresent) {
            if (count($allRepresentations) === 1 && $varData->extendedView !== null) {
                // don't need tabs!
                $output .= $this->drawAlternativeView(reset($allRepresentations));
            } else {
                $output .= "<ul class=\"_sage-tabs\">";

                $isFirst = true;
                foreach ($allRepresentations as $tab) {
                    $active  = $isFirst ? ' class="_sage-active-tab"' : '';
                    $isFirst = false;
                    $output  .= "<li{$active}>" . SageHelper::esc($tab->name) . '</li>';
                }

                $output .= '</ul><ul>';

                foreach ($allRepresentations as $alternative) {
                    $output .= '<li>';
                    $output .= $this->drawAlternativeView($alternative);
                    $output .= '</li>';
                }

                $output .= '</ul>';
            }
        }
        if ($isExtendedPresent) {
            $output .= '</dd>';
        }

        $output .= "</dl>\n";

        return $output;
    }

    public function decorateTrace(SageParsedVariable $trace, $pathsOnly = false)
    {
        $output    = '<dl class="_sage-trace">';
        $traceData = $trace->traceSteps;

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
                        $output .= $this->drawTraceStep($stepNumber - $j, $traceData[$stepNumber - $j], $pathsOnly);
                    }
                } else {
                    $output .= "<dt><b></b>[{$blacklistedStepsInARow} steps skipped]</dt>";
                }

                $blacklistedStepsInARow = 0;
            }

            $output .= $this->drawTraceStep($stepNumber, $step, $pathsOnly);
        }

        if ($blacklistedStepsInARow > 1) {
            $output .= "<dt><b></b>[{$blacklistedStepsInARow} steps skipped]</dt>";
        }

        $output .= '</dl>';

        return $output;
    }

    private function drawTraceStep($i, $step, $pathsOnly)
    {
        $isChildless = ! $step->sourceSnippet && ! $step->arguments && ! $step->object;

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

        if (! $pathsOnly && $step->arguments) {
            $output        .= "<li{$firstTabClass}>Arguments</li>";
            $firstTabClass = '';
        }

        if (! $pathsOnly && $step->object) {
            $output .= "<li{$firstTabClass}>Callee object [{$step->object->type}]</li>";
        }

        $output .= '</ul><ul>';

        if ($step->sourceSnippet) {
            $output .= "<li><pre class=\"_sage-source\">{$step->sourceSnippet}</pre></li>";
        }

        if (! $pathsOnly && $step->arguments) {
            $output .= '<li>';
            foreach ($step->arguments as $argument) {
                $output .= $this->decorate($argument);
            }
            $output .= '</li>';
        }

        if (! $pathsOnly && $step->object) {
            $output .= '<li>' . $this->decorate($step->object) . '</li>';
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
            // type output is unescaped as it is set internally and contains links to user class
            $output .= "<var>{$varData->type}</var> ";
        }

        if ($varData->size !== null) {
            $output .= '(' . $varData->size . ') ';
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

    private function drawAlternativeView(SageParsedVariableContents $variableContents)
    {
        switch ($variableContents->displayType) {
            case SageParsedVariableContents::CONTENT_TYPE_STRING:
                return SageHelper::pre($variableContents->contents);
            case SageParsedVariableContents::CONTENT_TYPE_PLAIN_TEXT_ROWS:
                $output       = '<pre>';
                $maxKeyLength = 0;
                foreach ($variableContents->contents as $key => $value) {
                    $maxKeyLength = max($maxKeyLength, strlen($value));
                }
                foreach ($variableContents->contents as $key => $value) {
                    $output .= str_pad($key, $maxKeyLength, ' ') . ': ' . $value;
                }
                $output .= '</pre>';

                return $output;
            case SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS:
                $output = '';
                foreach ($variableContents->contents as $row) {
                    $output .= $this->decorate($row);
                }

                return $output;
            case SageParsedVariableContents::CONTENT_TYPE_DUMP:
                return $this->decorate(SageParser::parse($variableContents->contents));
            default:
                throw new SageLogicException('unexpected variable content type');
        }
    }
}
