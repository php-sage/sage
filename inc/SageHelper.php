<?php

/**
 * @internal
 */
class SageHelper
{
    private static $_php53;
    const MAX_STR_LENGTH = 80;

    public static $editors = array(
        'sublime'                => 'subl://open?url=file://%f&line=%l',
        'textmate'               => 'txmt://open?url=file://%f&line=%l',
        'emacs'                  => 'emacs://open?url=file://%f&line=%l',
        'macvim'                 => 'mvim://open/?url=file://%f&line=%l',
        'phpstorm'               => 'phpstorm://open?file=%f&line=%l',
        'phpstorm-remotecall'    => 'http://localhost:8091?message=%f:%l',
        'idea'                   => 'idea://open?file=%f&line=%l',
        'vscode'                 => 'vscode://file/%f:%l',
        'vscode-insiders'        => 'vscode-insiders://file/%f:%l',
        'vscode-remote'          => 'vscode://vscode-remote/%f:%l',
        'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%f:%l',
        'vscodium'               => 'vscodium://file/%f:%l',
        'atom'                   => 'atom://core/open/file?filename=%f&line=%l',
        'nova'                   => 'nova://core/open/file?filename=%f&line=%l',
        'netbeans'               => 'netbeans://open/?f=%f:%l',
        'xdebug'                 => 'xdebug://%f@%l',
    );

    public static function php53()
    {
        if (! isset(self::$_php53)) {
            self::$_php53 = version_compare(PHP_VERSION, '5.3.0');
        }

        return self::$_php53;
    }

    public static function isRichMode()
    {
        return Sage::enabled() === Sage::MODE_RICH;
    }

    public static function errorHandler($errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
    {
        if (error_reporting() & $errno) {
            // This error is not suppressed by current error reporting settings
            // Convert the error into an ErrorException
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        }

        // Do not execute the PHP error handler
        return true;
    }

    public static function exceptionText(Exception $e)
    {
        return sprintf(
            '%s [ %s ]: %s ~ %s [ %d ]',
            get_class($e),
            $e->getCode(),
            strip_tags($e->getMessage()),
            str_replace(SAGE_DIR, 'SAGE_DIR/', $e->getFile()),
            $e->getLine()
        );
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
        $shortenedName = $file;
        $replaced = false;
        if (is_array(Sage::$appRootDirs)) {
            foreach (Sage::$appRootDirs as $path => $replaceString) {
                if (empty($path)) {
                    continue;
                }

                $path = str_replace('\\', '/', $path);

                if (strpos($file, $path) === 0) {
                    $shortenedName = $replaceString.substr($file, strlen($path));
                    $replaced = true;
                    break;
                }
            }
        }

        // fallback to find common path with Sage dir
        if (! $replaced) {
            $pathParts = explode('/', str_replace('\\', '/', SAGE_DIR));
            $fileParts = explode('/', $file);
            $i = 0;
            foreach ($fileParts as $i => $filePart) {
                if (! isset($pathParts[$i]) || $pathParts[$i] !== $filePart) {
                    break;
                }
            }

            $shortenedName = ($i ? '.../' : '').implode('/', array_slice($fileParts, $i));
        }

        return $shortenedName;
    }

    private static $aliases;

    /**
     * called during initialization phase of Sage::dump
     *
     * @return void
     */
    public static function buildAliases()
    {
        $aliases = array(
            'methods'   => array(
                array('sage', 'dump'),
                array('sage', 'trace'),
            ),
            'functions' => array(
                'd',
                'sage',
                'dd',
                'saged',
                'ddd',
                's',
                'sd',
            ),
        );

        if (! empty(Sage::$aliases)) {
            $a = is_string(Sage::$aliases) ?
                explode(',', strtolower(Sage::$aliases))
                : Sage::$aliases;

            foreach ($a as $alias) {
                if (strpos($alias, '::') !== false) {
                    $aliases['methods'][] = explode('::', $alias);
                } else {
                    $aliases['functions'][] = $alias;
                }
            }
        }

        self::$aliases = $aliases;
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
        if (isset($step['class'])) {
            foreach (self::$aliases['methods'] as $alias) {
                if ($alias[0] === strtolower($step['class']) && $alias[1] === strtolower($step['function'])) {
                    return true;
                }
            }

            return false;
        }

        return in_array(strtolower($step['function']), self::$aliases['functions'], true);
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
                return 'ASCII';
            }
        }


        if (! function_exists('iconv')) {
            return ! empty($mbDetected) ? $mbDetected : 'UTF-8';
        }

        $md5 = md5($value);
        foreach (Sage::$charEncodings as $encoding) {
            // fuck knows why, //IGNORE and //TRANSLIT still throw notice
            if (md5(@iconv($encoding, $encoding, $value)) === $md5) {
                return $encoding;
            }
        }

        return 'ASCII';
    }

    public static function strlen($string, $encoding = null)
    {
        if (function_exists('mb_strlen')) {
            $encoding or $encoding = self::detectEncoding($string);

            return mb_strlen($string, $encoding);
        }

        return strlen($string);
    }

    public static function decodeStr($value, $encoding = null)
    {
        $enabledMode = Sage::enabled();

        if ($enabledMode === Sage::MODE_TEXT_ONLY || empty($value)) {
            return $value;
        }

        if ($enabledMode === Sage::MODE_CLI) {
            return str_replace("\x1b", "\\x1b", $value);
        }

        $encoding or $encoding = self::detectEncoding($value);
        $value = htmlspecialchars($value, ENT_NOQUOTES, $encoding === 'ASCII' ? 'UTF-8' : $encoding);

        if ($encoding === 'UTF-8') {
            // todo we could make the symbols hover-title show the code for the invisible symbol
            // when possible force invisible characters to have some sort of display (experimental)
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '?', $value);
        }

        // this call converts all non-ASCII characters into html chars of format
        if (function_exists('mb_encode_numericentity')) {
            $value = mb_encode_numericentity(
                $value,
                array(0x80, 0xffff, 0, 0xffff,),
                $encoding
            );
        }

        return $value;
    }

    public static function ideLink($file, $line, $linkText = null)
    {
        $enabledMode = Sage::enabled();
        if ($enabledMode === Sage::MODE_CLI || $enabledMode === Sage::MODE_TEXT_ONLY) {
            return $file.':'.$line;
        } else {
            $linkText = $linkText ? $linkText : self::shortenPath($file).':'.$line;
            $linkText = htmlspecialchars($linkText, ENT_NOQUOTES);

            if (! Sage::$editor) {
                return $linkText;
            }

            $ideLink = str_replace(
                array('%f', '%l', Sage::$fileLinkServerPath),
                array($file, $line, Sage::$fileLinkLocalPath),
                isset(self::$editors[Sage::$editor]) ? self::$editors[Sage::$editor] : Sage::$editor
            );

            if ($enabledMode === Sage::MODE_RICH) {
                $class = (strpos($ideLink, 'http://') === 0) ? 'class="_sage-ide-link" ' : '';

                return "<a {$class}href=\"{$ideLink}\">{$linkText}</a>";
            }

            // MODE_PLAIN
            if (strpos($ideLink, 'http://') === 0) {
                return <<<HTML
<a href="{$ideLink}"onclick="X=new XMLHttpRequest;X.open('GET',this.href);X.send();return!1">{$linkText}</a>
HTML;
            } else {
                return "<a href=\"{$ideLink}\">{$linkText}</a>";
            }
        }
    }
}