<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersTimestamp implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (! $this->_fits($variable)) {
            return null;
        }

        $var = strlen($variable) === 13 ? substr($variable, 0, -3) : $variable;

        $result = new SageParsedVariable();
        $result->addAlternativeView(
            new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_STRING,
                'Timestamp',
                @date('Y-m-d H:i:s', $var) // avoid dreaded "Timezone must be set" error
            )
        );

        return $result;
    }

    private function _fits($variable)
    {
        if (! SageHelper::isRichMode()) {
            return false;
        }

        if (! is_string($variable) && ! is_int($variable)) {
            return false;
        }

        $len = strlen((int)$variable);

        return
            (
                $len === 9 || $len === 10 // a little naive
                || ($len === 13 && substr($variable, -3) === '000') // also handles javascript micro timestamps
            )
            && ((string)(int)$variable == $variable);
    }
}
