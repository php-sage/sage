<?php

/**
 * @internal
 */
class SageParsersXml implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    /** @return false|void */
    public function parse(&$variable, $varData)
    {
        return false; // this is an unsolved problem at humanity level
        if (! SageHelper::isRichMode()) {
            return false;
        }

        if (is_string($variable) && substr($variable, 0, 5) === '<?xml') {
            try {
                $e   = libxml_use_internal_errors(true);
                $xml = simplexml_load_string($variable);
                libxml_use_internal_errors($e);
            } catch (Exception $e) {
                return false;
            }

            if (empty($xml)) {
                return false;
            }
        } else {
            return false;
        }

        // todo issue #1
        $varData->addAlternativeView(
            new SageVariableExtendedView(
                SageVariableExtendedView::CONTENT_TYPE_DUMP,
                'XML',
                $xml
            )
        );
    }
}
