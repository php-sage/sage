<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class Sage_Parsers_Json extends SageParser
{
    protected function _parse(&$variable, $originalVarData)
    {
        if (! SageHelper::php53()
            || ! is_string($variable)
            || ! isset($variable[0]) || ($variable[0] !== '{' && $variable[0] !== '[')
            || ($json = json_decode($variable, true)) === null
        ) {
            return false;
        }

        $val = (array)$json;
        if (empty($val)) {
            return false;
        }

        $this->value = SageParser::factory($val)->extendedValue;
        $this->type = 'JSON';
    }
}