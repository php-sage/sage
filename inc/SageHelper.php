<?php

/**
 * @internal
 */
class SageHelper
{
    private static $_php53;

    const MAX_STR_LENGTH = 80;

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
        'xdebug'                 => 'xdebug://%file@%line'
    );

    private static $aliasesRaw;
    private static $projectRootDir;

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
        $file = str_replace('\\', '/', $file);

        // Find common path with Sage dir
        if (! isset(self::$projectRootDir)) {
            self::$projectRootDir = '';

            $sagePathParts = explode('/', str_replace('\\', '/', SAGE_DIR));
            $filePathParts = explode('/', $file);
            foreach ($filePathParts as $i => $filePart) {
                if (! isset($sagePathParts[$i]) || $sagePathParts[$i] !== $filePart) {
                    break;
                }

                self::$projectRootDir .= $filePart . '/';
            }
        }

        if (self::$projectRootDir && strpos($file, self::$projectRootDir) === 0) {
            return substr($file, strlen(self::$projectRootDir));
        }

        return $file;
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
            $class = (strpos($ideLink, 'http://') === 0) ? ' class="_sage-ide-link" ' : ' ';

            return "<a{$class}href=\"{$ideLink}\">{$linkText}</a>";
        }

        // MODE_PLAIN
        if (strpos($ideLink, 'http://') === 0) {
            return <<<HTML
<a href="{$ideLink}">{$linkText}</a>
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
