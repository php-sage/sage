<?php

/**
 * Alter {@see Sage::$enabledParsers} to enable/disable.
 *
 * @internal
 */
class SageParsersEloquentExpression implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (
            ! SageHelper::php53orLater()
            || ! is_a($variable, '\Illuminate\Database\Query\Expression') // a.k.a. DB::raw()
        ) {
            return null;
        }

        $property = new ReflectionProperty('\Illuminate\Database\Query\Expression', 'value');
        $property->setAccessible(true);
        $value = $property->getValue($variable);

        $result = new SageParsedVariable();
        $result->addTabView__String(
            SageSqlFormatter::format($value, false),
            'Formatted expression'
        );

        return $result;
    }
}
