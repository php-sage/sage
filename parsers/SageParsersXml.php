<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersXml extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        try {
            if (! SageHelper::isRichMode()) {
                return false;
            }

            if (is_string($variable) && substr($variable, 0, 5) === '<?xml') {
                $e = libxml_use_internal_errors(true);
                $xml = simplexml_load_string($variable);
                libxml_use_internal_errors($e);
                if (empty($xml)) {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        $varData->addTabToView('XML', @date('Y-m-d H:i:s', $xml));
    }
}
