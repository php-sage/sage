<?php

/** @internal */
class SageParser
{
    /** @var int */
    public static $level = 0;

    /** @var array<string, true> used to prevent recursion */
    public static $objects = array();

    /**
     * @var array keep parsers from looping todo?
     */
    // private static $parsingAlternative = array();

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

        // save internal data to revert after dumping to properly handle recursions etc
        $level   = self::$level++;
        $objects = self::$objects;

        $parser = new self();
        $result = $parser->doParse($variable, $name);

        self::$level   = $level;
        self::$objects = $objects;

        return $result;
    }

    private function doParse(&$variable, $name)
    {
        $result       = new SageParsedVariable();
        $result->name = $name;

        $parseAsNative = true;

        $objects = self::$objects;
        foreach (Sage::settings()->enabledParsers as $parserClass => $enabled) {
            if (! $enabled) {
                continue;
            }

            // if (array_key_exists($parserClass, self::$parsingAlternative)) {
            //     continue;
            // }
            // self::$parsingAlternative[$parserClass] = true;

            /** @var SageCustomParserInterface $parser */
            $parser      = new $parserClass();
            $parseResult = $parser->parse($variable);

            // unset(self::$parsingAlternative[$parserClass]);
            if ($parseResult) {
                $result->mergeFrom($parseResult);

                if ($parser->replacesAllOtherParsers()) {
                    $parseAsNative = false;
                    break;
                }
            }
        }
        self::$objects = $objects;

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
