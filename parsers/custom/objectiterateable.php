<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class Sage_Parsers_objectIterateable extends SageParser
{
    protected function _parse(&$variable)
    {
        if (! SAGE_PHP53
            || ! is_object($variable)
            || ! $variable instanceof Traversable
            || stripos(get_class($variable), 'zend') !== false // zf2 PDO wrapper does not play nice
        ) {
            return false;
        }


        $arrayCopy = iterator_to_array($variable, true);

        if ($arrayCopy === false) {
            return false;
        }

        $this->value = SageParser::factory($arrayCopy)->extendedValue;
        $this->type = 'Iterator contents';
        $this->size = count($arrayCopy);
    }
}