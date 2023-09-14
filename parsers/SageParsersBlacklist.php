<?php

/**
 * @internal
 */
class SageParsersBlacklist implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable, $varData)
    {
        // allow explicit, first level parameters
        if (SageParser::$_level === 1) {
            return false;
        }

        if (! is_object($variable)) {
            return false;
        }

        $className = get_class($variable);
        $match     = false;
        foreach (Sage::$classNameBlacklist as $item) {
            if (preg_match($item, $className)) {
                $match = true;
                break;
            }
        }

        if (! $match) {
            return false;
        }

        $varData->type = get_class($variable) . ' [skipped]';
    }
}
