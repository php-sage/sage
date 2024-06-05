<?php

/**
 * @internal
 */
class SageParsersSplObjectStorage implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    /** @return false|void */
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

        $varData->addAlternativeView(
            new SageVariableExtendedView(
                SageVariableExtendedView::CONTENT_TYPE_DUMP,
                "Object contents ({$count})",
                $arrayCopy
            )
        );
    }
}
