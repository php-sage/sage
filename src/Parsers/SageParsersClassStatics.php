<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersClassStatics implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        // todo breaks in PHP v8.4
        if (! SageHelper::isRichMode() || ! SageHelper::php53orLater() || ! is_object($variable)) {
            return null;
        }

        $staticProperties = (new ReflectionClass($variable))->getProperties(ReflectionProperty::IS_STATIC);
        if (count($staticProperties) === 0) {
            return null;
        }

        $tab = new SageParsedVariableContents(
            SageParsedVariableContents::RICH_ROWS,
            'Static class properties (' . count($staticProperties) . ')'
        );

        foreach ($staticProperties as $property) {
            if ($property->isProtected()) {
                $property->setAccessible(true);
                $access = 'protected';
            } elseif ($property->isPrivate()) {
                $property->setAccessible(true);
                $access = 'private';
            } else {
                $access = 'public';
            }

            if (method_exists($property, 'isInitialized') && ! $property->isInitialized($variable)) {
                $value  = null;
                $access .= ' [uninitialized]';
            } else {
                $value = $property->getValue($variable);
            }

            $name = '$' . $property->getName();
            $hash = SageHelper::getObjectHash($variable);
            if (array_key_exists($hash, SageParser::$objects) || $value === $variable) {
                $parsedProperty       = SageParsedVariable::erroneous("Recursion [{$hash}]");
                $parsedProperty->name = $name;
            } else {
                SageParser::$objects[$hash] = true;
                $parsedProperty             = SageParser::parse($value, SageHelper::esc($name));
            }

            $parsedProperty->access   = $access;
            $parsedProperty->operator = '::';

            $tab->addRow($parsedProperty);
        }

        $result = new SageParsedVariable();
        $result->addTabView($tab);

        return $result;
    }
}
