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
        'xdebug'                 => 'xdebug://%f@%l'
    );

    private static $aliasesRaw;

    public static function php53orLater()
    {
        if (! isset(self::$_php53)) {
            self::$_php53 = version_compare(PHP_VERSION, '5.3.0') > 0;
        }

        return self::$_php53;
    }

    public static function isRichMode()
    {
        return Sage::enabled() === Sage::MODE_RICH;
    }

    public static function isHtmlMode()
    {
        $enabledMode = Sage::enabled();

        return $enabledMode === Sage::MODE_RICH || $enabledMode === Sage::MODE_PLAIN;
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
        $file          = str_replace('\\', '/', $file);
        $shortenedName = $file;
        $replaced      = false;
        if (is_array(Sage::$appRootDirs)) {
            foreach (Sage::$appRootDirs as $path => $replaceString) {
                if (empty($path)) {
                    continue;
                }

                $path = str_replace('\\', '/', $path);

                if (strpos($file, $path) === 0) {
                    $shortenedName = $replaceString . substr($file, strlen($path));
                    $replaced      = true;
                    break;
                }
            }
        }

        // fallback to find common path with Sage dir
        if (! $replaced) {
            $pathParts = explode('/', str_replace('\\', '/', SAGE_DIR));
            $fileParts = explode('/', $file);
            $i         = 0;
            foreach ($fileParts as $i => $filePart) {
                if (! isset($pathParts[$i]) || $pathParts[$i] !== $filePart) {
                    break;
                }
            }

            $shortenedName = ($i ? '.../' : '') . implode('/', array_slice($fileParts, $i));
        }

        return $shortenedName;
    }

    public static function buildAliases()
    {
        self::$aliasesRaw = array(
            'methods' => array(
                array('sage', 'dump'),
                array('sage', 'trace')
            )
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
            foreach (self::$aliasesRaw['methods'] as $alias) {
                if ($alias[0] === strtolower($step['class']) && $alias[1] === strtolower($step['function'])) {
                    return true;
                }
            }

            return false;
        }

        return in_array(strtolower($step['function']), self::$aliasesRaw['functions'], true);
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
        if (! self::isHtmlMode()) {
            return $file . ':' . $line;
        }

        $linkText = $linkText ? $linkText : self::shortenPath($file) . ':' . $line;
        $linkText = self::esc($linkText);

        if (! Sage::$editor) {
            return $linkText;
        }

        $ideLink = str_replace(
            array('%f', '%l', Sage::$fileLinkServerPath),
            array($file, $line, Sage::$fileLinkLocalPath),
            isset(self::$editors[Sage::$editor]) ? self::$editors[Sage::$editor] : Sage::$editor
        );

        if ($enabledMode === Sage::MODE_RICH) {
            $class = (strpos($ideLink, 'http://') === 0) ? ' class="_sage-ide-link" ' : ' ';

            return "<a{$class}href=\"{$ideLink}\">{$linkText}</a>";
        }

        // MODE_PLAIN
        if (strpos($ideLink, 'http://') === 0) {
            return <<<HTML
<a href="{$ideLink}"onclick="X=new XMLHttpRequest;X.open('GET',this.href);X.send();return!1">{$linkText}</a>
HTML;
        }

        return "<a href=\"{$ideLink}\">{$linkText}</a>";
    }

    public static function esc($value, $decode = true)
    {
        $value = self::isHtmlMode()
            ? htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8')
            : $value;

        if ($decode) {
            $value = self::decodeStr($value);
        }

        return $value;
    }

    /**
     * Make all invisible characters visible. HTML-escape if needed.
     */
    private static function decodeStr($value)
    {
        if (is_int($value)) {
            return (string)$value;
        }

        if ($value === '') {
            return '';
        }

        if (self::isHtmlMode()) {
            if (htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8') === '') {
                return '‹binary data›';
            }

            $controlCharsMap = array(
                "\v"   => '<u>\v</u>',
                "\f"   => '<u>\f</u>',
                "\033" => '<u>\e</u>',
                "\t"   => "\t<u>\\t</u>",
                "\r\n" => "<u>\\r\\n</u>\n",
                "\n"   => "<u>\\n</u>\n",
                "\r"   => "<u>\\r</u>"
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
}
