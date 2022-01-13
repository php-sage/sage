<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Rokas Sleinius (raveren@gmail.com) and contributors (https://github.com/php-sage/sage/contributors)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Sage is a zero-setup debugging tool to output insightful information about variables and stack traces.
 *
 * https://github.com/php-sage/sage
 */

if (defined('SAGE_DIR')) {
    return;
}

define('SAGE_DIR', __DIR__.'/');

require SAGE_DIR.'inc/SageVariableData.php';
require SAGE_DIR.'inc/SageParser.php';
require SAGE_DIR.'inc/SageHelper.php';
require SAGE_DIR.'decorators/SageDecoratorsRich.php';
require SAGE_DIR.'decorators/SageDecoratorsPlain.php';

class Sage
{
    private static $_initialized = false;
    private static $_enabledMode = true;


    /*
     *     ██████╗ ██████╗ ███╗   ██╗███████╗██╗ ██████╗ ██╗   ██╗██████╗  █████╗ ████████╗██╗ ██████╗ ███╗   ██╗
     *    ██╔════╝██╔═══██╗████╗  ██║██╔════╝██║██╔════╝ ██║   ██║██╔══██╗██╔══██╗╚══██╔══╝██║██╔═══██╗████╗  ██║
     *    ██║     ██║   ██║██╔██╗ ██║█████╗  ██║██║  ███╗██║   ██║██████╔╝███████║   ██║   ██║██║   ██║██╔██╗ ██║
     *    ██║     ██║   ██║██║╚██╗██║██╔══╝  ██║██║   ██║██║   ██║██╔══██╗██╔══██║   ██║   ██║██║   ██║██║╚██╗██║
     *    ╚██████╗╚██████╔╝██║ ╚████║██║     ██║╚██████╔╝╚██████╔╝██║  ██║██║  ██║   ██║   ██║╚██████╔╝██║ ╚████║
     *     ╚═════╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝     ╚═╝ ╚═════╝  ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝
     *
     * credit: patorjk.com/software/taag/#p=display&h=1&v=2&c=c&f=ANSI Shadow&t=
     */

    /**
     * @var string makes visible source file paths clickable to open your editor.
     *
     * Pre-defined values:
     *             'sublime'                => 'subl://open?url=file://%f&line=%l',
     *             'textmate'               => 'txmt://open?url=file://%f&line=%l',
     *             'emacs'                  => 'emacs://open?url=file://%f&line=%l',
     *             'macvim'                 => 'mvim://open/?url=file://%f&line=%l',
     *             'phpstorm'               => 'phpstorm://open?file=%f&line=%l',
     *             'phpstorm-remotecall'    => 'http://localhost:8091?message=%f:%l',
     *             'idea'                   => 'idea://open?file=%f&line=%l',
     *             'vscode'                 => 'vscode://file/%f:%l',
     *             'vscode-insiders'        => 'vscode-insiders://file/%f:%l',
     *             'vscode-remote'          => 'vscode://vscode-remote/%f:%l',
     *             'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%f:%l',
     *             'vscodium'               => 'vscodium://file/%f:%l',
     *             'atom'                   => 'atom://core/open/file?filename=%f&line=%l',
     *             'nova'                   => 'nova://core/open/file?filename=%f&line=%l',
     *             'netbeans'               => 'netbeans://open/?f=%f:%l',
     *             'xdebug'                 => 'xdebug://%f@%l',
     *
     * Or pass a custom string where %f should be replaced with full file path, %l with line number to create a
     * custom link. Set to null to disable linking.
     *
     * Example:
     *             // works with for PHPStorm and RemoteCall Plugin
     *             Sage::$editor = 'phpstorm-remotecall';
     * Example:
     *             // same result as above, but explicitly defined
     *             Sage::$editor = 'http://localhost:8091/?message=%f:%l';
     *
     * Default:
     *             ini_get('xdebug.file_link_format') ?: 'phpstorm-remotecall'
     *
     */
    public static $editor;


    /**
     * @var string the full path (not URL) to your project folder on your remote dev server, be this Homestead, Docker,
     *             or in the cloud.
     *
     * Default:
     *             null
     */
    public static $fileLinkServerPath;


    /**
     * @var string the full path (not URL) to your project on your local machine, the way your IDE or editor accesses
     *             the files.
     *
     * Default:
     *             null
     */
    public static $fileLinkLocalPath;


    /**
     * @var bool whether to display where Sage was called from
     *
     * Default:
     *           true
     */
    public static $displayCalledFrom;


    /**
     * @var int max array/object levels to go deep, set to zero/false to disable
     *
     * Default:
     *          7
     */
    public static $maxLevels;


    /**
     * @var string theme for rich view
     *
     * Example:
     *             Sage::$theme = Sage::THEME_ORIGINAL;
     *             Sage::$theme = Sage::THEME_LIGHT;
     *             Sage::$theme = Sage::THEME_SOLARIZED;
     *             Sage::$theme = Sage::THEME_SOLARIZED_DARK;
     *
     * Default:
     *             Sage::THEME_ORIGINAL
     */
    public static $theme;


    /**
     * @var array directories of your application that will be displayed instead of the full path. Keys are paths,
     *            values are replacement strings.
     *
     *            Use this if you need to hide the access path from output.
     *
     * Example (for Kohana framework (R.I.P.)):
     *            Sage::appRootDirs = array(
     *                 SYSPATH => 'SYSPATH',
     *                 MODPATH => 'MODPATH',
     *                 DOCROOT => 'DOCROOT',
     *            );
     *
     * Example #2:
     *            Sage::appRootDirs = array( realpath( __DIR__ . '/../../..' ) => 'ROOT' );
     *
     * Default:
     *            array( $_SERVER['DOCUMENT_ROOT'] => 'ROOT' )
     */
    public static $appRootDirs;


    /**
     * @var bool draw rich output already expanded without having to click
     *
     * Default:
     *           false
     */
    public static $expandedByDefault;


    /**
     * @var bool enable detection when running in command line and adjust output format accordingly.
     *
     * Default:
     *           true
     */
    public static $cliDetection;


    /**
     * @var bool in addition to above setting, enable detection when Sage is run in *UNIX* command line.
     * Attempts to add coloring, but if seen as plain text, the color information is visible as gibberish
     *
     * Default:
     *           true
     */
    public static $cliColors;


    /**
     * @var array possible alternative char encodings in order of probability,
     *
     * Default:
     *           array(
     *                 'UTF-8',
     *                 'Windows-1252', // Western; includes iso-8859-1, replace this with windows-1251 if you have Russian code
     *                 'euc-jp',       // Japanese
     *           );
     */
    public static $charEncodings;

    /**
     * @var bool Sage returns output instead of echo
     *
     * Default:
     *           false
     */
    public static $returnOutput;


    /**`
     * @var string|array Add new custom Sage wrapper names. Optional, but needed for backtraces, variable name
     *                   detection and modifiers to work properly. Accepts array or comma separated string.
     *                   Use notation `Class::method` for methods.
     *
     * Example :
     *            function doom_dump($args)
     *            {
     *                echo "DOOOM!";
     *                d(...func_get_args());
     *            }
     *            Sage::$aliases = 'doom_dump';
     *
     * Default:
     *            array()
     */
    public static $aliases;

    /*
     *     ██████╗ ██████╗ ███╗   ██╗███████╗████████╗ █████╗ ███╗   ██╗████████╗███████╗
     *    ██╔════╝██╔═══██╗████╗  ██║██╔════╝╚══██╔══╝██╔══██╗████╗  ██║╚══██╔══╝██╔════╝
     *    ██║     ██║   ██║██╔██╗ ██║███████╗   ██║   ███████║██╔██╗ ██║   ██║   ███████╗
     *    ██║     ██║   ██║██║╚██╗██║╚════██║   ██║   ██╔══██║██║╚██╗██║   ██║   ╚════██║
     *    ╚██████╗╚██████╔╝██║ ╚████║███████║   ██║   ██║  ██║██║ ╚████║   ██║   ███████║
     *     ╚═════╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═╝   ╚══════╝
     *
     */

    const MODE_RICH = 'r';
    const MODE_TEXT_ONLY = 'w';
    const MODE_CLI = 'c';
    const MODE_PLAIN = 'p';

    /** @deprecated in favor of Sage::MODE_TEXT_ONLY will be removed in the next version! */
    const MODE_WHITESPACE = 'w';

    const THEME_ORIGINAL = 'original';
    const THEME_LIGHT = 'aante-light';
    const THEME_SOLARIZED_DARK = 'solarized-dark';
    const THEME_SOLARIZED = 'solarized';


    /*
     *    ███████╗███╗   ██╗ █████╗ ██████╗ ██╗     ███████╗██████╗
     *    ██╔════╝████╗  ██║██╔══██╗██╔══██╗██║     ██╔════╝██╔══██╗
     *    █████╗  ██╔██╗ ██║███████║██████╔╝██║     █████╗  ██║  ██║
     *    ██╔══╝  ██║╚██╗██║██╔══██║██╔══██╗██║     ██╔══╝  ██║  ██║
     *    ███████╗██║ ╚████║██║  ██║██████╔╝███████╗███████╗██████╔╝
     *    ╚══════╝╚═╝  ╚═══╝╚═╝  ╚═╝╚═════╝ ╚══════╝╚══════╝╚═════╝
     */

    /**
     * Enables or disables Sage, and forces display mode. Also returns currently active mode.
     *
     * @param mixed $forceMode
     *                        null or void - return current mode
     *                        false        - disable Sage
     *                        true         - enable Sage and allow it to auto-detect the best formatting
     *                        Sage::MODE_* - enable and force selected mode:
     *                        -      Sage::MODE_RICH         Rich Text HTML
     *                        -      Sage::MODE_PLAIN        Plain-view, HTML formatted output
     *                        -      Sage::MODE_CLI          Console-formatted colored output
     *                        -      Sage::MODE_TEXT_ONLY    Non-escaped plain text mode
     *
     * @return mixed            previously set value
     */
    public static function enabled($forceMode = null)
    {
        // act both as a setter...
        if (isset($forceMode)) {
            $before = self::$_enabledMode;
            self::$_enabledMode = $forceMode;

            return $before;
        }

        // ...and a getter
        return self::$_enabledMode;
    }

    /*
     *    ████████╗██████╗  █████╗  ██████╗███████╗    ██╗██████╗ ██╗   ██╗███╗   ███╗██████╗
     *    ╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██╔════╝   ██╔╝██╔══██╗██║   ██║████╗ ████║██╔══██╗
     *       ██║   ██████╔╝███████║██║     █████╗    ██╔╝ ██║  ██║██║   ██║██╔████╔██║██████╔╝
     *       ██║   ██╔══██╗██╔══██║██║     ██╔══╝   ██╔╝  ██║  ██║██║   ██║██║╚██╔╝██║██╔═══╝
     *       ██║   ██║  ██║██║  ██║╚██████╗███████╗██╔╝   ██████╔╝╚██████╔╝██║ ╚═╝ ██║██║
     *       ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝╚══════╝╚═╝    ╚═════╝  ╚═════╝ ╚═╝     ╚═╝╚═╝
     *
     */

    /**
     * Prints a debug backtrace, same as Sage::dump(1)
     *
     * @param array $trace [OPTIONAL] you can pass your own trace, otherwise, `debug_backtrace` will be called
     *
     * @return mixed
     */
    public static function trace($trace = null)
    {
        if (! self::enabled()) {
            return '';
        }

        return self::dump(isset($trace)
            ? $trace
            : debug_backtrace(true));
    }

    /**
     * Dump information about variables, accepts any number of parameters, supports modifiers:
     *
     *  clean up any output before Sage and place the dump at the top of page:
     *   - Sage::dump()
     *  *****
     *  expand all nodes on display:
     *   ! Sage::dump()
     *  *****
     *  dump variables disregarding their depth:
     *   + Sage::dump()
     *  *****
     *  return output instead of displaying it:
     *   @ Sage::dump()
     *  *****
     *  force output as plain text
     *   ~ Sage::dump()
     *
     * Modifiers are supported by all dump wrapper functions, including Sage::trace(). Space is optional.
     *
     *
     * You can also use the following shorthand to display debug_backtrace():
     *   Sage::dump( 1 );
     *
     * Passing the result from debug_backtrace() to Sage::dump() as a single parameter will display it as trace too:
     *   $trace = debug_backtrace( true );
     *   Sage::dump( $trace );
     *  Or simply:
     *   Sage::dump( debug_backtrace() );
     *
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function dump($data = null)
    {
        $enabledMode = self::enabled();
        if (! $enabledMode) {
            return '';
        }

        self::_init();

        list($names, $modifiers, $callee, $previousCaller, $miniTrace) = self::_getCalleeInfo(
            defined('DEBUG_BACKTRACE_IGNORE_ARGS')
                ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                : debug_backtrace()
        );

        // auto-detect mode if not explicitly set
        if (! in_array($enabledMode, array(
            self::MODE_RICH,
            self::MODE_TEXT_ONLY,
            self::MODE_CLI,
            self::MODE_PLAIN
        ), true)) {
            $newMode = (PHP_SAPI === 'cli' && self::$cliDetection === true)
                ? self::MODE_CLI
                : self::MODE_RICH;

            self::enabled($newMode);
        }

        /** @var SageDecoratorsPlain|SageDecoratorsRich $decorator */
        $decorator = self::enabled() === self::MODE_RICH
            ? 'SageDecoratorsRich'
            : 'SageDecoratorsPlain';

        $firstRunOldValue = $decorator::$firstRun;

        // process modifiers: @, +, !, ~ and -
        if (strpos($modifiers, '-') !== false) {
            $decorator::$firstRun = true;
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        if (strpos($modifiers, '!') !== false) {
            $expandedByDefaultOldValue = self::$expandedByDefault;
            self::$expandedByDefault = true;
        }
        if (strpos($modifiers, '+') !== false) {
            $maxLevelsOldValue = self::$maxLevels;
            self::$maxLevels = false;
        }
        if (strpos($modifiers, '@') !== false) {
            $returnOldValue = self::$returnOutput;
            self::$returnOutput = true;
            $decorator::$firstRun = true;
        }
        if (strpos($modifiers, '~') !== false) {
            if ($firstRunOldValue !== $decorator::$firstRun) {
                $firstRunTmp = $decorator::$firstRun;
                $decorator::$firstRun = $firstRunOldValue;
                $decorator = 'SageDecoratorsPlain';
                $firstRunOldValue = $decorator::$firstRun;
                $decorator::$firstRun = $firstRunTmp;
            }
            self::enabled(self::MODE_TEXT_ONLY);
        }

        $output = '';
        if ($decorator::$firstRun) {
            $output .= call_user_func(array($decorator, 'init'));
        }

        $trace = false;
        if ($data === 1 && $names === array(null) && func_num_args() === 1) { // Sage::dump(1) shorthand
            $trace = SageHelper::php53() ? debug_backtrace(true) : debug_backtrace();
        } elseif (func_num_args() === 1 && is_array($data)) {
            $trace = $data; // test if the single parameter is result of debug_backtrace()
        }
        $trace and $trace = self::_parseTrace($trace);

        $output .= call_user_func(array($decorator, 'wrapStart'));
        if ($trace) {
            $output .= call_user_func(array($decorator, 'decorateTrace'), $trace);
        } else {
            $data = func_num_args() === 0
                ? array("[[no arguments passed]]")
                : func_get_args();

            foreach ($data as $k => $argument) {
                SageParser::reset();
                // when the dump arguments take long to generate output, user might have changed the file and
                // Sage might not parse the arguments correctly, so check if names are set and while the
                // displayed names might be wrong, at least don't throw an error
                $output .= call_user_func(
                    array($decorator, 'decorate'),
                    SageParser::process($argument, isset($names[$k]) ? $names[$k] : '')
                );
            }
        }

        $output .= call_user_func(array($decorator, 'wrapEnd'), $callee, $miniTrace, $previousCaller);

        self::enabled($enabledMode);

        $decorator::$firstRun = false;
        if (strpos($modifiers, '~') !== false) {
            $decorator::$firstRun = $firstRunOldValue;
        } else {
            self::enabled($enabledMode);
        }
        if (strpos($modifiers, '!') !== false) {
            self::$expandedByDefault = $expandedByDefaultOldValue;
        }
        if (strpos($modifiers, '+') !== false) {
            self::$maxLevels = $maxLevelsOldValue;
        }
        if (strpos($modifiers, '@') !== false) {
            self::$returnOutput = $returnOldValue;
            $decorator::$firstRun = $firstRunOldValue;

            return $output;
        }

        if (self::$returnOutput) {
            return $output;
        }

        echo $output;

        return '';
    }


    /*
     *    ██████╗ ██████╗ ██╗██╗   ██╗ █████╗ ████████╗███████╗
     *    ██╔══██╗██╔══██╗██║██║   ██║██╔══██╗╚══██╔══╝██╔════╝
     *    ██████╔╝██████╔╝██║██║   ██║███████║   ██║   █████╗
     *    ██╔═══╝ ██╔══██╗██║╚██╗ ██╔╝██╔══██║   ██║   ██╔══╝
     *    ██║     ██║  ██║██║ ╚████╔╝ ██║  ██║   ██║   ███████╗
     *    ╚═╝     ╚═╝  ╚═╝╚═╝  ╚═══╝  ╚═╝  ╚═╝   ╚═╝   ╚══════╝
     *
     */


    /**
     * trace helper, shows the place in code inline
     *
     * @param string $file       full path to file
     * @param int    $lineNumber the line to display
     * @param int    $padding    surrounding lines to show besides the main one
     *
     * @return bool|string
     */
    private static function _showSource($file, $lineNumber, $padding = 7)
    {
        if (! $file || ! is_readable($file)) {
            // continuing will cause errors
            return false;
        }

        // open the file and set the line position
        $file = fopen($file, 'r');
        $line = 0;

        // Set the reading range
        $range = array(
            'start' => $lineNumber - $padding,
            'end'   => $lineNumber + $padding,
        );

        // set the zero-padding amount for line numbers
        $format = '% '.strlen($range['end']).'d';

        $source = '';
        while (($row = fgets($file)) !== false) {
            // increment the line number
            if (++$line > $range['end']) {
                break;
            }

            if ($line >= $range['start']) {
                // make the row safe for output
                $row = htmlspecialchars($row, ENT_NOQUOTES, 'UTF-8');

                // trim whitespace and sanitize the row
                $row = '<span>'.sprintf($format, $line).'</span> '.$row;

                if ($line === $lineNumber) {
                    // apply highlighting to this row
                    $row = '<div class="_sage-highlight">'.$row.'</div>';
                } else {
                    $row = '<div>'.$row.'</div>';
                }

                // add to the captured source
                $source .= $row;
            }
        }

        // close the file
        fclose($file);

        return $source;
    }

    /**
     * returns parameter names that the function was passed, as well as any predefined symbols before function
     * call (modifiers)
     *
     * @param array $trace
     *
     * @return array( $parameters, $modifier, $callee, $previousCaller )
     */
    private static function _getCalleeInfo($trace)
    {
        $previousCaller = array();
        $miniTrace = array();
        $prevStep = array();

        // go from back of trace to find first occurrence of call to Sage or its wrappers
        while ($step = array_pop($trace)) {
            if (SageHelper::stepIsInternal($step)) {
                $previousCaller = $prevStep;
                break;
            }

            if (isset($step['file'], $step['line'])) {
                unset($step['object'], $step['args']);
                array_unshift($miniTrace, $step);
            }

            $prevStep = $step;
        }
        $callee = $step;

        if (! isset($callee['file']) || ! is_readable($callee['file'])) {
            return array(null, null, $callee, $previousCaller, $miniTrace);
        }

        // open the file and read it up to the position where the function call expression ended
        $file = fopen($callee['file'], 'r');
        $line = 0;
        $source = '';
        while (($row = fgets($file)) !== false) {
            if (++$line > $callee['line']) {
                break;
            }
            $source .= $row;
        }
        fclose($file);
        $source = self::_removeAllButCode($source);

        if (empty($callee['class'])) {
            $codePattern = $callee['function'];
        } else {
            if ($callee['type'] === '::') {
                $codePattern = $callee['class']."\x07*".$callee['type']."\x07*".$callee['function'];;
            } else /*if ( $callee['type'] === '->' )*/ {
                $codePattern = ".*\x07*".$callee['type']."\x07*".$callee['function'];;
            }
        }

        // get the position of the last call to the function
        preg_match_all("
            [
            # beginning of statement
            [\x07{(]

            # search for modifiers (group 1)
            ([-+!@~]*)?

            # spaces
            \x07*

            # check if output is assigned to a variable (group 2) todo: does not detect concat
            (
                \\$[a-z0-9_]+ # variable
                \x07*\\.?=\x07*  # assignment
            )?

            # possibly a namespace symbol
            \\\\?

			# spaces again
            \x07*

            # main call to Sage
            ({$codePattern})

			# spaces everywhere
            \x07*

            # find the character where Sage's opening bracket resides (group 3)
            (\\()

            ]ix",
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $modifiers = end($matches[1]);
        $assignment = end($matches[2]);
        $callToSage = end($matches[3]);
        $bracket = end($matches[4]);

        if (empty($callToSage)) {
            // if a wrapper is misconfigured, don't display the whole file as variable name
            return array(array(), $modifiers, $callee, $previousCaller, $miniTrace);
        }

        $modifiers = $modifiers[0];
        if ($assignment[1] !== -1) {
            $modifiers .= '@';
        }

        $paramsString = preg_replace("[\x07+]", ' ', substr($source, $bracket[1] + 1));
        // we now have a string like this:
        // <parameters passed>); <the rest of the last read line>

        // remove everything in brackets and quotes, we don't need nested statements nor literal strings which would
        // only complicate separating individual arguments
        $c = strlen($paramsString);
        $inString = $escaped = $openedBracket = $closingBracket = false;
        $i = 0;
        $inBrackets = 0;
        $openedBrackets = array();

        while ($i < $c) {
            $letter = $paramsString[$i];

            if (! $inString) {
                if ($letter === '\'' || $letter === '"') {
                    $inString = $letter;
                } elseif ($letter === '(' || $letter === '[') {
                    $inBrackets++;
                    $openedBrackets[] = $openedBracket = $letter;
                    $closingBracket = $openedBracket === '(' ? ')' : ']';
                } elseif ($inBrackets && $letter === $closingBracket) {
                    $inBrackets--;
                    array_pop($openedBrackets);
                    $openedBracket = end($openedBrackets);
                    $closingBracket = $openedBracket === '(' ? ')' : ']';
                } elseif (! $inBrackets && $letter === ')') {
                    $paramsString = substr($paramsString, 0, $i);
                    break;
                }
            } elseif ($letter === $inString && ! $escaped) {
                $inString = false;
            }

            // replace whatever was inside quotes or brackets with untypeable characters, we don't
            // need that info. We'll later replace the whole string with '...'
            if ($inBrackets > 0) {
                if ($inBrackets > 1 || $letter !== $openedBracket) {
                    $paramsString[$i] = "\x07";
                }
            }
            if ($inString) {
                if ($letter !== $inString || $escaped) {
                    $paramsString[$i] = "\x07";
                }
            }

            $escaped = ! $escaped && ($letter === '\\');
            $i++;
        }

        // by now we have an un-nested arguments list, lets make it to an array for processing further
        $arguments = explode(',', preg_replace("[\x07+]", '...', $paramsString));

        // test each argument whether it was passed literary or was it an expression or a variable name
        $parameters = array();
        $blacklist = array('null', 'true', 'false', 'array(...)', 'array()', '"..."', '[...]', 'b"..."',);
        foreach ($arguments as $argument) {
            $argument = trim($argument);

            if (is_numeric($argument)
                || in_array(str_replace("'", '"', strtolower($argument)), $blacklist, true)
            ) {
                $parameters[] = null;
            } else {
                $parameters[] = $argument;
            }
        }

        return array($parameters, $modifiers, $callee, $previousCaller, $miniTrace);
    }

    /**
     * removes comments and zaps whitespace & < ?php tags from php code, makes for easier further parsing
     *
     * @param string $source
     *
     * @return string
     */
    private static function _removeAllButCode($source)
    {
        $commentTokens = array(
            T_COMMENT     => true,
            T_INLINE_HTML => true,
            T_DOC_COMMENT => true,
        );
        $whiteSpaceTokens = array(
            T_WHITESPACE         => true,
            T_CLOSE_TAG          => true,
            T_OPEN_TAG           => true,
            T_OPEN_TAG_WITH_ECHO => true,
        );

        $cleanedSource = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (isset($commentTokens[$token[0]])) {
                    continue;
                }

                if (isset($whiteSpaceTokens[$token[0]])) {
                    $token = "\x07";
                } else {
                    $token = $token[1];
                }
            } elseif ($token === ';') {
                $token = "\x07";
            }

            $cleanedSource .= $token;
        }

        return $cleanedSource;
    }


    private static function _parseTrace(array $data)
    {
        $trace = array();
        $traceFields = array('file', 'line', 'args', 'class');
        $fileFound = false; // file element must exist in one of the steps

        // validate whether a trace was indeed passed
        while ($step = array_pop($data)) {
            if (! is_array($step) || ! isset($step['function'])) {
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

            if (SageHelper::stepIsInternal($step)) {
                $step = array(
                    'file'     => $step['file'],
                    'line'     => $step['line'],
                    'function' => '',
                );
                array_unshift($trace, $step);
                break;
            }
            if ($step['function'] !== 'spl_autoload_call') { // meaningless
                array_unshift($trace, $step);
            }
        }
        if (! $fileFound) {
            return false;
        }

        $output = array();
        foreach ($trace as $step) {
            if (isset($step['file'])) {
                $file = $step['file'];

                if (isset($step['line'])) {
                    $line = $step['line'];
                    // include the source of this step
                    if (self::enabled() === self::MODE_RICH) {
                        $source = self::_showSource($file, $line);
                    }
                }
            }

            $function = $step['function'];

            if (in_array($function, array('include', 'include_once', 'require', 'require_once'))) {
                if (empty($step['args'])) {
                    // no arguments
                    $args = array();
                } else {
                    // sanitize the included file path
                    $args = array('file' => SageHelper::shortenPath($step['args'][0]));
                }
            } elseif (isset($step['args'])) {
                if (empty($step['class']) && ! function_exists($function)) {
                    // introspection on closures or language constructs in a stack trace is impossible before PHP 5.3
                    $params = null;
                } else {
                    try {
                        if (isset($step['class'])) {
                            if (method_exists($step['class'], $function)) {
                                $reflection = new ReflectionMethod($step['class'], $function);
                            } elseif (isset($step['type']) && $step['type'] === '::') {
                                $reflection = new ReflectionMethod($step['class'], '__callStatic');
                            } else {
                                $reflection = new ReflectionMethod($step['class'], '__call');
                            }
                        } else {
                            $reflection = new ReflectionFunction($function);
                        }

                        // get the function parameters
                        $params = $reflection->getParameters();
                    } catch (Exception $e) { // avoid various PHP version incompatibilities
                        $params = null;
                    }
                }

                $args = array();
                foreach ($step['args'] as $i => $arg) {
                    if (isset($params[$i])) {
                        // assign the argument by the parameter name
                        $args[$params[$i]->name] = $arg;
                    } else {
                        // assign the argument by number
                        $args['#'.($i + 1)] = $arg;
                    }
                }
            }

            if (isset($step['class'])) {
                // Class->method() or Class::method()
                $function = $step['class'].$step['type'].$function;
            }

            // todo it's possible to parse the object name out from the source!
            $output[] = array(
                'function' => $function,
                'args'     => isset($args) ? $args : null,
                'file'     => isset($file) ? $file : null,
                'line'     => isset($line) ? $line : null,
                'source'   => isset($source) ? $source : null,
                'object'   => isset($step['object']) ? $step['object'] : null,
            );

            unset($function, $args, $file, $line, $source);
        }

        return $output;
    }

    /*
     *    ██╗███╗   ██╗██╗████████╗
     *    ██║████╗  ██║██║╚══██╔══╝
     *    ██║██╔██╗ ██║██║   ██║
     *    ██║██║╚██╗██║██║   ██║
     *    ██║██║ ╚████║██║   ██║
     *    ╚═╝╚═╝  ╚═══╝╚═╝   ╚═╝
     *
     */

    private static function _initSetting($name, $default)
    {
        if (! isset(self::$$name)) {
            $value = get_cfg_var('sage.'.$name);
            if (! $value) {
                $value = $default;
            }

            self::$$name = $value;
        }
    }

    private static function _init()
    {
        SageHelper::buildAliases();

        if (self::$_initialized) {
            return;
        }

        // first load defaults for configuration. In this order:
        // 1. If value is set, it means user explicitly set it
        // 2. TODO: composer.json
        // 3. If present in get_cfg_var means user put it into his php.ini
        // 4. Load default from Sage
        self::_initSetting('editor', ini_get('xdebug.file_link_format'));
        self::_initSetting('fileLinkServerPath', null);
        self::_initSetting('fileLinkLocalPath', null);
        self::_initSetting('displayCalledFrom', true);
        self::_initSetting('maxLevels', 7);
        self::_initSetting('theme', self::THEME_ORIGINAL);
        self::_initSetting('appRootDirs', array($_SERVER['DOCUMENT_ROOT'] => 'ROOT'));
        self::_initSetting('expandedByDefault', false);
        self::_initSetting('cliDetection', true);
        self::_initSetting('cliColors', true);
        self::_initSetting('charEncodings', array(
                'UTF-8',
                'Windows-1252', // Western; includes iso-8859-1, replace this with windows-1251 if you have
                'euc-jp',       // Japanese
            )
        );
        self::_initSetting('returnOutput', false);
        self::_initSetting('aliases', array());
    }
}


/*
 *    ███████╗██╗  ██╗ ██████╗ ██████╗ ████████╗██╗  ██╗ █████╗ ███╗   ██╗██████╗ ███████╗
 *    ██╔════╝██║  ██║██╔═══██╗██╔══██╗╚══██╔══╝██║  ██║██╔══██╗████╗  ██║██╔══██╗██╔════╝
 *    ███████╗███████║██║   ██║██████╔╝   ██║   ███████║███████║██╔██╗ ██║██║  ██║███████╗
 *    ╚════██║██╔══██║██║   ██║██╔══██╗   ██║   ██╔══██║██╔══██║██║╚██╗██║██║  ██║╚════██║
 *    ███████║██║  ██║╚██████╔╝██║  ██║   ██║   ██║  ██║██║  ██║██║ ╚████║██████╔╝███████║
 *    ╚══════╝╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ ╚══════╝
 *
 */


if (! function_exists('d')) {
    /**
     * Alias of Sage::dump()
     *
     * @return string
     */
    function d()
    {
        if (! Sage::enabled()) {
            return '';
        }
        $_ = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $_);
    }
}

if (! function_exists('sage')) {
    /**
     * Alias of Sage::dump()
     *
     * @return string
     */
    function sage()
    {
        if (! Sage::enabled()) {
            return '';
        }
        $_ = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $_);
    }
}

if (! function_exists('dd')) {
    /**
     * Alias of Sage::dump(); die;
     * [!!!] IMPORTANT: execution will halt after call to this function
     */
    function dd()
    {
        if (! Sage::enabled()) {
            return '';
        }

        $_ = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $_);
        die;
    }
}

if (! function_exists('ddd')) {
    /**
     * Alias of Sage::dump(); die;
     * [!!!] IMPORTANT: execution will halt after call to this function
     */
    function ddd()
    {
        if (! Sage::enabled()) {
            return '';
        }

        $_ = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $_);
        die;
    }
}

if (! function_exists('saged')) {
    /**
     * Alias of Sage::dump(); die;
     * [!!!] IMPORTANT: execution will halt after call to this function
     */
    function saged()
    {
        if (! Sage::enabled()) {
            return '';
        }

        $_ = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $_);
        die;
    }
}

if (! function_exists('s')) {
    /**
     * Alias of Sage::dump(), however the output is in plain htmlescaped text and some minor visibility enhancements
     * added. If run in CLI mode, output is pure whitespace.
     *
     * To force rendering mode without autodetecting anything:
     *
     *  Sage::enabled( Sage::MODE_PLAIN );
     *  Sage::dump( $variable );
     *
     * [!!!] IMPORTANT: execution will halt after call to this function
     *
     * @return string
     */
    function s()
    {
        $enabled = Sage::enabled();
        if (! $enabled) {
            return '';
        }

        if ($enabled !== Sage::MODE_TEXT_ONLY) { // if already in whitespace, don't elevate to plain
            Sage::enabled( // remove cli colors in cli mode; remove rich interface in HTML mode
                PHP_SAPI === 'cli' ? Sage::MODE_TEXT_ONLY : Sage::MODE_PLAIN
            );
        }

        $params = func_get_args();
        $dump = call_user_func_array(array('Sage', 'dump'), $params);
        Sage::enabled($enabled);

        return $dump;
    }
}

if (! function_exists('sd')) {
    /**
     * @return string
     * @see s()
     *
     * [!!!] IMPORTANT: execution will halt after call to this function
     *
     */
    function sd()
    {
        $enabled = Sage::enabled();
        if (! $enabled) {
            return '';
        }

        if ($enabled !== Sage::MODE_TEXT_ONLY) {
            Sage::enabled(
                PHP_SAPI === 'cli' ? Sage::MODE_TEXT_ONLY : Sage::MODE_PLAIN
            );
        }

        $params = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $params);
        die;
    }
}


if (get_cfg_var('sage.enabled') !== false) {
    Sage::enabled(get_cfg_var('sage.enabled'));
}