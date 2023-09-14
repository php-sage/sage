<?php

/**
 * @internal
 */
class SageParsersSplObjectStorage implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable, $varData)
    {
        if (! SageHelper::isRichMode() || ! is_object($variable) || ! $variable instanceof SplObjectStorage) {
            return false;
        }

        $count = $variable->count();
        if ($count === 0) {
            return false;
        }

        $variable->rewind();
        $arrayCopy = array();
        while ($variable->valid()) {
            $arrayCopy[] = $variable->current();
            $variable->next();
        }

        $varData->addTabToView($variable, "Storage contents ({$count})", $arrayCopy);
    }
}
