<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersXml extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
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

        //        dd($xml);

        $varData->addTabToView($variable, 'XML', SageParser::alternativesParse($variable, $xml));
    }
}
