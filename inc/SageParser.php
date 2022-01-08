<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
abstract class SageParser extends SageVariableData
{
    private static $_level = 0;
    private static $_customDataTypes;
    private static $_objectParsers;
    private static $_objects;
    private static $_marker;

    private static $_skipAlternatives = false;

    private static $_placeFullStringInValue = false;


    private static function _init()
    {
        $fh = opendir(SAGE_DIR.'parsers/custom/');
        while ($fileName = readdir($fh)) {
            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            require SAGE_DIR.'parsers/custom/'.$fileName;
            self::$_customDataTypes[] = substr($fileName, 0, -4);
        }
        $fh = opendir(SAGE_DIR.'parsers/objects/');
        while ($fileName = readdir($fh)) {
            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            require SAGE_DIR.'parsers/objects/'.$fileName;
            self::$_objectParsers[] = substr($fileName, 0, -4);
        }
    }

    public static function reset()
    {
        self::$_level = 0;
        self::$_objects = self::$_marker = null;
    }

    /**
     * main and usually single method a custom parser must implement
     *
     * @param mixed            $variable
     * @param SageVariableData $originalVarData
     *
     * @return mixed [!!!] false is returned if the variable is not of current type
     */
    abstract protected function _parse(&$variable, $originalVarData);


    /**
     * the only public entry point to return a parsed representation of a variable
     *
     * @static
     *
     * @param      $variable
     * @param null $name
     *
     * @return \SageParser
     * @throws Exception
     */
    public final static function factory(&$variable, $name = null)
    {
        isset(self::$_customDataTypes) or self::_init();

        # save internal data to revert after dumping to properly handle recursions etc
        $revert = array(
            'level'   => self::$_level,
            'objects' => self::$_objects,
        );

        self::$_level++;

        $varData = new SageVariableData;
        $varData->name = $name;

        # first parse the variable based on its type
        $varType = gettype($variable);
        $varType === 'unknown type' and $varType = 'unknown'; # PHP 5.4 inconsistency
        $methodName = '_parse_'.$varType;

        # objects can be presented in a different way altogether, INSTEAD, not ALONGSIDE the generic parser
        if ($varType === 'object') {
            foreach (self::$_objectParsers as $parserClass) {
                $className = 'Sage_Objects_'.$parserClass;

                /** @var $object SageObject */
                $object = new $className;
                if (($alternativeTabs = $object->parse($variable)) !== false) {
                    self::$_skipAlternatives = true;
                    $alternativeDisplay = new SageVariableData;
                    $alternativeDisplay->type = $object->name;
                    $alternativeDisplay->value = $object->value;
                    $alternativeDisplay->name = $name;

                    foreach ($alternativeTabs as $n => $values) {
                        $alternative = SageParser::factory($values);
                        $alternative->type = $n;
                        if (Sage::enabled() === Sage::MODE_RICH) {
                            empty($alternative->value) and $alternative->value = $alternative->extendedValue;
                            $alternativeDisplay->_alternatives[] = $alternative;
                        } else {
                            $alternativeDisplay->extendedValue[] = $alternative;
                        }
                    }

                    self::$_skipAlternatives = false;
                    self::$_level = $revert['level'];
                    self::$_objects = $revert['objects'];

                    return $alternativeDisplay;
                }
            }
        }

        if (! method_exists(__CLASS__, $methodName)) {
            $varData->type = $varType; // resource (closed) for example

            return $varData;
        }

        # base type parser returning false means "stop processing further": e.g. recursion
        if (self::$methodName($variable, $varData) === false) {
            self::$_level--;

            return $varData;
        }

        if (Sage::enabled() === Sage::MODE_RICH && ! self::$_skipAlternatives) {
            # if an alternative returns something that can be represented in an alternative way, don't :)
            self::$_skipAlternatives = true;

            # now check whether the variable can be represented in a different way
            foreach (self::$_customDataTypes as $parserClass) {
                $className = 'Sage_Parsers_'.$parserClass;

                /** @var $parser SageParser */
                $parser = new $className;
                $parser->name = $name; # the parser may overwrite the name value, so set it first

                if ($parser->_parse($variable, $varData) !== false) {
                    $varData->_alternatives[] = $parser;
                }
            }


            # if alternatives exist, push extendedValue to their front and display it as one of alternatives
            if (! empty($varData->_alternatives) && isset($varData->extendedValue)) {
                $_ = new SageVariableData;

                $_->value = $varData->extendedValue;
                $_->type = 'contents';
                $_->size = null;

                array_unshift($varData->_alternatives, $_);
                $varData->extendedValue = null;
            }

            self::$_skipAlternatives = false;
        }

        self::$_level = $revert['level'];
        self::$_objects = $revert['objects'];

        if (isset($varData->name) && strlen($varData->name) > 80) {
            $varData->name =
                self::_substr($varData->name, 0, 37)
                .'...'
                .self::_substr($varData->name, -38, null);
        }

        return $varData;
    }

    private static function _checkDepth()
    {
        return Sage::$maxLevels != 0 && self::$_level >= Sage::$maxLevels;
    }

    private static function _isArrayTabular(array $variable)
    {
        if (Sage::enabled() !== Sage::MODE_RICH) {
            return false;
        }

        $arrayKeys = array();
        $keys = null;
        $closeEnough = false;
        foreach ($variable as $row) {
            if (! is_array($row) || empty($row)) {
                return false;
            }

            foreach ($row as $col) {
                if (! empty($col) && ! is_scalar($col)) {
                    return false;
                } // todo add tabular "tolerance"
            }

            if (isset($keys) && ! $closeEnough) {
                # let's just see if the first two rows have same keys, that's faster and has the
                # positive side effect of easily spotting missing keys in later rows
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
        if ($varData->extendedValue !== null || ! empty($varData->_alternatives)) {
            return '<td>'.SageDecoratorsRich::decorate($varData).'</td>';
        }

        $output = '<td';

        if ($varData->value !== null) {
            $output .= ' title="'.$varData->type;

            if ($varData->size !== null) {
                $output .= " (".$varData->size.")";
            }

            $output .= '">'.$varData->value;
        } else {
            $output .= '>';

            if ($varData->type !== 'NULL') {
                $output .= '<u>'.$varData->type;

                if ($varData->size !== null) {
                    $output .= "(".$varData->size.")";
                }

                $output .= '</u>';
            } else {
                $output .= '<u>NULL</u>';
            }
        }


        return $output.'</td>';
    }


    public static function decodeStr($value, $encoding = null)
    {
        if (empty($value)) {
            return $value;
        }

        if (Sage::enabled() === Sage::MODE_CLI) {
            $value = str_replace("\x1b", "\\x1b", $value);
        }

        if (Sage::enabled() === Sage::MODE_CLI || Sage::enabled() === Sage::MODE_TEXT_ONLY) {
            return $value;
        }

        $encoding or $encoding = self::_detectEncoding($value);

        if ($encoding === 'UTF-8') {
            // todo we could make the symbols hover-title show the code for the invisible symbol
            # when possible force invisible characters to have some sort of display (experimental)
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '?', $value);
        }

        # this call converts all non-ASCII characters into html chars of format
        if (function_exists('mb_encode_numericentity')) {
            $value = mb_encode_numericentity(
                $value,
                array(0x80, 0xffff, 0, 0xffff,),
                $encoding
            );
        }

        return $value;
    }


    private static $_dealingWithGlobals = false;

    private static function _parse_array(&$variable, SageVariableData $variableData)
    {
        isset(self::$_marker) or self::$_marker = "\x00".uniqid();

        # naturally, $GLOBALS variable is an intertwined recursion nightmare, use black magic
        $globalsDetector = false;
        if (array_key_exists('GLOBALS', $variable) && is_array($variable['GLOBALS'])) {
            $globalsDetector = "\x01".uniqid();

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
        if (isset($variable[self::$_marker])) { # recursion; todo mayhaps show from where
            if (self::$_dealingWithGlobals) {
                $variableData->value = '*RECURSION*';
            } else {
                unset($variable[self::$_marker]);
                $variableData->value = self::$_marker;
            }

            return false;
        }
        if (self::_checkDepth()) {
            $variableData->extendedValue = "*DEPTH TOO GREAT*";

            return false;
        }

        $isSequential = self::_isSequential($variable);

        if ($variableData->size > 1 && ($arrayKeys = self::_isArrayTabular($variable)) !== false) {
            $variableData->alreadyEscaped = true;
            $variable[self::$_marker] = true; # this must be AFTER _isArrayTabular
            $firstRow = true;
            $extendedValue = '<table class="_sage-report"><thead>';

            foreach ($variable as $rowIndex => & $row) {
                # display strings in their full length
                self::$_placeFullStringInValue = true;

                if ($rowIndex === self::$_marker) {
                    continue;
                }

                if (isset($row[self::$_marker])) {
                    $variableData->value = "*RECURSION*";

                    return false;
                }


                $extendedValue .= '<tr>';
                if ($isSequential) {
                    $output = '<td>'.($rowIndex + 1).'</td>';
                } else {
                    $output = self::_decorateCell(SageParser::factory($rowIndex));
                }
                if ($firstRow) {
                    $extendedValue .= '<th>&nbsp;</th>';
                }

                # we iterate the known full set of keys from all rows in case some appeared at later rows,
                # as we only check the first two to assume
                foreach ($arrayKeys as $key) {
                    if ($firstRow) {
                        $extendedValue .= '<th>'.self::decodeStr($key).'</th>';
                    }

                    if (! array_key_exists($key, $row)) {
                        $output .= '<td class="_sage-empty"></td>';
                        continue;
                    }

                    $var = SageParser::factory($row[$key]);

                    if ($var->value === self::$_marker) {
                        $variableData->value = '*RECURSION*';

                        return false;
                    } elseif ($var->value === '*RECURSION*') {
                        $output .= '<td class="_sage-empty"><u>*RECURSION*</u></td>';
                    } else {
                        $output .= self::_decorateCell($var);
                    }
                    unset($var);
                }

                if ($firstRow) {
                    $extendedValue .= '</tr></thead><tr>';
                    $firstRow = false;
                }

                $extendedValue .= $output.'</tr>';
            }
            self::$_placeFullStringInValue = false;

            $variableData->extendedValue = $extendedValue.'</table>';

        } else {
            $variable[self::$_marker] = true;
            $extendedValue = array();

            foreach ($variable as $key => & $val) {
                if ($key === self::$_marker) {
                    continue;
                }

                $output = SageParser::factory($val);
                if ($output->value === self::$_marker) {
                    $variableData->value = "*RECURSION*"; // recursion occurred on a higher level, thus $this is recursion

                    return false;
                }
                if (! $isSequential) {
                    $output->operator = '=>';
                }
                $output->name = $isSequential ? null : "'".$key."'";
                $extendedValue[] = $output;
            }
            $variableData->extendedValue = $extendedValue;
        }

        if ($globalsDetector) {
            self::$_dealingWithGlobals = false;
        }

        unset($variable[self::$_marker]);
    }


    private static function _parse_object(&$variable, SageVariableData $variableData)
    {
        if (function_exists('spl_object_hash')) {
            $hash = spl_object_hash($variable);
        } else {
            ob_start();
            var_dump($variable);
            preg_match('[#(\d+)]', ob_get_clean(), $match);
            $hash = $match[1];
        }

        $castedArray = (array)$variable;
        $variableData->type = get_class($variable);
        $variableData->size = count($castedArray);

        if (isset(self::$_objects[$hash])) {
            $variableData->value = '*RECURSION*';

            return false;
        }
        if (self::_checkDepth()) {
            $variableData->extendedValue = "*DEPTH TOO GREAT*";

            return false;
        }


        # ArrayObject (and maybe ArrayIterator, did not try yet) unsurprisingly consist of mainly dark magic.
        # What bothers me most, var_dump sees no problem with it, and ArrayObject also uses a custom,
        # undocumented serialize function, so you can see the properties in internal functions, but
        # can never iterate some of them if the flags are not STD_PROP_LIST. Fun stuff.
        if ($variableData->type === 'ArrayObject' || is_subclass_of($variable, 'ArrayObject')) {
            $arrayObjectFlags = $variable->getFlags();
            $variable->setFlags(ArrayObject::STD_PROP_LIST);
        }

        self::$_objects[$hash] = true; // todo store reflectorObject here for alternatives cache
        $reflector = new ReflectionObject($variable);

        # add link to definition of userland objects
        if (Sage::enabled() === Sage::MODE_RICH && Sage::$fileLinkFormat && $reflector->isUserDefined()) {
            $url = SageHelper::getIdeLink($reflector->getFileName(), $reflector->getStartLine());

            $class = (strpos($url, 'http://') === 0) ? 'class="_sage-ide-link" ' : '';
            $variableData->type = "<a {$class}href=\"{$url}\">{$variableData->type}</a>";
        }
        $variableData->size = 0;

        $extendedValue = array();
        $encountered = array();

        # copy the object as an array as it provides more info than Reflection (depends)
        foreach ($castedArray as $key => $value) {
            /* casting object to array:
             * integer properties are inaccessible;
             * private variables have the class name prepended to the variable name;
             * protected variables have a '*' prepended to the variable name.
             * These prepended values have null bytes on either side.
             * http://www.php.net/manual/en/language.types.array.php#language.types.array.casting
             */
            if ($key[0] === "\x00") {

                $access = $key[1] === "*" ? "protected" : "private";

                // Remove the access level from the variable name
                $key = substr($key, strrpos($key, "\x00") + 1);
            } else {
                $access = "public";
            }

            $encountered[$key] = true;

            $output = SageParser::factory($value, self::decodeStr($key));
            $output->access = $access;
            $output->operator = '->';
            $extendedValue[] = $output;
            $variableData->size++;
        }

        foreach ($reflector->getProperties() as $property) {
            $name = $property->name;
            if ($property->isStatic() || isset($encountered[$name])) {
                continue;
            }

            if ($property->isProtected()) {
                $property->setAccessible(true);
                $access = "protected";
            } elseif ($property->isPrivate()) {
                $property->setAccessible(true);
                $access = "private";
            } else {
                $access = "public";
            }

            $value = $property->getValue($variable);

            $output = SageParser::factory($value, self::decodeStr($name));
            $output->access = $access;
            $output->operator = '->';
            $extendedValue[] = $output;
            $variableData->size++;
        }

        if (isset($arrayObjectFlags)) {
            $variable->setFlags($arrayObjectFlags);
        }

        if ($variableData->size) {
            $variableData->extendedValue = $extendedValue;
        }
    }


    private static function _parse_boolean(&$variable, SageVariableData $variableData)
    {
        $variableData->type = 'bool';
        $variableData->value = $variable ? 'TRUE' : 'FALSE';
    }

    private static function _parse_double(&$variable, SageVariableData $variableData)
    {
        $variableData->type = 'float';
        $variableData->value = $variable;
    }

    private static function _parse_integer(&$variable, SageVariableData $variableData)
    {
        $variableData->type = 'integer';
        $variableData->value = $variable;
    }

    private static function _parse_null(&$variable, SageVariableData $variableData)
    {
        $variableData->type = 'NULL';
    }

    private static function _parse_resource(&$variable, SageVariableData $variableData)
    {
        $resourceType = get_resource_type($variable);
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

    private static function _parse_string(&$variable, SageVariableData $variableData)
    {
        if (preg_match('//u', $variable)) {
            $variableData->type = 'string';
        } else {
            $variableData->type .= 'binary string';
        }

        $encoding = self::_detectEncoding($variable);
        if ($encoding !== 'ASCII') {
            $variableData->type .= ' '.$encoding;
        }


        $variableData->size = self::_strlen($variable, $encoding);
        if (Sage::enabled() !== Sage::MODE_RICH) {
            $variableData->value = '"'.self::decodeStr($variable, $encoding).'"';

            return;
        }


        if (! self::$_placeFullStringInValue) {

            $strippedString = preg_replace('[\s+]', ' ', $variable);
            if ($variable !== $strippedString) { // omit no data from display

                $variableData->value = '"'.self::decodeStr($variable, $encoding).'"';
                $variableData->extendedValue = self::decodeStr($variable, $encoding);

                return;
            }
        }

        $variableData->value = '"'.self::decodeStr($variable, $encoding).'"';
    }

    private static function _parse_unknown(&$variable, SageVariableData $variableData)
    {
        $type = gettype($variable);
        $variableData->type = "UNKNOWN".(! empty($type) ? " ({$type})" : '');
        $variableData->value = var_export($variable, true);
    }

}