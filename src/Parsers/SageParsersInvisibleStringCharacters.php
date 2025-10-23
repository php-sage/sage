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
        $result->addTabView__String(
            SageHelper::esc($variable, false),
            'Original (hidden characters not escaped)'
        );

        return $result;
    }
}
