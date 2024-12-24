<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersIterable implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (! SageHelper::isRichMode()
            || ! SageHelper::php53orLater()
            || ! is_object($variable)
            || ! $variable instanceof Traversable
            || stripos($class = get_class($variable), 'zend') !== false // zf2 PDO wrapper does not play nice
            || strpos($class, 'DOMN') !== 0 // DOMNamedNodeMap, DOMNamedNodeMap
        ) {
            return null;
        }

        $arrayCopy = iterator_to_array($variable, true);

        $size = count($arrayCopy);

        $result = new SageParsedVariable();

        $result->addAlternativeView(
            new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_DUMP,
                "Iterator contents ({$size})",
                $arrayCopy
            )
        );

        return $result;
    }
}
