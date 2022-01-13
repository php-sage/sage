<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersSplObjectStorage extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (!SageHelper::isRichMode() || ! is_object($variable) || ! $variable instanceof SplObjectStorage) {
            return false;
        }

        /** @var $variable SplObjectStorage */

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

        $varData->addTabToView("Storage contents ({$count})", $arrayCopy);
    }
}