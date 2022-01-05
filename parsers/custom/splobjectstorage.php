<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class Sage_Parsers_SplObjectStorage extends SageParser
{
    protected function _parse(&$variable)
    {
        if (! is_object($variable) || ! $variable instanceof SplObjectStorage) {
            return false;
        }

        /** @var $variable SplObjectStorage */

        $count = $variable->count();
        if ($count === 0) {
            return false;
        }

        $variable->rewind();
        while ($variable->valid()) {
            $current = $variable->current();
            $this->value[] = SageParser::factory($current);
            $variable->next();
        }

        $this->type = 'Storage contents';
        $this->size = $count;
    }
}