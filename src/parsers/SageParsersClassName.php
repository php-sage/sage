<?php

/**
 * @internal
 */
class SageParsersClassName implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    /** @return false|void */
    public function parse(&$variable, $varData)
    {
        if (
            ! SageHelper::isHtmlMode()
            || ! SageHelper::php53orLater()
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

        // produces link to userland class, eg.: "MyClass" string|class-name
        $varData->type = new SageHtmlable(
            'string|'
            . SageHelper::ideLink($reflector->getFileName(), $reflector->getStartLine(), 'class-name')
        );
    }
}
