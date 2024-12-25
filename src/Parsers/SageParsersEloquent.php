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
            return false;
        }

        $isModel = is_a($variable, '\Illuminate\Database\Eloquent\Model');

        if (
            ! $isModel
            && ! is_a($variable, '\Illuminate\Contracts\Database\Query\Builder')
        ) {
            return false;
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
            SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS,
            'Retrieved DB rows from ' . $reference
        );
        foreach ($attributes as $key => $value) {
            $attributesDump->addRow($value, $key);
        }

        $result = new SageParsedVariable();

        $result->size = count($attributes);

        if (SageHelper::isRichMode()) {
            $result->type = get_class($variable);
            $result->addAlternativeView($attributesDump);

            foreach ($variable->relationsToArray() as $relationName => $attributes) {
                $result->addAlternativeView(
                    new SageParsedVariableContents(
                        SageParsedVariableContents::CONTENT_TYPE_DUMP,
                        'Loaded relation: ' . $relationName,
                        $attributes
                    )
                );
            }

            return $result;
        }

        $result->type         = get_class($variable) . '; ' . $reference . ' row data:';
        $result->extendedView = $attributesDump;

        return $result;
    }

    /**
     * @param $variable Illuminate\Contracts\Database\Query\Builder
     */
    private function parseQuery(&$variable)
    {
        $result = new SageParsedVariable();

        $result->type = get_class($variable);
        $queryDump    = new SageParsedVariableContents(
            SageParsedVariableContents::CONTENT_TYPE_DUMP,
            'Raw query',
            SageSqlFormatter::format($variable->toRawSql(), false)
        );

        if (SageHelper::isRichMode()) {
            $result->addAlternativeView($queryDump);

            return $result;
        }

        $result->extendedView = $queryDump;

        return $result;
    }
}
