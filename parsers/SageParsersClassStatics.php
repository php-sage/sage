<?php

/**
 * @internal
 */
class SageParsersClassStatics implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable, $varData)
    {
        if (! SageHelper::isRichMode() || ! SageHelper::php53orLater() || ! is_object($variable)) {
            return false;
        }

        $statics    = array();
        $class      = get_class($variable);
        $reflection = new ReflectionClass($class);

        // first show static values
        foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property) {
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

            $name   = '$' . $property->getName();
            $output = SageParser::process($value, SageHelper::esc($name));

            $output->access   = $access;
            $output->operator = '::';
            $statics[]        = $output;
        }

        if (empty($statics)) {
            return false;
        }

        $varData->addTabToView(
            $variable,
            'Static class properties (' . count($statics) . ')',
            $statics
        );
    }
}
