<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersEloquentCollection implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (
            ! is_a($variable, '\Illuminate\Database\Eloquent\Collection')
            || $variable->isEmpty()
        ) {
            return false;
        }

        $result       = new SageParsedVariable();
        $result->type = 'Illuminate\Database\Eloquent\Collection';
        $result->size = $variable->count();
        $result->hash = SageHelper::getObjectHash($variable);

        $output = new SageParsedVariableContents(
            SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS,
            'Collection of ' . get_class($variable->first()) . "({$variable->count()})"
        );

        $result->extendedView = new SageParsedVariableContents(
            SageParsedVariableContents::CONTENT_TYPE_STRING,
            '',
            $this->arrayToTable($variable)
        );

        $result->addAlternativeView($output);

        return $result;
    }

    private function arrayToTable($variable)
    {
        $out = '<table class="_sage-report">';

        $out .= '<thead><tr>';
        $out .= '<th>#</th>';
        foreach (array_keys($variable->first()->getAttributes()) as $key) {
            $out .= "<th>{$key}</th>";
        }
        $out .= '</tr></thead>';

        $out .= '<tbody>';
        foreach ($variable as $rowIndex => & $row) {
            // display strings in their full length
            //            self::$_placeFullStringInValue = true;

            $out .= '<tr>';
            $out .= '<td>' . ($rowIndex + 1) . '</td>';

            foreach ($row->getAttributes() as $key => $value) {
                if (SageHelper::isKeyBlacklisted($key)) {
                    $processedVar = SageParsedVariable::erroneous('Redacted');
                } else {
                    $processedVar = SageParser::parse($value);
                }

                $out .= self::_decorateCell($processedVar);
            }

            $out .= '</tr>';
        }

        $out .= '</tbody></table>';

        return new SageHtmlable($out);
        //        self::$_placeFullStringInValue = false;
    }

    private static function _decorateCell(SageParsedVariable $varData)
    {
        if ($varData->error) {
            return '<td class="_sage-empty"><u>' . $varData->error . '</u></td>';
        }

        if ($varData->extendedView) {
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
