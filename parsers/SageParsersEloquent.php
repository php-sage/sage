<?php

/**
 * @internal
 */
class SageParsersEloquent extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (! SageHelper::php53orLater() || ! $variable instanceof Illuminate\Database\Eloquent\Model) {
            return false;
        }

        $reflection = new ReflectionObject($variable);
        $p          = $reflection->getProperty('attributes');
        $p->setAccessible(true);

        if (SageHelper::isRichMode()) {
            $varData->addTabToView($variable, 'DB data', $p->getValue($variable));
        } else {
            $varData->extendedValue = SageParser::alternativesParse($variable, $p->getValue($variable));
        }

        return true;
    }
}
