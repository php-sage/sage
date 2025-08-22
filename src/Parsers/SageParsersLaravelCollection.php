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
        if (! is_a($variable, '\Illuminate\Support\Collection')) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = 'Illuminate\Support\Collection';
        $result->size = $variable->count();
        $result->hash = SageHelper::getObjectHash($variable);

        if ($variable->isNotEmpty()) {
            // todo not necessary all items will be same as the first one
            $result->subtype = '<' . SageHelper::getDebugType($variable->first()) . '>';
            $result->addTabView__UnwrappedDump($variable->toArray());
        }

        return $result;
    }
}
