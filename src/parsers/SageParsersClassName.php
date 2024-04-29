<?php

/**
 * @internal
 */
class SageParsersClassName implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable, $varData)
    {
        if (
            Sage::enabled() === Sage::MODE_TEXT_ONLY
            || ! SageHelper::php53orLater()
            || empty($variable)
            || ! is_string($variable)
            || strlen($variable) < 3
        ) {
            return false;
        }

        try {
            if (! @class_exists($variable)) {
                return false;
            }
        } catch (Throwable $t) {
            return false;
        } catch (Exception $e) {
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
