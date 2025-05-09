<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersSplObjectStorage implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (! SageHelper::isRichMode() || ! is_object($variable) || ! $variable instanceof SplObjectStorage) {
            return null;
        }

        $count = $variable->count();
        if ($count === 0) {
            return null;
        }

        $variable->rewind();
        $arrayCopy = array();
        while ($variable->valid()) {
            $arrayCopy[] = $variable->current();
            $variable->next();
        }

        $result = new SageParsedVariable();
        $result->addExtended(
            new SageParsedVariableContents(
                SageParsedVariableContents::DUMP,
                "Object contents ({$count})",
                $arrayCopy
            )
        );

        return $result;
    }
}
