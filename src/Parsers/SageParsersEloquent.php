<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersEloquent implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (! SageHelper::php53orLater()) {
            return null;
        }

        $isModel = is_a($variable, '\Illuminate\Database\Eloquent\Model');

        if (
            ! $isModel
            && ! is_a($variable, '\Illuminate\Contracts\Database\Query\Builder')
        ) {
            return null;
        }

        if ($isModel) {
            return $this->parseModel($variable);
        }

        return $this->parseQuery($variable);
    }

    /**
     * @param $variable Illuminate\Database\Eloquent\Model
     */
    public function parseModel(&$variable)
    {
        $attributes = $variable->getAttributes();

        $reference = '`' . $variable->getConnection()->getDatabaseName() . '`.`' . $variable->getTable() . '`';

        $attributesDump = new SageParsedVariableContents(
            SageParsedVariableContents::RICH_ROWS,
            'Retrieved DB rows from ' . $reference
        );
        foreach ($attributes as $key => $value) {
            // the $value (from $model->getAttributes()) is before casting/mutating, we want the processed one.
            $attributesDump->addRow($variable->$key, $key, '->');
        }

        $result = new SageParsedVariable();

        $result->size = count($attributes);

        if (SageHelper::isRichMode()) {
            $result->type = get_class($variable);
            $result->addExtended($attributesDump);

            foreach ($variable->relationsToArray() as $relationName => $relationAttributes) {
                $result->addExtended(
                    new SageParsedVariableContents(
                        SageParsedVariableContents::DUMP,
                        'Loaded relation: ' . $relationName,
                        $relationAttributes
                    )
                );
            }

            return $result;
        }

        $result->type = get_class($variable) . '; ' . $reference . ' row data:';
        $result->addExtended($attributesDump);

        return $result;
    }

    /**
     * @param $variable Illuminate\Contracts\Database\Query\Builder
     */
    private function parseQuery(&$variable)
    {
        $result       = new SageParsedVariable();
        $result->type = get_class($variable);
        $result->hash = SageHelper::getObjectHash($variable);

        $query        = $variable->toRawSql();
        $result->size = strlen($query);
        $result->addExtendedString(
            SageSqlFormatter::format($variable->toRawSql(), false),
            'Raw query'
        );

        return $result;
    }
}
