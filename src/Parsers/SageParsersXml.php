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

            $val = $dom->saveXML();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'XML declaration allowed only at the start of the document') !== false) {
                try {
                    $val = '';
                    foreach (explode('<?xml', $variable) as $part) {
                        if (trim($part) === '') {
                            continue;
                        }

                        if ($dom->loadXML('<?xml ' . $part) === false) {
                            return null;
                        }

                        $val .= "\n\n" . $dom->saveXML();
                    }
                } catch (Exception $e) {
                    return null;
                }
            }
        }

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
