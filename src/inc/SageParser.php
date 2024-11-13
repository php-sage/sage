<?php

/** @internal */
class SageParser
{
    public static $level = 0;
    private static $objects; // todo is still needed?

    /**
     * @var array keep parsers from looping
     */
    private static $parsingAlternative = array();

    public static function reset()
    {
        self::$level   = 0;
        self::$objects = null;
    }

    /**
     * @param mixed               $variable copy of user-provided variable
     * @param string|SageHtmlable $name     todo escape by default, expect sageHtmlable otherwise
     *
     * @return SageParsedVariable
     */
    public static function parse(&$variable, $name = null)
    {
        // save internal data to revert after dumping to properly handle recursions etc
        $level = self::$level++;

        $parser = new self();
        $result = $parser->doParse($variable, $name);

        self::$level = $level;
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
            $parsed = $this->parseNative($variable);
            // base type parser returning null means "stop processing further": e.g. recursion
            if ($parsed) {
                $result->mergeFrom($parsed);
            } else {
                self::$level--;
            }
        }

        return $result;
    }

    /**
     * @return ?SageParsedVariable null means 'stop processing further': e.g. recursion
     */
    private function parseNative(&$variable)
    {
        $varType = gettype($variable);
        if ($varType === 'unknown type') {
            $varType = 'unknown';// PHP 5.4 inconsistency
        }

        switch ($varType) {
            case 'array':
                return SageNativeTypesParser::parseArray($variable);
            case 'object':
                return SageNativeTypesParser::parseObject($variable);
            case 'boolean':
                return SageNativeTypesParser::parseBoolean($variable);
            case 'double':
                return SageNativeTypesParser::parseDouble($variable);
            case 'integer':
                return SageNativeTypesParser::parseInteger($variable);
            case 'null':
                return SageNativeTypesParser::parseNull($variable);
            case 'resource':
                return SageNativeTypesParser::parseResource($variable);
            case 'string':
                return SageNativeTypesParser::parseString($variable);
            case 'unknown':
            default: // resource (closed) for example
                return SageNativeTypesParser::parseUnknown($variable);
        }
    }
}
