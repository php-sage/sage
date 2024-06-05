<?php

/**
 * @internal
 */
class SageParsersJson implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    /** @return false|void */
    public function parse(&$variable, $varData)
    {
        if (! SageHelper::isRichMode()
            || ! SageHelper::php53orLater()
            || ! is_string($variable)
            || ! isset($variable[0])
            || ($variable[0] !== '{' && $variable[0] !== '[')
            || ($json = json_decode($variable, true)) === null
        ) {
            return false;
        }

        $val = (array)$json;

        if (! $val) {
            return false;
        }

        $varData->addAlternativeView(new SageVariableExtendedView(SageVariableExtendedView::CONTENT_TYPE_DUMP, 'Json', $val));
    }
}
