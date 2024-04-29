<?php

/**
 * @internal
 */
class SageParsersEloquent implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable, $varData)
    {
        if (! SageHelper::php53orLater() || ! is_a($variable, '\Illuminate\Database\Eloquent\Model')) {
            return false;
        }

        $reflection = new ReflectionObject($variable);

        $attrReflecion = $reflection->getProperty('attributes');
        $attrReflecion->setAccessible(true);
        $attributes = $attrReflecion->getValue($variable);

        $reference = '`' . $variable->getConnection()->getDatabaseName() . '`.`' . $variable->getTable() . '`';

        $varData->size = count($attributes);
        if (SageHelper::isRichMode()) {
            $varData->type = $reflection->getName();
            $varData->addTabToView($variable, 'data from ' . $reference, $attributes);
        } else {
            $varData->type          = $reflection->getName() . '; ' . $reference . ' row data:';
            $varData->extendedValue = SageParser::alternativesParse($variable, $attributes);
        }
    }
}
