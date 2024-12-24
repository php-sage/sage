<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersInvisibleStringCharacters implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (
            ! SageHelper::isRichMode()
            || ! is_string($variable)
            || $variable === preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $variable)
        ) {
            return null;
        }

        $result = new SageParsedVariable();
        $result->addAlternativeView(
            new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_STRING,
                'Hidden characters escaped',
                $variable
            )
        );

        $result->extendedView = new SageParsedVariableContents(
            SageParsedVariableContents::CONTENT_TYPE_STRING,
            'Contents',
            SageHelper::esc($variable, false)
        );

        return $result;
    }
}
