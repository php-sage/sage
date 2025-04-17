<?php

/** @internal */
class SageParser
{
    public static $level = 0;

    /** @var array<string, true> used to prevent recursion */
    public static $objects = array();

    /**
     * @var array keep parsers from looping todo?
     */
    private static $parsingAlternative = array();

    public static function reset() // todo test reset
    {
        self::$level   = 0;
        self::$objects = array();
    }

    /**
     * @param mixed $variable copy of user-provided variable
     * @param string|SageHtmlable $name todo escape by default, expect sageHtmlable otherwise
     *
     * @return SageParsedVariable
     */
    public static function parse(&$variable, $name = null)
    {
        // save internal data to revert after dumping to properly handle recursions etc
        $level   = self::$level++;
        $objects = self::$objects;

        $parser = new self();
        $result = $parser->doParse($variable, $name);

        self::$level   = $level;
        self::$objects = $objects;

        //        self::$level    = $revert['level'];
        //        self::$_objects = $revert['objects'];

        return $result;
    }

    private function doParse(&$variable, $name)
    {
        $result       = new SageParsedVariable();
        $result->name = $name;

        $parseAsNative = true;

        foreach (Sage::$enabledParsers as $parserClass => $enabled) {
            if (! $enabled) {
                continue;
            }

            //            // todo is this necessary anymore?
            //            if (array_key_exists($parserClass, self::$parsingAlternative)) {
            //                continue;
            //            }
            //            self::$parsingAlternative[$parserClass] = true;

            /** @var SageCustomParserInterface $parser */
            $parser      = new $parserClass();
            $parseResult = $parser->parse($variable);

            //            unset(self::$parsingAlternative[$parserClass]);
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
            // base type parser returning null means "stop processing further": e.g. recursion
            if ($parsed) {
                $result = $parsed->mergeFrom($result);
            } else {
                self::$level--;
            }
        }

        return $result;
    }
}
