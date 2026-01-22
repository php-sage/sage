<?php

/**
 * {@see SageSettings::enabledParsers} to enable/disable.
 *
 * @internal
 */
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
        foreach (Sage::settings()->classNameBlacklist as $item) {
            if (preg_match($item, $className)) {
                $match = true;
                break;
            }
        }

        if (! $match) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = $className;
        $result->hash = SageHelper::getObjectHash($variable);

        $result->error = '[skipped, dump object in top level to see contents]';  // todo ..or disable on the fly

        return $result;
    }
}
