<?php

/**
 * Alter {@see Sage::$enabledParsers} to enable/disable.
 *
 * @internal
 */
class SageParsersEloquentCollection implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (
            ! SageHelper::isRichMode()
            || ! is_a($variable, '\Illuminate\Database\Eloquent\Collection')
        ) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = 'Illuminate\Database\Eloquent\Collection';
        $size         = $variable->count();
        $result->size = $size;
        $result->hash = SageHelper::getObjectHash($variable);

        if ($variable->isNotEmpty()) {
            $result->subtype = '<' . get_class($variable->first()) . '>';

            $result->addTabView__DumpedRows(
                $variable->all()
            );

            if ($size > 3 && $size < 1000) {
                $result->addTabView__String(
                    $this->arrayToTable($variable),
                    'As table'
                );
            }
        }

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
        foreach ($variable as $rowIndex => $row) {
            // display strings in their full length
            //            self::$_placeFullStringInValue = true;

            $out .= '<tr>';
            $out .= '<td>' . $rowIndex . '</td>';

            foreach ($row->getAttributes() as $key => $value) {
                if (SageHelper::isKeyBlacklisted($key)) {
                    $processedVar = SageParsedVariable::erroneous(SageHelper::trans('key_blacklisted'));
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
        if ($varData->error !== null) {
            return '<td class="_sage-empty"><u>' . $varData->error . '</u></td>';
        }

        if ($varData->alternativeViews) {
            $decorator = new SageDecoratorsRich();

            // todo we don't care about non-essential representations here
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
