<?php

/**
 * @internal
 */
class SageParsersInvisibleStringCharacters implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    /** @return false|void */
    public function parse(&$variable, $varData)
    {
        if (
            ! SageHelper::isRichMode()
            || ! is_string($variable)
            || $variable === preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $variable)
        ) {
            return false;
        }

        $varData->addAlternativeView(
            new SageVariableExtendedView(
                SageVariableExtendedView::CONTENT_TYPE_STRING,
                'Hidden characters escaped',
                $variable
            )
        );

        $varData->extendedView = new SageVariableExtendedView(
            SageVariableExtendedView::CONTENT_TYPE_STRING,
            'Contents',
            SageHelper::esc($variable, false)
        );
    }
}
