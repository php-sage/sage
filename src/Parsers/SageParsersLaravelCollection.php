<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */

// todo it's a quick copy from eloquent collection, duplicates code, always displays as a table!
class SageParsersLaravelCollection implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (
            ! SageHelper::isRichMode()
            || ! is_a($variable, '\Illuminate\Support\Collection')
        ) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = 'Illuminate\Support\Collection';
        $result->size = $variable->count();
        $result->hash = SageHelper::getObjectHash($variable);

        if ($variable->isNotEmpty()) {
            $result->subtype = '<' . SageHelper::getDebugType($variable->first()) . '>';

            $result->extendedView = new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_STRING,
                null,
                $this->arrayToTable($variable)
            );
        }

        return $result;
    }

    private function arrayToTable($variable)
    {
        $out = '<table class="_sage-report">';

        $out .= '<thead><tr>';
        $out .= '<th>#</th>';
        foreach (array_keys((array) $variable->first()) as $key) {
            $out .= "<th>{$key}</th>";
        }
        $out .= '</tr></thead>';

        $out .= '<tbody>';
        foreach ($variable as $i => & $row) {
            // display strings in their full length (todo)
            //            self::$_placeFullStringInValue = true;

            $out      .= '<tr>';
            $rowIndex = is_int($i) ? $i + 1 : SageHelper::esc($i);
            $out      .= '<td>' . $rowIndex . '</td>';

            if (is_array($row) || $row instanceof Traversable) {
                foreach ($row as $key => $value) {
                    if (SageHelper::isKeyBlacklisted($key)) {
                        $processedVar = SageParsedVariable::erroneous('Redacted');
                    } else {
                        $processedVar = SageParser::parse($value);
                    }

                    $out .= self::_decorateCell($processedVar);
                }
            } else {
                $processedVar = SageParser::parse($row);
                $out          .= self::_decorateCell($processedVar);
            }

            $out .= '</tr>';
        }

        $out .= '</tbody></table>';

        return new SageHtmlable($out);
        //        self::$_placeFullStringInValue = false;
    }

    private static function _decorateCell(SageParsedVariable $varData)
    {
        if ($varData->error !== null) {
            return '<td class="_sage-empty"><u>' . $varData->error . '</u></td>';
        }

        if (isset($varData->extendedView)) {
            $decorator = new SageDecoratorsRich();

            return '<td>' . $decorator->decorate($varData) . '</td>';
        }

        $output = '<td';

        if ($varData->value !== null) {
            $output .= ' title="' . $varData->type;

            if ($varData->size !== null) {
                $output .= ' (' . $varData->size . ')';
            }

            $output .= '">' . $varData->value;
        } else {
            $output .= '>';

            if ($varData->type !== 'NULL') {
                $output .= '<u>' . $varData->type;

                if ($varData->size !== null) {
                    $output .= '(' . $varData->size . ')';
                }

                $output .= '</u>';
            } else {
                $output .= '<u>NULL</u>';
            }
        }

        return $output . '</td>';
    }
}
