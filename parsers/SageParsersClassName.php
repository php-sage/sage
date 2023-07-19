<?php

/**
 * @internal
 */
class SageParsersClassName extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (empty($variable)
            || ! is_string($variable)
            || strlen($variable) < 3
            || ! class_exists($variable)) {
            return false;
        }

        $reflector = new ReflectionClass($variable);
        if (! $reflector->isUserDefined()) {
            return false;
        }

        if (SageHelper::isRichMode()) {
            $varData->addTabToView(
                $variable,
                'Existing class',
                SageHelper::ideLink(
                    $reflector->getFileName(),
                    $reflector->getStartLine(),
                    $reflector->getShortName()
                )
            );
        } else {
            if (SageHelper::isHtmlMode()) {
                $varData->extendedValue =
                    array(
                        'Existing class' => SageHelper::ideLink(
                            $reflector->getFileName(),
                            $reflector->getStartLine(),
                            $reflector->getShortName()
                        )
                    );
            } else {
                $varData->extendedValue =
                    array('Existing class' => $reflector->getFileName() . ':' . $reflector->getStartLine());
            }
        }
    }
}
