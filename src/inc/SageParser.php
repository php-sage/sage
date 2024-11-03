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

        $parseAsPrimitive = true;

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
                    $parseAsPrimitive = false;
                    break;
                }
            }
        }

        if ($parseAsPrimitive) {
            $result->mergeFrom(
                $this->parsePrimitive($variable)
            );
        }
        // base type parser returning false means "stop processing further": e.g. recursion
        //            if (self::parsePrimitive($variable, $varData) === false) {
        //                self::$_level--;
        //
        //                return $varData;
        //            }

        return $result;
    }

    /**
     * @return SageParsedVariable
     */
    private function parsePrimitive(&$variable)
    {
        $varType = gettype($variable);
        if ($varType === 'unknown type') {
            $varType = 'unknown';// PHP 5.4 inconsistency
        }

        switch ($varType) { // sigh, modern PHP, I miss you so!
            case 'array':
                return SagePrimitivesParser::parseArray($variable);
            case 'object':
                return SagePrimitivesParser::parseObject($variable);
            case 'boolean':
                return SagePrimitivesParser::parseBoolean($variable);
            case 'double':
                return SagePrimitivesParser::parseDouble($variable);
            case 'integer':
                return SagePrimitivesParser::parseInteger($variable);
            case 'null':
                return SagePrimitivesParser::parseNull($variable);
            case 'resource':
                return SagePrimitivesParser::parseResource($variable);
            case 'string':
                return SagePrimitivesParser::parseString($variable);
            case 'unknown':
            default: // resource (closed) for example
                return SagePrimitivesParser::parseUnknown($variable);
        }
    }
}
