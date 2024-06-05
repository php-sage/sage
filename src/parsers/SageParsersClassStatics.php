<?php

/**
 * @internal
 */
class SageParsersClassStatics implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    /** @return false|void */
    public function parse(&$variable, $varData)
    {
        if (! SageHelper::isRichMode() || ! SageHelper::php53orLater() || ! is_object($variable)) {
            return false;
        }

        $staticProperties = (new ReflectionClass($variable))->getProperties(ReflectionProperty::IS_STATIC);
        if (count($staticProperties) === 0) {
            return false;
        }

        $result = new SageVariableExtendedView(
            SageVariableExtendedView::CONTENT_TYPE_RICH_ROWS,
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

            $name             = '$' . $property->getName();
            $output           = SageParser::process($value, SageHelper::esc($name));
            $output->access   = $access;
            $output->operator = '::';
            $result->addRow($output);
        }

        $varData->addAlternativeView($result);
    }
}
