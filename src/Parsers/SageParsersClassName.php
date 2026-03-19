<?php

/**
 * {@see SageInstance::enabledParsers} to enable/disable.
 *
 * @internal
 */
class SageParsersClassName implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (
            ! SageHelper::isHtmlMode()
            || ! SageHelper::php53orLater()
            || ! is_string($variable)
            || strlen($variable) < 3
        ) {
            return null;
        }

        try {
            if (! @class_exists($variable)) {
                return null;
            }
        } catch (Throwable $t) {
            return null;
        } catch (Exception $e) {
            return null;
        }

        $reflector = new ReflectionClass($variable);
        if (! $reflector->isUserDefined()) {
            return null;
        }

        $result = new SageParsedVariable();
        // produces link to userland class, eg.: "MyClass" string|class-name
        $result->subtype = '|class-name';
        $result->value = SageHelper::getIdeLink(
            $reflector->getFileName(),
            $reflector->getStartLine(),
            '"' . $variable . '"'
        );

        return $result;
    }
}
