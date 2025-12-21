<?php

/**
 * @internal
 */
class SageHelper
{
    private static $_php53;
    private static $_php82;

    const MAX_STR_LENGTH = 80;

    /** @var string used to prevent recursion for arrays */
    const ARRAY_MARKER = "\x00sage-array-marker";

    public static $editors = array(
        'sublime'                => 'subl://open?url=file://%file&line=%line',
        'textmate'               => 'txmt://open?url=file://%file&line=%line',
        'emacs'                  => 'emacs://open?url=file://%file&line=%line',
        'macvim'                 => 'mvim://open/?url=file://%file&line=%line',
        'phpstorm'               => 'phpstorm://open?file=%file&line=%line',
        'phpstorm-remote'        => 'http://localhost:63342/api/file/%file:%line',
        'idea'                   => 'idea://open?file=%file&line=%line',
        'vscode'                 => 'vscode://file/%file:%line',
        'vscode-insiders'        => 'vscode-insiders://file/%file:%line',
        'vscode-remote'          => 'vscode://vscode-remote/%file:%line',
        'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%file:%line',
        'vscodium'               => 'vscodium://file/%file:%line',
        'atom'                   => 'atom://core/open/file?filename=%file&line=%line',
        'nova'                   => 'nova://core/open/file?filename=%file&line=%line',
        'netbeans'               => 'netbeans://open/?f=%file:%line',
        'xdebug'                 => 'xdebug://%file@%line',
    );

    /** @var array build from internal methods and {@see Sage::$aliases} */
    private static $aliasesRaw;
    private static $projectRootDir;

    public static function php53orLater()
    {
        if (! isset(self::$_php53)) {
            self::$_php53 = version_compare(PHP_VERSION, '5.3.0') > 0;
        }

        return self::$_php53;
    }

    public static function php82orLater()
    {
        if (! isset(self::$_php82)) {
            self::$_php82 = version_compare(PHP_VERSION, '8.2.0') > 0;
        }

        return self::$_php82;
    }

    public static function isRichMode()
    {
        return Sage::enabled() === Sage::MODE_RICH;
    }

    public static function isHtmlMode()
    {
        $enabledMode = Sage::enabled();

        return $enabledMode === Sage::MODE_RICH || $enabledMode === Sage::MODE_PLAIN_HTML;
    }

    /**
     * generic path display callback, can be configured in the settings; purpose is to show relevant path info and hide
     * as much of the path as possible.
     *
     * @param string $file
     *
     * @return string
     */
    public static function shortenPath($file)
    {
        $file = str_replace('\\', '/', $file);

        if (self::$projectRootDir && strpos($file, self::$projectRootDir) === 0) {
            return substr($file, strlen(self::$projectRootDir));
        }

        return $file;
    }

    public static function buildAliases()
    {
        self::$aliasesRaw = array(
            'methods'   => array(),
            'functions' => array(),
        );

        foreach (Sage::$aliases as $alias) {
            $alias = strtolower($alias);

            if (strpos($alias, '::') !== false) {
                self::$aliasesRaw['methods'][] = explode('::', $alias);
            } else {
                self::$aliasesRaw['functions'][] = $alias;
            }
        }
    }

    public static function detectProjectRoot($calledFromFile)
    {
        // Find common path with Sage dir
        self::$projectRootDir = '';

        if (! $calledFromFile) {
            return;
        }

        $sagePathParts = explode('/', str_replace('\\', '/', SAGE_DIR));
        $filePathParts = explode('/', $calledFromFile);
        foreach ($filePathParts as $i => $filePart) {
            if (! isset($sagePathParts[$i]) || $sagePathParts[$i] !== $filePart) {
                break;
            }

            self::$projectRootDir .= $filePart . '/';
        }
    }

    /**
     * returns whether current trace step belongs to Sage or its wrappers
     *
     * @param $step
     *
     * @return bool
     */
    public static function stepIsInternal($step)
    {
        $methodName = strtolower($step['function']);
        $className  = array_key_exists('class', $step) ? strtolower($step['class']) : '';

        if (! $className) {
            return in_array($methodName, self::$aliasesRaw['functions'], true);
        }

        if ($className === 'sage' || $className === 'sagedynamicfacade') {
            return true;
        }

        foreach (self::$aliasesRaw['methods'] as $alias) {
            if ($className === $alias[0]) {
                if (
                    $methodName === $alias[1]
                    || $alias[1] === '*'
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isKeyBlacklisted($key)
    {
        return Sage::$keysBlacklist && in_array(preg_replace('/\W/', '', $key), Sage::$keysBlacklist, true);
    }

    public static function substr($string, $start, $end, $encoding = null)
    {
        if (! isset($string)) {
            return '';
        }

        if (function_exists('mb_substr')) {
            $encoding or $encoding = self::detectEncoding($string);

            return mb_substr($string, $start, $end, $encoding);
        }

        return substr($string, $start, $end);
    }

    /**
     * returns whether the array:
     *  1) is numeric and
     *  2) in sequence starting from zero
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isArraySequential(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) === $keys;
    }

    public static function detectEncoding($value)
    {
        if (function_exists('mb_detect_encoding')) {
            $mbDetected = mb_detect_encoding($value);
            if ($mbDetected === 'ASCII') {
                return 'UTF-8';
            }
        }

        if (! function_exists('iconv')) {
            return ! empty($mbDetected) ? $mbDetected : 'UTF-8';
        }

        $md5 = md5($value);
        foreach (Sage::$charEncodings as $encoding) {
            // f*#! knows why, //IGNORE and //TRANSLIT still throw notice
            if (md5(@iconv($encoding, $encoding, $value)) === $md5) {
                return $encoding;
            }
        }

        return 'UTF-8';
    }

    public static function strlen($string, $encoding = null)
    {
        if (function_exists('mb_strlen')) {
            $encoding or $encoding = self::detectEncoding($string);

            return mb_strlen($string, $encoding);
        }

        return strlen($string);
    }

    public static function ideLink($file, $line, $linkText = null)
    {
        $enabledMode = Sage::enabled();
        $file        = self::shortenPath($file);

        $fileLine = $file;
        // in some cases (like called from inside template) we don't know the $line
        // it's then passed here as null, in that case don't display it in the link text, but keep :0 in the
        // url so that the IDE protocols don't break.
        if ($line) {
            $fileLine .= ':' . $line;
        } else {
            $line = 0;
        }

        if (! self::isHtmlMode()) {
            return $fileLine;
        }

        $linkText = $linkText
            ? $linkText
            : $fileLine;
        $linkText = self::esc($linkText);

        if (! Sage::$editor) {
            return $linkText;
        }

        $ideLink = str_replace(
            array('%file', '%line', Sage::$fileLinkServerPath),
            array($file, $line, Sage::$fileLinkLocalPath),
            isset(self::$editors[Sage::$editor]) ? self::$editors[Sage::$editor] : Sage::$editor
        );

        if ($enabledMode === Sage::MODE_RICH) {
            return new SageHtmlable("<a class=\"_sage-ide-link\" href=\"{$ideLink}\">{$linkText}</a>");
        }

        return new SageHtmlable("<a href=\"{$ideLink}\">{$linkText}</a>");
    }

    /**
     * same as {@see SageHelper::esc()} but keeps invisible characters
     */
    public static function escapeVisibleChars($value)
    {
        if ($value instanceof SageHtmlable) {
            return $value;
        }

        return new SageHtmlable(
            self::isHtmlMode() ? htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8') : $value
        );
    }

    public static function esc($value)
    {
        if ($value instanceof SageHtmlable) {
            return $value;
        }

        if (self::isHtmlMode()) {
            $escaped = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');
            if ($value !== '' && $escaped === '') {
                return new SageHtmlable('‹binary data›');
            }
            $value = $escaped;
        }

        return new SageHtmlable(self::exposeInvisibleCharacters($value));
    }

    public static function trans($key)
    {
        return array_key_exists($key, Sage::$translations) ? Sage::$translations[$key] : $key;
    }

    public static function getDebugType($variable)
    {
        if (function_exists('get_debug_type')) {
            return get_debug_type($variable);
        }

        switch (true) {
            case $variable === null:
                return 'null';
            case is_bool($variable):
                return 'bool';
            case is_string($variable):
                return 'string';
            case is_array($variable):
                return 'array';
            case is_int($variable):
                return 'int';
            case is_float($variable):
                return 'float';
            case is_object($variable):
                break;
            case $variable instanceof __PHP_Incomplete_Class:
                return '__PHP_Incomplete_Class';
            default:
                $type = @get_resource_type($variable);
                if ($type === null) {
                    return 'unknown';
                }

                if ($type === 'Unknown') {
                    $type = 'closed';
                }

                return "resource ($type)";
        }

        $class = get_class($variable);

        if (strpos($class, '@') === false) {
            return $class;
        }

        return (get_parent_class($class) ?: key(class_implements($class)) ?: 'class') . '@anonymous';
    }

    private static function exposeInvisibleCharacters($value)
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if ($value === '') {
            return '';
        }

        if (self::isHtmlMode()) {
            $controlCharsMap = array(
                "\v"   => '<u>\v</u>',
                "\f"   => '<u>\f</u>',
                "\033" => '<u>\e</u>',
                "\t"   => "\t<u>\\t</u>",
                "\r\n" => "<u>\\r\\n</u>\n",
                "\n"   => "<u>\\n</u>\n",
                "\r"   => "<u>\\r</u>",
            );
            $replaceTemplate = '<u>‹0x%d›</u>';
        } else {
            $controlCharsMap = array(
                "\v"   => '\v',
                "\f"   => '\f',
                "\033" => '\e',
            );
            $replaceTemplate = '\x%02X';
        }

        $out = '';
        $i   = 0;
        do {
            $character = $value[$i];
            $ord       = ord($character);
            // escape all invisible characters except \t, \n and \r - ORD 9, 10 and 13 respectively
            if ($ord < 32 && $ord !== 9 && $ord !== 10 && $ord !== 13) {
                if (isset($controlCharsMap[$character])) {
                    $out .= $controlCharsMap[$character];
                } else {
                    $out .= sprintf($replaceTemplate, $ord);
                }
            } else {
                $out .= $character;
            }
        } while (isset($value[++$i]));

        return $out;
    }

    /** @return string */
    public static function pre($string)
    {
        // the browser does not render leading new line in <pre>
        if ($string === "\n" || $string === "\r") {
            $string = "\n" . $string;
        }

        return '<pre>' . self::esc($string) . '</pre>';
    }

    /**
     * @param array $trace
     *
     * @return bool
     */
    public static function isValidTrace($trace)
    {
        if (! is_array($trace)) {
            return false;
        }

        $traceFields = array('file', 'line', 'args', 'class');
        $fileFound   = false; // "file" element must exist in one of the steps

        // validate whether a trace was indeed passed
        foreach ($trace as $step) {
            if (! is_array($step) || ! isset($step['function'])) {
                return false;
            }
            if (isset($step['class']) && ! isset($step['type'])) {
                return false;
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
                return false;
            }
        }

        if (! $fileFound) {
            return false;
        }

        return true;
    }

    public static function getObjectHash($variable)
    {
        if (function_exists('spl_object_id')) { // since PHP 5.2
            return '#' . spl_object_id($variable);
        }

        ob_start();
        var_dump($variable);
        preg_match('[#(\d+)]', ob_get_clean(), $match);

        return $match[1];
    }
}
