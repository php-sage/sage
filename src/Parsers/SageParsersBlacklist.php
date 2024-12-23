<?php

/** @internal */
class SageParsersBlacklist implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        // allow explicit, first level parameters, also it's immediate children
        if (SageParser::$level <= 2) {
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
        $result->type = $className . ' [skipped, dump object in top level to see contents]'; // todo ..or disable on the fly

        return $result;
    }
}
