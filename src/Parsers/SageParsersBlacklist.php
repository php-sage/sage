<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersBlacklist implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        // allow explicit, first level parameters
        if (
            SageParser::$level === 1
            || (SageHelper::isRichMode() && SageParser::$level === 2)
        ) {
            return null;
        }

        if (! is_object($variable)) {
            return null;
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
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = $className; // todo ..or disable on the fly

        $result->error = '[skipped, dump object in top level to see contents]';

        return $result;
    }
}
