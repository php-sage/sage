<?php

/**
 * @internal
 */
class SagePrimitivesParser
{
    /**
     * @return ?SageTraceContainer
     */
    public static function parseIfTrace($data)
    {
        if (! is_array($data)) {
            return null;
        }

        $trace       = array();
        $traceFields = array('file', 'line', 'args', 'class');
        $fileFound   = false; // file element must exist in one of the steps
        $lastStep    = array();

        // validate whether a trace was indeed passed
        foreach ($data as $step) {
            if (! is_array($step) || ! isset($step['function'])) {
                return null;
            }
            if (! $fileFound && isset($step['file']) && file_exists($step['file'])) {
                $fileFound = true;
            }

            $valid = false;
            foreach ($traceFields as $element) {
                if (isset($step[$element])) {
                    $valid = true;
                    break;
                }
            }
            if (! $valid) {
                return null;
            }

            if ($step['function'] === 'spl_autoload_call') { // meaningless
                continue;
            }

            // also modify it in the same go
            if (SageHelper::stepIsInternal($step)) {
                // take first step from the top that is not inside Sage already
                if (isset($step['file'], $step['line'])) {
                    $lastStep = array(
                        'file'     => $step['file'],
                        'line'     => $step['line'],
                        'function' => '',
                    );
                }

                continue;
            }

            $trace[] = $step;
        }

        if (! $fileFound) {
            return null;
        }

        if ($lastStep) {
            array_unshift($trace, $lastStep);
        }

        // now parse the trace into a usable format
        $output = array();
        foreach ($trace as $i => $step) {
            $output[] = new SageTraceStep($step, $i);
        }

        $result        = new SageTraceContainer();
        $result->steps = $output;

        return $result;
    }

    /**
     * @return array|false return ALL keys from all rows if array is tabular, false otherwise
     */
    private static function _isArrayTabular(array $variable)
    {
        if (Sage::enabled() !== Sage::MODE_RICH) {
            return false;
        }

        $arrayKeys   = array();
        $keys        = null;
        $closeEnough = false;
        foreach ($variable as $k => $row) {
            if (isset(self::$_marker) && $k === self::$_marker) {
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

    private static function _decorateCell(SageVariableData $varData)
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

    private static $_dealingWithGlobals = false;

    /** @return false|void */
    private static function _parse_array(&$variable, SageVariableData $variableData)
    {
        isset(self::$_marker) or self::$_marker = "\x00" . uniqid();

        // naturally, $GLOBALS variable is an intertwined recursion nightmare, use black magic
        $globalsDetector = false;
        if (array_key_exists('GLOBALS', $variable) && is_array($variable['GLOBALS'])) {
            $globalsDetector = "\x01" . uniqid();

            $variable['GLOBALS'][$globalsDetector] = true;
            if (isset($variable[$globalsDetector])) {
                unset($variable[$globalsDetector]);
                self::$_dealingWithGlobals = true;
            } else {
                unset($variable['GLOBALS'][$globalsDetector]);
                $globalsDetector = false;
            }
        }

        $variableData->type = 'array';
        $variableData->size = count($variable);

        if ($variableData->size === 0) {
            return;
        }
        if (isset($variable[self::$_marker])) { // recursion; todo mayhaps show from where
            if (self::$_dealingWithGlobals) {
                $variableData->error = 'Recursion';
            } else {
                unset($variable[self::$_marker]);
                $variableData->value = self::$_marker;
            }

            return false;
        }
        if (self::isDepthLimit()) {
            $variableData->error = 'Depth too Great';

            return false;
        }

        $isSequential             = SageHelper::isArraySequential($variable);
        $variable[self::$_marker] = true;

        if ($variableData->size > 1 && ($arrayKeys = self::_isArrayTabular($variable)) !== false) {
            // tabular array parse
            $firstRow      = true;
            $extendedValue = '<table class="_sage-report"><thead>';

            foreach ($variable as $rowIndex => & $row) {
                // display strings in their full length
                self::$_placeFullStringInValue = true;

                if ($rowIndex === self::$_marker) {
                    continue;
                }

                if (isset($row[self::$_marker])) {
                    $variableData->error = 'Recursion';

                    return false;
                }

                $extendedValue .= '<tr>';
                if ($isSequential) {
                    $nestedVarData = '<td>' . (((int)$rowIndex) + 1) . '</td>';
                } else {
                    $nestedVarData = self::_decorateCell(self::process($rowIndex));
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
                        $nestedVarData .= '<td class="_sage-empty"></td>';
                        continue;
                    }

                    if (SageHelper::isKeyBlacklisted($key)) {
                        $processedVar = SageVariableData::erroneous('Redacted');
                    } else {
                        $processedVar = self::process($row[$key]);
                    }

                    if ($processedVar->value === self::$_marker) {
                        $variableData->error = 'Recursion';

                        return false;
                    }

                    if ($processedVar->error) {
                        $nestedVarData .= '<td class="_sage-empty"><u>' . $processedVar->error . '</u></td>';
                    } else {
                        $nestedVarData .= self::_decorateCell($processedVar);
                    }
                }

                if ($firstRow) {
                    $extendedValue .= '</tr></thead><tr>';
                    $firstRow      = false;
                }

                $extendedValue .= $nestedVarData . '</tr>';
            }
            self::$_placeFullStringInValue = false;

            $variableData->extendedView = new SageVariableExtendedView(
                SageVariableExtendedView::CONTENT_TYPE_STRING,
                '',
                new SageHtmlable($extendedValue . '</table>')
            );
        } else {
            $extendedValue = array();

            foreach ($variable as $key => & $val) {
                if ($key === self::$_marker) {
                    continue;
                }

                if (SageHelper::isKeyBlacklisted($key)) {
                    $nestedVarData = SageVariableData::erroneous('Redacted');
                } else {
                    $nestedVarData = self::process($val);
                }

                if ($nestedVarData->value === self::$_marker) {
                    // recursion occurred on a higher level, thus $variableData is recursion
                    $variableData->error = 'Recursion';

                    return false;
                }
                if ($isSequential) {
                    $nestedVarData->name = null;
                } else {
                    $nestedVarData->operator = '=>';
                    $nestedVarData->name     = is_int($key)
                        ? $key
                        : "'" . $key . "'";
                }
                $extendedValue[] = $nestedVarData;
            }
            $variableData->extendedView = new SageVariableExtendedView(
                SageVariableExtendedView::CONTENT_TYPE_DUMP,
                '',
                $extendedValue
            );
        }

        if ($globalsDetector) {
            self::$_dealingWithGlobals = false;
        }

        unset($variable[self::$_marker]);
    }

    /** @return false|void */
    private static function _parse_object(&$variable, SageVariableData $variableData)
    {
        $hash = self::getObjectHash($variable);

        $castedArray = (array)$variable;
        $className   = get_class($variable);
        if (! isset($variableData->type)) {
            // ArrayObject (and maybe ArrayIterator, did not try yet) unsurprisingly consist of mainly dark magic.
            // What bothers me most, var_dump sees no problem with it, and ArrayObject also uses a custom,
            // undocumented serialize function, so you can see the properties in internal functions, but
            // can never iterate some of them if the flags are not STD_PROP_LIST. Fun stuff.
            if ($className === 'ArrayObject' || is_subclass_of($variable, 'ArrayObject')) {
                $arrayObjectFlags = $variable->getFlags();
                $variable->setFlags(ArrayObject::STD_PROP_LIST);
            } elseif (strpos($className, "@anonymous\0") !== false) {
                $variableData->type = 'Anonymous class';
            } else {
                $variableData->type = $className;
            }
        }

        if (! isset($variableData->size)) {
            $variableData->size = count($castedArray);
        }

        if (isset(self::$_objects[$hash])) {
            $variableData->value = "*RECURSION* ({$hash})";

            return false;
        }

        if (self::isDepthLimit()) {
            // todo provide solution
            $variableData->error = 'Depth too Great';

            return false;
        }

        self::$_objects[$hash] = true;
        $variableReflection    = new ReflectionObject($variable);

        // add link to definition of userland objects
        if (SageHelper::isHtmlMode() && $variableReflection->isUserDefined()) {
            $variableData->type = SageHelper::ideLink(
                $variableReflection->getFileName(),
                $variableReflection->getStartLine(),
                $variableData->type
            );
        }
        $variableData->size = 0;

        $extendedValue = array();
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

                if ($variableData->type !== 'stdClass' && ! isset($publicProperties[$className][$key])) {
                    $access .= ' (dynamically added)';
                }
            }

            if (SageHelper::isKeyBlacklisted($key)) {
                $nestedVarData = SageVariableData::erroneous('Redacted');
            } else {
                $nestedVarData = self::process($value);
            }

            $nestedVarData->name     = SageHelper::esc($key);
            $nestedVarData->access   = $access;
            $nestedVarData->operator = '->';

            $extendedValue[$key] = $nestedVarData;

            $variableData->size++;
        }

        if ($variable instanceof __PHP_Incomplete_Class) {
            $variableData->extendedView = $extendedValue;

            return $castedArray;
        }

        foreach ($variableReflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            if (isset($extendedValue[$name])) {
                if (method_exists($property, 'isReadOnly') && $property->isReadOnly()) {
                    $extendedValue[$name]->access .= ' readonly';
                }

                continue;
            }

            if ($property->isProtected()) {
                $property->setAccessible(true);
                $access = 'protected';
            } elseif ($property->isPrivate()) {
                $property->setAccessible(true);
                $access = 'private';
            } else {
                $access = 'public';
            }

            if (method_exists($property, 'isInitialized')
                && ! $property->isInitialized($variable)) {
                $value  = null;
                $access .= ' [uninitialized]';
            } else {
                $value = $property->getValue($variable);
            }

            $nestedVarData = self::process($value, SageHelper::esc($name));

            $nestedVarData->access   = $access;
            $nestedVarData->operator = '->';
            $extendedValue[]         = $nestedVarData;
            $variableData->size++;
        }

        if (isset($arrayObjectFlags)) {
            $variable->setFlags($arrayObjectFlags);
        }

        if (method_exists($variableReflection, 'isEnum') && $variableReflection->isEnum()) {
            $variableData->size  = 'enum';
            $variableData->value = '"' . $variable->name . '"';
        }

        if ($variableData->size) {
            $variableData->extendedView = $extendedValue;
        }
    }

    private static function _parse_boolean(&$variable, SageVariableData $variableData)
    {
        $variableData->type  = 'bool';
        $variableData->value = $variable ? 'TRUE' : 'FALSE';
    }

    private static function _parse_double(&$variable, SageVariableData $variableData)
    {
        if (! isset($variableData->type)) {
            $variableData->type = 'float';
        }
        if (! isset($variableData->value)) {
            $variableData->value = $variable;
        }
    }

    private static function _parse_integer(&$variable, SageVariableData $variableData)
    {
        if (! isset($variableData->type)) {
            $variableData->type = 'integer';
        }
        if (! isset($variableData->value)) {
            $variableData->value = $variable;
        }
    }

    private static function _parse_null(&$variable, SageVariableData $variableData)
    {
        $variableData->type = 'NULL';
    }

    private static function _parse_resource(&$variable, SageVariableData $variableData)
    {
        $resourceType       = get_resource_type($variable);
        $variableData->type = "resource ({$resourceType})";

        if ($resourceType === 'stream' && $meta = stream_get_meta_data($variable)) {
            if (isset($meta['uri'])) {
                $file = $meta['uri'];

                if (function_exists('stream_is_local')) {
                    // Only exists on PHP >= 5.2.4
                    if (stream_is_local($file)) {
                        $file = SageHelper::shortenPath($file);
                    }
                }

                $variableData->value = $file;
            }
        }
    }

    private static function _parse_string(string &$variable, SageVariableData $variableData)
    {
        if (! isset($variableData->type)) {
            if (preg_match('//u', $variable)) {
                $variableData->type = 'string';
            } else {
                $variableData->type .= 'binary string';
            }

            $encoding = SageHelper::detectEncoding($variable);
            if ($encoding !== 'UTF-8') {
                $variableData->type .= ' ' . $encoding;
            }
        }

        if (! isset($variableData->size)) {
            $variableData->size = SageHelper::strlen($variable, isset($encoding) ? $encoding : null);
        }

        if (! isset($variableData->value)) {
            if (self::$_placeFullStringInValue) { // in tabular view
                $variableData->value = SageHelper::esc($variable);

                return;
            }

            if (! SageHelper::isRichMode()) {
                $variableData->value = '"' . SageHelper::esc($variable) . '"';

                return;
            }

            // trim inline value if too long
            if ($variableData->size > (SageHelper::MAX_STR_LENGTH + 8)) {
                $variableData->value =
                    '"'
                    . SageHelper::esc(
                        SageHelper::substr($variable, 0, SageHelper::MAX_STR_LENGTH, $encoding),
                        false
                    )
                    . '&hellip;"';
            } else {
                $variableData->value = '"' . SageHelper::esc($variable, false) . '"';
            }
        }

        if (! isset($variableData->extendedView)) {
            if (
                $variableData->size > (SageHelper::MAX_STR_LENGTH + 8)
                || $variable !== preg_replace('/\s+/', ' ', $variable)
            ) {
                $variableData->extendedView = new SageVariableExtendedView(
                    SageVariableExtendedView::CONTENT_TYPE_STRING,
                    '',
                    $variable
                );
            }
        }
    }

    private static function _parse_unknown(&$variable, SageVariableData $variableData)
    {
        $type                = gettype($variable);
        $variableData->type  = 'UNKNOWN' . (! empty($type) ? " ({$type})" : '');
        $variableData->value = var_export($variable, true);
    }

    private static function getObjectHash($variable)
    {
        if (function_exists('spl_object_hash')) { // since PHP 5.2
            return spl_object_hash($variable);
        }

        ob_start();
        var_dump($variable);
        preg_match('[#(\d+)]', ob_get_clean(), $match);

        return $match[1];
    }
}
