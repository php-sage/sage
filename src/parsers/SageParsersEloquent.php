<?php

/**
 * @internal
 */
class SageParsersEloquent implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    /** @return false|void */
    public function parse(&$variable, $varData)
    {
        if (
            ! SageHelper::php53orLater()
            || ! is_a($variable, '\Illuminate\Database\Eloquent\Model')
        ) {
            return false;
        }

        $reflection    = new ReflectionObject($variable);
        $attrReflecion = $reflection->getProperty('attributes'); // todo is this really the best way??
        $attrReflecion->setAccessible(true);
        $attributes = $attrReflecion->getValue($variable);
        $reference  = '`' . $variable->getConnection()->getDatabaseName() . '`.`' . $variable->getTable() . '`';

        $attributesDump = new SageVariableExtendedView(
            SageVariableExtendedView::CONTENT_TYPE_DUMP,
            'Retrieved DB data from: ' . $reference,
            $attributes
        );

        $varData->size = count($attributes);
        $varData->type = $reflection->getName();

        if (SageHelper::isRichMode()) {
            $varData->addAlternativeView($attributesDump);

            // todo add relations
            return;
        }

        $varData->type         = $reflection->getName() . '; ' . $reference . ' row data:';
        $varData->extendedView = $attributes;
    }
}
