<?php

/**
 * @internal
 */
class SageParsersClassName extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (
            ! SageHelper::isHtmlMode()
            || empty($variable)
            || ! is_string($variable)
            || strlen($variable) < 3
            || ! class_exists($variable)) {
            return false;
        }

        $reflector = new ReflectionClass($variable);
        if (!$reflector->isUserDefined()) {
            return false;
        }

        $varData->addTabToView(
            $variable,
            'Existing class',
            SageHelper::ideLink(
                $reflector->getFileName(),
                $reflector->getStartLine(),
                $reflector->getShortName()
            )
        );
    }
}