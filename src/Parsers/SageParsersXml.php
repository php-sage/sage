<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersXml implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (! SageHelper::isRichMode()
            || ! SageHelper::php53orLater()
            || ! is_string($variable)
            || ! $variable
            || ($variable[0] !== '<')
            || ! class_exists('DOMDocument')
        ) {
            return null;
        }

        $dom                      = new DOMDocument();
        $dom->preserveWhiteSpace  = false;
        $dom->formatOutput        = true;
        $dom->strictErrorChecking = false;

        try {
            if ($dom->loadXML($variable) === false) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }

        $val = $dom->saveXML();

        if (! $val || $val === $variable) {
            return null;
        }

        $result = new SageParsedVariable();
        $result->addExtended(
            new SageParsedVariableContents(SageParsedVariableContents::STRING, 'Formatted XML', $val)
        );

        return $result;
    }
}
