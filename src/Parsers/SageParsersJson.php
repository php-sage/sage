<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersJson implements SageCustomParserInterface
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
            || ! isset($variable[0])
            || ($variable[0] !== '{' && $variable[0] !== '[')
            || ($json = json_decode($variable, true)) === null
        ) {
            return null;
        }

        $val = (array)$json;

        if (! $val) {
            return null;
        }

        $result = new SageParsedVariable();
        $result->addTabView(
            new SageParsedVariableContents(SageParsedVariableContents::DUMP, 'Json', $val)
        );

        return $result;
    }
}
