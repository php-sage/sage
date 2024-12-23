<?php

/**
 * @internal
 */
class SageNativeTypesParser
{
    /** todo */
    protected static $_placeFullStringInValue = false;

    /** @var string used to prevent recursion for arrays */
    private static $arrayMarker = "\x00sage-array-marker";

    private static $dealingWithGlobals = false;

    /**
     * @return ?SageParsedVariable null means 'stop processing further': e.g. recursion
     */
    public static function parse(&$variable)
    {
        $varType = gettype($variable);
        if ($varType === 'unknown type') {
            $varType = 'unknown';// PHP 5.4 inconsistency
        }

        switch ($varType) {
            case 'array':
                return self::parseArray($variable);
            case 'object':
                return self::parseObject($variable);
            case 'boolean':
                return self::parseBoolean($variable);
            case 'double':
                return self::parseDouble($variable);
            case 'integer':
                return self::parseInteger($variable);
            case 'null':
            case 'NULL':
                return self::parseNull($variable);
            case 'resource':
                return self::parseResource($variable);
            case 'string':
                return self::parseString($variable);
            case 'unknown':
            default: // resource (closed) for example
                return self::parseUnknown($variable);
        }
    }

    /**
     * @return SageParsedVariable
     */
    private static function parseArray(array &$variable)
    {
        $result = new SageParsedVariable();

        // naturally, $GLOBALS variable is an intertwined recursion nightmare, use black magic
        $globalsDetector = false;
        if (array_key_exists('GLOBALS', $variable) && is_array($variable['GLOBALS'])) {
            $globalsDetector = "\x01" . uniqid();

            $variable['GLOBALS'][$globalsDetector] = true;
            if (isset($variable[$globalsDetector])) {
                unset($variable[$globalsDetector]);
                self::$dealingWithGlobals = true;
            } else {
                unset($variable['GLOBALS'][$globalsDetector]);
                $globalsDetector = false;
            }
        }

        $result->type = 'array';
        $result->size = count($variable);

        if ($result->size === 0) {
            return $result;
        }
        if (array_key_exists(self::$arrayMarker, $variable)) { // recursion; todo mayhaps show from where
            if (self::$dealingWithGlobals) {
                return SageParsedVariable::erroneous('Recursion');
            }

            unset($variable[self::$arrayMarker]);
            $result->value = self::$arrayMarker;

            return $result; // todo test
        }

        if (self::isDepthLimit()) {
            return SageParsedVariable::erroneous('Depth too Great');
        }

        $isSequential                 = SageHelper::isArraySequential($variable);
        $variable[self::$arrayMarker] = true;

        if ($result->size > 1 && ($arrayKeys = self::isArrayTabular($variable)) !== false) {
            // tabular array parse
            $firstRow      = true;
            $extendedValue = '<table class="_sage-report"><thead>';

            foreach ($variable as $rowIndex => & $row) {
                // display strings in their full length
                self::$_placeFullStringInValue = true;

                if ($rowIndex === self::$arrayMarker) {
                    continue;
                }

                if (isset($row[self::$arrayMarker])) {
                    $result->error = 'Recursion';

                    return null;
                }

                $extendedValue .= '<tr>';
                if ($isSequential) {
                    $parsedValue = '<td>' . (((int)$rowIndex) + 1) . '</td>';
                } else {
                    $parsedValue = self::_decorateCell(SageParser::parse($rowIndex));
                }
                if ($firstRow) {
                    $extendedValue .= '<th>&nbsp;</th>';
                }

                // we iterate the known full set of keys from all rows in case some appeared at later rows,
                // as we only check the first two to assume
                foreach ($arrayKeys as $key) {
                    if ($firstRow) {
                        $extendedValue .= '<th>' . SageHelper::esc($key) . '</th>';
                    }

                    if (! array_key_exists($key, $row)) {
                        $parsedValue .= '<td class="_sage-empty"></td>';
                        continue;
                    }

                    if (SageHelper::isKeyBlacklisted($key)) {
                        $processedVar = SageParsedVariable::erroneous('Redacted');
                    } else {
                        $processedVar = SageParser::parse($row[$key]);
                    }

                    if ($processedVar->value === self::$arrayMarker) {
                        $result->error = 'Recursion';

                        return null;
                    }

                    if ($processedVar->error) {
                        $parsedValue .= '<td class="_sage-empty"><u>' . $processedVar->error . '</u></td>';
                    } else {
                        $parsedValue .= self::_decorateCell($processedVar);
                    }
                }

                if ($firstRow) {
                    $extendedValue .= '</tr></thead><tr>';
                    $firstRow      = false;
                }

                $extendedValue .= $parsedValue . '</tr>';
            }
            self::$_placeFullStringInValue = false;

            $result->extendedView = new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_STRING,
                '',
                new SageHtmlable($extendedValue . '</table>')
            );
        } else {
            $result->extendedView = new SageParsedVariableContents(SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS);

            foreach ($variable as $key => & $val) {
                if ($key === self::$arrayMarker) {
                    continue;
                }

                if (SageHelper::isKeyBlacklisted($key)) {
                    $parsedValue = SageParsedVariable::erroneous('Redacted');
                } elseif (is_array($val) && array_key_exists(self::$arrayMarker, $val)) {
                    $parsedValue = SageParsedVariable::erroneous('Recursion');
                } else {
                    $parsedValue = SageParser::parse($val);
                }

                if ($isSequential) {
                    $parsedValue->name = null;
                } else {
                    $parsedValue->operator = '=>';
                    $parsedValue->name     = is_int($key)
                        ? $key
                        : "'" . $key . "'";
                }
                $result->extendedView->addRow($parsedValue);
            }
        }

        if ($globalsDetector) {
            self::$dealingWithGlobals = false;
        }

        unset($variable[self::$arrayMarker]);

        return $result;
    }

    /**
     * @return SageParsedVariable
     */
    private static function parseObject(&$variable)
    {
        $result = new SageParsedVariable();

        $hash = self::getObjectHash($variable);

        // PHP: the best way to dump everything about an object: cast it to array!
        $castedArray = (array)$variable;
        $className   = get_class($variable);

        // ArrayObject (and maybe ArrayIterator, did not try yet) unsurprisingly consist of mainly dark magic.
        // What bothers me most, var_dump sees no problem with it, and ArrayObject also uses a custom,
        // undocumented serialize function, so you can see the properties in internal functions, but
        // can never iterate some of them if the flags are not STD_PROP_LIST. Fun stuff.
        if ($className === 'ArrayObject' || is_subclass_of($variable, 'ArrayObject')) {
            $arrayObjectFlags = $variable->getFlags();
            $variable->setFlags(ArrayObject::STD_PROP_LIST);
        }

        if (! isset($result->type)) {
            if (strpos($className, "@anonymous\0") !== false) {
                $result->type = 'Anonymous class';
            } else {
                $result->type = $className;
            }
        }
        $result->hash = '#' . $hash; // todo improve this fast addition

        if (! isset($result->size)) {
            $result->size = count($castedArray);
        }

        if (isset(SageParser::$objects[$hash])) {
            $result->error = "*RECURSION* [{$hash}]";

            return $result;
        }

        if (self::isDepthLimit()) {
            $result->error = 'Depth too Great'; // todo suggest solution

            return $result;
        }

        SageParser::$objects[$hash] = true;
        $variableReflection         = new ReflectionObject($variable);

        // add link to definition of userland objects
        if (SageHelper::isHtmlMode() && $variableReflection->isUserDefined()) {
            $result->type = SageHelper::ideLink(
                $variableReflection->getFileName(),
                $variableReflection->getStartLine(),
                $result->type
            );
        }
        $result->size = 0;

        $result->extendedView = new SageParsedVariableContents(SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS);
        static $publicProperties = array();
        if (! isset($publicProperties[$className])) {
            $reflectionClass = new ReflectionClass($className);
            foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                $publicProperties[$className][$prop->name] = true;
            }
        }

        // copy the object as an array as it provides more info than Reflection (depends)
        foreach ($castedArray as $key => $value) {
            /* casting object to array:
             * integer properties are inaccessible;
             * private variables have the class name prepended to the variable name;
             * protected variables have a '*' prepended to the variable name.
             * These prepended values have null bytes on either side.
             * http://www.php.net/manual/en/language.types.array.php#language.types.array.casting
             */
            if (is_string($key) && $key[0] === "\x00") {
                $access = $key[1] === '*' ? 'protected' : 'private';

                // Remove the access level from the variable name
                $key = substr($key, strrpos($key, "\x00") + 1);
            } else {
                $access = 'public';

                if ($result->type !== 'stdClass' && ! isset($publicProperties[$className][$key])) {
                    $access .= ' (dynamically added)';
                }
            }

            if (SageHelper::isKeyBlacklisted($key)) {
                $nestedVarData = SageParsedVariable::erroneous('Redacted');
            } else {
                $nestedVarData = SageParser::parse($value);
            }

            $nestedVarData->name     = SageHelper::esc($key);
            $nestedVarData->access   = $access;
            $nestedVarData->operator = '->';
            $result->extendedView->addRow($nestedVarData);

            $result->size++;
        }

        if ($variable instanceof __PHP_Incomplete_Class) { // todo test
            return $result;
        }

        $recursiveReflection = $variableReflection;
        do {
            foreach ($recursiveReflection->getProperties() as $propertyReflection) {
                if ($propertyReflection->isStatic()) {
                    continue;
                }

                $name = $propertyReflection->getName();
                foreach ($result->extendedView->contents as $alreadyParsed) {
                    if ((string)$alreadyParsed->name === $name) {
                        if (method_exists($propertyReflection, 'isReadOnly') && $propertyReflection->isReadOnly()) {
                            $alreadyParsed->access .= ' readonly';
                        }

                        continue 2;
                    }
                }

                if ($propertyReflection->isProtected()) {
                    $propertyReflection->setAccessible(true);
                    $access = 'protected';
                } elseif ($propertyReflection->isPrivate()) {
                    $propertyReflection->setAccessible(true);
                    $access = 'private';
                } else {
                    $access = 'public';
                }

                if (method_exists($propertyReflection, 'isInitialized')
                    && ! $propertyReflection->isInitialized($variable)) {
                    $value  = null;
                    $access .= ' [uninitialized]';
                } else {
                    $value = $propertyReflection->getValue($variable);
                }

                $output = SageParser::parse($value, SageHelper::esc($name));

                $output->access   = $access;
                $output->operator = '->';
                $result->extendedView->addRow($output);
                $result->size++;
            }
        } while ($recursiveReflection = $recursiveReflection->getParentClass());

        if (isset($arrayObjectFlags)) {
            $variable->setFlags($arrayObjectFlags);
        }

        if (method_exists($variableReflection, 'isEnum') && $variableReflection->isEnum()) {
            $result->size  = 'enum';
            $result->value = '"' . $variable->name . '"';
        }

        return $result;
    }

    private static function parseBoolean(&$variable)
    {
        $result        = new SageParsedVariable();
        $result->type  = 'bool';
        $result->value = $variable ? 'TRUE' : 'FALSE';

        return $result;
    }

    private static function parseDouble(&$variable)
    {
        $result        = new SageParsedVariable();
        $result->type  = 'float';
        $result->value = $variable;

        return $result;
    }

    private static function parseInteger(&$variable)
    {
        $result        = new SageParsedVariable();
        $result->type  = 'integer';
        $result->value = $variable;

        return $result;
    }

    private static function parseNull(&$variable)
    {
        $result       = new SageParsedVariable();
        $result->type = 'NULL';

        return $result;
    }

    private static function parseResource(&$variable)
    {
        $result = new SageParsedVariable();

        $resourceType = get_resource_type($variable);
        $result->type = "resource ({$resourceType})";

        if ($resourceType === 'stream' && $meta = stream_get_meta_data($variable)) {
            if (isset($meta['uri'])) {
                $file = $meta['uri'];

                if (function_exists('stream_is_local')) {
                    // Only exists on PHP >= 5.2.4
                    if (stream_is_local($file)) {
                        $file = SageHelper::shortenPath($file);
                    }
                }

                $result->value = $file;
            }
        }

        return $result;
    }

    /**
     * @return ?SageParsedVariable
     */
    private static function parseString(&$variable)
    {
        $result = new SageParsedVariable();

        if (preg_match('//u', $variable)) {
            $result->type = 'string';
        } else {
            $result->type .= 'binary string';
        }

        $encoding = SageHelper::detectEncoding($variable);
        if ($encoding !== 'UTF-8') {
            $result->type .= ' ' . $encoding;
        }

        $result->size = SageHelper::strlen($variable, isset($encoding) ? $encoding : null);

        if (self::$_placeFullStringInValue) { // in tabular view
            $result->value = SageHelper::esc($variable);

            return $result;
        }

        if (! SageHelper::isRichMode()) {
            $result->value = '"' . SageHelper::esc($variable) . '"';

            return $result;
        }

        // trim inline value if too long
        if ($result->size > (SageHelper::MAX_STR_LENGTH + 8)) {
            $result->value =
                '"'
                . SageHelper::esc(
                    SageHelper::substr($variable, 0, SageHelper::MAX_STR_LENGTH, $encoding),
                    false
                )
                . '&hellip;"';
        } else {
            $result->value = '"' . SageHelper::esc($variable, false) . '"';
        }

        if (
            $result->size > (SageHelper::MAX_STR_LENGTH + 8)
            || $variable !== preg_replace('/\s+/', ' ', $variable)
        ) {
            $result->extendedView = new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_STRING,
                '',
                $variable
            );
        }

        return $result;
    }

    private static function parseUnknown(&$variable)
    {
        $result = new SageParsedVariable();

        $type          = gettype($variable);
        $result->type  = 'UNKNOWN' . (! empty($type) ? " ({$type})" : '');
        $result->value = var_export($variable, true);

        return $result;
    }

    private static function getObjectHash($variable)
    {
        if (function_exists('spl_object_id')) { // since PHP 5.2
            return spl_object_id($variable);
        }

        ob_start();
        var_dump($variable);
        preg_match('[#(\d+)]', ob_get_clean(), $match);

        return $match[1];
    }

    /**
     * @return array|false return ALL keys from all rows if array is tabular, false otherwise
     */
    private static function isArrayTabular(array $variable)
    {
        //todo
        return false;
        if (Sage::enabled() !== Sage::MODE_RICH) {
            return false;
        }

        $arrayKeys   = array();
        $keys        = null;
        $closeEnough = false;
        foreach ($variable as $k => $row) {
            if (isset(self::$arrayMarker) && $k === self::$arrayMarker) {
                continue;
            }

            if (! is_array($row) || empty($row)) {
                return false;
            }

            foreach ($row as $col) {
                if (! empty($col) && ! is_scalar($col)) {
                    return false;
                } // todo add tabular "tolerance"
            }

            if (isset($keys) && ! $closeEnough) {
                // let's just see if the first two rows have same keys, that's faster and has the
                // positive side effect of easily spotting missing keys in later rows
                if ($keys !== array_keys($row)) {
                    return false;
                }

                $closeEnough = true;
            } else {
                $keys = array_keys($row);
            }

            $arrayKeys = array_unique(array_merge($arrayKeys, $keys));
        }

        return $arrayKeys;
    }

    private static function _decorateCell(SageParsedVariable $varData)
    {
        if ($varData->extendedView) {
            $decorator = new SageDecoratorsRich();

            return '<td>' . $decorator->decorate($varData) . '</td>';
        }

        $output = '<td';

        if ($varData->value !== null) {
            $output .= ' title="' . $varData->type;

            if ($varData->size !== null) {
                $output .= ' (' . $varData->size . ')';
            }

            $output .= '">' . $varData->value;
        } else {
            $output .= '>';

            if ($varData->type !== 'NULL') {
                $output .= '<u>' . $varData->type;

                if ($varData->size !== null) {
                    $output .= '(' . $varData->size . ')';
                }

                $output .= '</u>';
            } else {
                $output .= '<u>NULL</u>';
            }
        }

        return $output . '</td>';
    }

    private static function isDepthLimit()
    {
        return Sage::$maxLevels && SageParser::$level >= Sage::$maxLevels;
    }
}
