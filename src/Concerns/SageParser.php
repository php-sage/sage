<?php

/** @internal */
class SageParser
{
    /** @var int */
    public static $level = 0;

    /** @var array<string, true> used to prevent recursion */
    public static $objects = array();

    /**
     * @param mixed $variable copy of user-provided variable
     * @param string|SageHtmlable $name todo escape by default, expect sageHtmlable otherwise
     *
     * @return SageParsedVariable
     */
    public static function parse(&$variable, $name = null)
    {
        if ($variable instanceof SageParsedVariable) {
            throw new SageLogicException('This is already parsed!', $variable);
        }


        // save internal data to revert after dumping to properly handle recursions
        $level   = SageParser::$level++;
        $objects = SageParser::$objects;

        if (is_object($variable)) {
            $hash = SageHelper::getObjectHash($variable);

            if (isset(SageParser::$objects[$hash])) {
                $sageParsedVariable       = SageParsedVariable::erroneous("Recursion [{$hash}]");
                $sageParsedVariable->name = $name;
                $sageParsedVariable->type = get_class($variable);

                return $sageParsedVariable;
            }

            SageParser::$objects[$hash] = true;
        }

        $parser = new self();
        $result = $parser->doParse($variable, $name);

        SageParser::$level   = $level;
        SageParser::$objects = $objects;

        return $result;
    }

    private function doParse(&$variable, $name)
    {
        $result       = new SageParsedVariable();
        $result->name = $name;

        $parseAsNative = true;

        foreach (Sage::settings()->enabledParsers as $parserClass => $enabled) {
            if (! $enabled) {
                continue;
            }

            /** @var SageCustomParserInterface $parser */
            $parser      = new $parserClass();
            $parseResult = $parser->parse($variable);

            if ($parseResult) {
                $result->mergeFrom($parseResult);

                if ($parser->replacesAllOtherParsers()) {
                    $parseAsNative = false;
                    break;
                }
            }
        }

        if ($parseAsNative) {
            $parsed = SageNativeTypesParser::parse($variable);
            // Native type parser returning null means "stop processing further": e.g. recursion
            if ($parsed) {
                $result = $parsed->mergeFrom($result);
            } else {
                self::$level--;
            }
        }

        return $result;
    }
}
