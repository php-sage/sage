<?php

/**
 * @internal
 */
class SageParsersObjectIterateable implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable, $varData)
    {
        if (! SageHelper::isRichMode()
            || ! SageHelper::php53orLater()
            || ! is_object($variable)
            || ! $variable instanceof Traversable
            || stripos($class = get_class($variable), 'zend') !== false // zf2 PDO wrapper does not play nice
            || strpos($class, 'DOMN') !== 0 // DOMNamedNodeMap, DOMNamedNodeMap
        ) {
            return false;
        }

        $arrayCopy = iterator_to_array($variable, true);

        $size = count($arrayCopy);

        $varData->addTabToView($variable, "Iterator contents ({$size})", $arrayCopy);
    }
}
