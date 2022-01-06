<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class Sage_Parsers_ClassStatics extends SageParser
{
    protected function _parse(&$variable, $originalVarData)
    {
        if (! SageHelper::php53() || ! is_object($variable)) {
            return false;
        }

        $extendedValue = array();

        $reflection = new ReflectionClass($variable);
        // first show static values
        foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property) {
            if ($property->isPrivate()) {
                if (! method_exists($property, 'setAccessible')) {
                    break;
                }
                $property->setAccessible(true);
                $access = "private";
            } elseif ($property->isProtected()) {
                $property->setAccessible(true);
                $access = "protected";
            } else {
                $access = 'public';
            }

            $_ = $property->getValue();
            $output = SageParser::factory($_, '$'.$property->getName());

            $output->access = $access;
            $output->operator = '::';
            $extendedValue[] = $output;
        }

        foreach ($reflection->getConstants() as $constant => $val) {
            $output = SageParser::factory($val, $constant);

            $output->access = 'constant';
            $output->operator = '::';
            $extendedValue[] = $output;
        }

        if (empty($extendedValue)) {
            return false;
        }

        $this->value = $extendedValue;
        $this->type = 'Static class properties';
        $this->size = count($extendedValue);
    }
}