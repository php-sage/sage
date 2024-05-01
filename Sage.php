<?php

/*
 * Sage is a zero-setup PHP debugging assistant. It provides insightful data about variables and program flow.
 *
 * https://github.com/php-sage/sage
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Rokas Sleinius (raveren@gmail.com) and contributors:
 * (https://github.com/php-sage/sage/contributors)
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

// Prevent repeat inclusion
if (defined('SAGE_DIR')) {
    return;
}
define('SAGE_DIR', dirname(__FILE__) . '/');

// With PHP 5.1++ compatibility in mind we don't use namespaces and do the autoloading manually
require SAGE_DIR . 'src/inc/DataStructures/SageCallee.php';
require SAGE_DIR . 'src/inc/SageDynamicFacade.php';
require SAGE_DIR . 'src/inc/SageVariableData.php';
require SAGE_DIR . 'src/inc/SageTraceStep.php';
require SAGE_DIR . 'src/inc/SageParser.php';
require SAGE_DIR . 'src/inc/SageHelper.php';
require SAGE_DIR . 'src/inc/shorthands.inc.php';
require SAGE_DIR . 'src/decorators/SageDecoratorsInterface.php';
require SAGE_DIR . 'src/decorators/SageDecoratorsRich.php';
require SAGE_DIR . 'src/decorators/SageDecoratorsPlain.php';
require SAGE_DIR . 'src/parsers/SageParserInterface.php';

class Sage
{
    private static $_initialized = false;
    private static $_enabledMode = true;
    private static $_openedOutput;

    /*
     *   SECTION: CONFIG
     *     ██████╗ ██████╗ ███╗   ██╗███████╗██╗ ██████╗ ██╗   ██╗██████╗  █████╗ ████████╗██╗ ██████╗ ███╗   ██╗
     *    ██╔════╝██╔═══██╗████╗  ██║██╔════╝██║██╔════╝ ██║   ██║██╔══██╗██╔══██╗╚══██╔══╝██║██╔═══██╗████╗  ██║
     *    ██║     ██║   ██║██╔██╗ ██║█████╗  ██║██║  ███╗██║   ██║██████╔╝███████║   ██║   ██║██║   ██║██╔██╗ ██║
     *    ██║     ██║   ██║██║╚██╗██║██╔══╝  ██║██║   ██║██║   ██║██╔══██╗██╔══██║   ██║   ██║██║   ██║██║╚██╗██║
     *    ╚██████╗╚██████╔╝██║ ╚████║██║     ██║╚██████╔╝╚██████╔╝██║  ██║██║  ██║   ██║   ██║╚██████╔╝██║ ╚████║
     *     ╚═════╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝     ╚═╝ ╚═════╝  ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝
     *
     * ASCII ART: http://patorjk.com/software/taag/#p=display&h=1&v=2&c=c&f=ANSI%20Shadow&t=
     */

    /**
     * @var string makes visible source file paths clickable to open your editor.
     *
     * Pre-defined values:
     *   'sublime'                => 'subl://open?url=file://%file&line=%line',
     *   'textmate'               => 'txmt://open?url=file://%file&line=%line',
     *   'emacs'                  => 'emacs://open?url=file://%file&line=%line',
     *   'macvim'                 => 'mvim://open/?url=file://%file&line=%line',
     *   'phpstorm'               => 'phpstorm://open?file=%file&line=%line',
     *   'phpstorm-remote'        => 'http://localhost:63342/api/file/%file:%line',
     *   'idea'                   => 'idea://open?file=%file&line=%line',
     *   'vscode'                 => 'vscode://file/%file:%line',
     *   'vscode-insiders'        => 'vscode-insiders://file/%file:%line',
     *   'vscode-remote'          => 'vscode://vscode-remote/%file:%line',
     *   'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%file:%line',
     *   'vscodium'               => 'vscodium://file/%file:%line',
     *   'atom'                   => 'atom://core/open/file?filename=%file&line=%line',
     *   'nova'                   => 'nova://core/open/file?filename=%file&line=%line',
     *   'netbeans'               => 'netbeans://open/?f=%file:%line',
     *   'xdebug'                 => 'xdebug://%file@%line'
     *
     * Or pass a custom string where %file should be replaced with full file path, %line with line number
     * to create a custom link. Set to null to disable linking.
     *
     * Example:
     *             // works with for PHPStorm and IDE Remote Control Plugin
     *             Sage::$editor = 'phpstorm-remote';
     * Example:
     *             // same result as above, but explicitly defined
     *             Sage::$editor = 'http://localhost:63342/api/file/f:%line';
     *
     * Default:
     *             ini_get('xdebug.file_link_format') ?: 'phpstorm-remote'
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
     *               'UTF-8',
     *               'Windows-1252', // Western; includes iso-8859-1, replace this with windows-1251 if you use Russian
     *               'euc-jp',       // Japanese
     *           );
     */
    public static $charEncodings;

    /**
     * @var bool|string Sage returns output instead of echo.
     *
     * If true, the return has scripts+css always included, if set to a string, only first time per "group".
     *
     * Default:
     *           false
     */
    public static $returnOutput;

    /**
     * @var string Write output to this file instead of echoing it. If it ends in `.html` forces output in html mode.
     *
     * Default:
     *           false
     */
    public static $outputFile;

    /**
     * @var array Add new custom Sage wrapper names. Needed for nice backtraces, variable name detection and modifiers.
     *
     *            [!] Use notation `Class::method` for methods.
     *
     * Example:
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
    public static $aliases = array();

    /*
     *    SECTION: WIP
     *    ██╗    ██╗██╗██████╗
     *    ██║    ██║██║██╔══██╗
     *    ██║ █╗ ██║██║██████╔╝
     *    ██║███╗██║██║██╔═══╝
     *    ╚███╔███╔╝██║██║
     *     ╚══╝╚══╝ ╚═╝╚═╝
     */
    /**
     * @var string[] Patterns of filename paths. Keys don't matter, but you can use them to unset a particular entry.
     */
    public static $traceBlacklist = array(
        'vendor'     => '#\/vendor\/#',
        'middleware' => '#\/Middleware\/#'
    );

    public static $classNameBlacklist = array(
        'illuminate' => '/^Illuminate(?!.*(?:Exception|Collection))/'
        // 'symfony'    => '/^Symfony/'
    );

    public static $keysBlacklist = array();

    public static $minimumTraceStepsToShowFull = 1;

    /** @var class-string<SageParser>[] */
    public static $enabledParsers = array(
        'SageParsersSmarty'            => true,
        'SageParsersSplFileInfo'       => true,
        'SageParsersClosure'           => true,
        'SageParsersEloquent'          => true,
        'SageParsersDateTime'          => true,
        'SageParsersSplObjectStorage'  => true,
        'SageParsersTimestamp'         => true,
        'SageParsersFilePath'          => true,
        // above this line are only those parsers that $replacesAllOtherParsers

        // now we run the blacklist
        'SageParsersBlacklist'         => true,

        // all the rest
        'SageParsersXml'               => true,
        'SageParsersObjectIterateable' => true,
        'SageParsersClassStatics'      => true,
        'SageParsersColor'             => true,
        'SageParsersJson'              => true,
        'SageParsersClassName'         => true,
        'SageParsersMicrotime'         => true,
    );

    public static function saveState($state = array())
    {
        $rich  = new SageDecoratorsRich();
        $plain = new SageDecoratorsPlain();

        if (func_num_args()) {
            self::$_enabledMode       = $state['enabled'];
            self::$editor             = $state['editor'];
            self::$fileLinkServerPath = $state['fileLinkServerPath'];
            self::$fileLinkLocalPath  = $state['fileLinkLocalPath'];
            self::$displayCalledFrom  = $state['displayCalledFrom'];
            self::$maxLevels          = $state['maxLevels'];
            self::$theme              = $state['theme'];
            self::$expandedByDefault  = $state['expandedByDefault'];
            self::$cliDetection       = $state['cliDetection'];
            self::$cliColors          = $state['cliColors'];
            self::$charEncodings      = $state['charEncodings'];
            self::$returnOutput       = $state['returnOutput'];
            self::$outputFile         = $state['outputFile'];
            self::$aliases            = $state['aliases'];
            self::$traceBlacklist     = $state['traceBlacklist'];
            self::$classNameBlacklist = $state['classNameBlacklist'];
            self::$enabledParsers     = $state['enabledParsers'];

            $rich->setAssetsNeeded($state['SageDecoratorsRich::firstRun']);
            $plain->setAssetsNeeded($state['SageDecoratorsPlain::firstRun']);

            return;
        }

        return array(
            'enabled'                       => self::$_enabledMode,
            'editor'                        => self::$editor,
            'fileLinkServerPath'            => self::$fileLinkServerPath,
            'fileLinkLocalPath'             => self::$fileLinkLocalPath,
            'displayCalledFrom'             => self::$displayCalledFrom,
            'maxLevels'                     => self::$maxLevels,
            'theme'                         => self::$theme,
            'expandedByDefault'             => self::$expandedByDefault,
            'cliDetection'                  => self::$cliDetection,
            'cliColors'                     => self::$cliColors,
            'charEncodings'                 => self::$charEncodings,
            'returnOutput'                  => self::$returnOutput,
            'outputFile'                    => self::$outputFile,
            'aliases'                       => self::$aliases,
            'traceBlacklist'                => self::$traceBlacklist,
            'classNameBlacklist'            => self::$classNameBlacklist,
            'enabledParsers'                => self::$enabledParsers,
            'SageDecoratorsRich::firstRun'  => $rich->areAssetsNeeded(),
            'SageDecoratorsPlain::firstRun' => $plain->areAssetsNeeded()
        );
    }

    /**
     * @var bool there are multiple ways to direct sage to display "simpler" view than current mode (e.g. Rich -> PLain)
     * todo must be private
     */
    public static $simplifyDisplay = false;

    /*
     *   SECTION: CONSTANTS
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

    const THEME_ORIGINAL = 'original';
    const THEME_LIGHT = 'aante-light';
    const THEME_ORIGINAL_LIGHT = 'original-light';
    const THEME_SOLARIZED_DARK = 'solarized-dark';
    const THEME_SOLARIZED = 'solarized';

    /**
     * Returned when disabled or error occurred.
     *
     * "5463" is "SAGE" in l33tspeak :)
     *
     * The return value has to be an int otherwise modifiers throw typesafe warinings, eg if we return null:
     *
     *    ~d(); // TypeError: Cannot perform bitwise not on null
     *
     * It's not zero because it doesn't matter, and if you find this somewhere in your logs or something - you know who
     * to blame :))
     */
    const ERROR_STATUS = 5463;

    /*
     *    SECTION: ENABLE
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
            $before             = self::$_enabledMode;
            self::$_enabledMode = $forceMode;

            return $before;
        }

        // ...and a getter
        return self::$_enabledMode;
    }

    /*
     *    SECTION: MAIN
     *    ████████╗██████╗  █████╗  ██████╗███████╗    ██╗██████╗ ██╗   ██╗███╗   ███╗██████╗
     *    ╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██╔════╝   ██╔╝██╔══██╗██║   ██║████╗ ████║██╔══██╗
     *       ██║   ██████╔╝███████║██║     █████╗    ██╔╝ ██║  ██║██║   ██║██╔████╔██║██████╔╝
     *       ██║   ██╔══██╗██╔══██║██║     ██╔══╝   ██╔╝  ██║  ██║██║   ██║██║╚██╔╝██║██╔═══╝
     *       ██║   ██║  ██║██║  ██║╚██████╗███████╗██╔╝   ██████╔╝╚██████╔╝██║ ╚═╝ ██║██║
     *       ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝╚══════╝╚═╝    ╚═════╝  ╚═════╝ ╚═╝     ╚═╝╚═╝
     *
     */

    /**
     * Prints a debug backtrace, same as `sage(1)`.
     *
     * Skip trace arguments and only see the paths - `sage(2)`
     *
     * @param array $trace [OPTIONAL] you can pass your own trace, otherwise, `debug_backtrace` will be called
     *
     * @return mixed
     */
    public static function trace($trace = null)
    {
        if ($trace === null) {
            $trace = SageHelper::php53orLater() ? debug_backtrace(true) : debug_backtrace();
        }

        return self::dump($trace);
    }

    /**
     * Dump information about variables, accepts any number of parameters, supports prefix-modifiers:
     *
     * ```md
     *           | Prefix |                                              | Example      |
     *           |--------|----------------------------------------------|--------------|
     *           | print  | Puts output into current DIR as sage.html    | print sage() |
     *           | !      | Dump ignoring depth limits for large objects | ! sage()     |
     *           | ~      | Simplifies sage output (rich->html->plain)   | ~ sage()     |
     *           | -      | Clean up any output before dumping           | - sage()     |
     *           | +      | Expand all nodes (in rich view)              | + sage()     |
     *           | @      | Return output instead of displaying it       | @ sage()     |
     *           |--------|----------------------------------------------|--------------|
     * ```
     *
     * Modifiers are supported by all dump wrapper functions, including Sage::trace(). Combinations possible.
     *
     * -----
     * Shorthand to display debug_backtrace():
     *   Sage::dump( 1 );
     *   Sage::dump( debug_backtrace() ); // must be single parameter!
     *
     * @param mixed $data
     *
     * @return string|int returns 5463 if disabled/error
     *
     * @see Sage::ERROR_STATUS for explanation for 5463
     */
    public static function dump($data = null)
    {
        try {
            $params = func_get_args();

            return call_user_func_array(array('Sage', 'doDump'), $params);
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return 5463;
    }

    /**
     * @internal use Sage::dump() instead
     */
    public static function doDump($data = null)
    {
        $enabledMode = self::enabled();

        if (! $enabledMode) {
            return 5463;
        }

        self::_init();

        $calleeInfo = SageCallee::getCalleeInfo(debug_backtrace());

        // auto-detect mode if not explicitly set
        if ($enabledMode === true) {
            if ($calleeInfo->hasModifier('print') && isset($calleeInfo->callerStep['file'])) {
                $newMode = self::MODE_RICH;
            } elseif (self::$outputFile && substr(self::$outputFile, -5) === '.html') {
                $newMode = self::MODE_RICH;
            } else {
                $newMode = PHP_SAPI === 'cli' && self::$cliDetection === true
                    ? self::MODE_CLI
                    : self::MODE_RICH;
            }

            if (self::$simplifyDisplay) {
                switch ($newMode) {
                    case self::MODE_RICH:
                        $newMode = self::MODE_PLAIN;
                        break;
                    case self::MODE_CLI:
                        $newMode = self::MODE_TEXT_ONLY;
                        break;
                }
            }

            if ($calleeInfo->hasModifier('~')) {
                switch ($newMode) {
                    case self::MODE_RICH:
                        $newMode = self::MODE_PLAIN;
                        break;
                    case self::MODE_PLAIN:
                    case self::MODE_CLI:
                        $newMode = self::MODE_TEXT_ONLY;
                        break;
                }
            }

            self::enabled($newMode);
        }

        $decoratorClass = self::enabled() === self::MODE_RICH ? 'SageDecoratorsRich' : 'SageDecoratorsPlain';
        /** @var SageDecoratorsPlain|SageDecoratorsRich $decorator */
        $decorator = new $decoratorClass();

        $firstRunOldValue = $decorator->areAssetsNeeded();

        // process modifiers: @, +, !, ~ and -
        if ($calleeInfo->hasModifier('-')) {
            $decorator->setAssetsNeeded(true);

            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        if ($calleeInfo->hasModifier('+')) {
            $expandedByDefaultOldValue = self::$expandedByDefault;
            self::$expandedByDefault   = true;
        }
        if ($calleeInfo->hasModifier('!')) {
            /*if (strpos($modifiers, '!!') !== false) {
                $oldClassNameBlacklist = self::$classNameBlacklist = array();
                $oldTraceBlacklist     = self::$traceBlacklist = array();
                $oldEnabledParsers     = self::$enabledParsers;

                self::$classNameBlacklist = array();
                self::$traceBlacklist     = array();
                if (($key = array_search('SageParsersEloquent', self::$enabledParsers)) !== false) {
                    unset(self::$enabledParsers[$key]);
                }
            } else {*/
            $maxLevelsOldValue = self::$maxLevels;
            self::$maxLevels   = false;
            /*}*/
        }
        if ($calleeInfo->hasModifier('@')) {
            $returnOldValue     = self::$returnOutput;
            self::$returnOutput = true;
        }
        if (self::$returnOutput) {
            if (self::$returnOutput === true) {
                $decorator->setAssetsNeeded(true);
            } elseif (! isset(self::$_openedOutput[self::$returnOutput])) {
                $decorator->setAssetsNeeded(true);

                self::$_openedOutput[self::$returnOutput] = true;
            }
        }

        if ($calleeInfo->hasModifier('print') && isset($calleeInfo->callerStep['file'])) {
            $outputFileOldValue = self::$outputFile;
            self::$outputFile   = dirname($calleeInfo->callerStep['file']) . '/sage.html';
        }

        if (self::$outputFile && ! isset(self::$_openedOutput[self::$outputFile])) {
            $firstRunOldValue = $decorator->areAssetsNeeded();

            $decorator->setAssetsNeeded(true);
        }

        $trace      = false;
        $lightTrace = false;
        if (func_num_args() === 1) {
            if ($calleeInfo->parameterNames === array('1') && $data === 1) {
                // Sage::dump(1) shorthand
                $trace = SageHelper::php53orLater() ? debug_backtrace(true) : debug_backtrace();
            } elseif ($calleeInfo->parameterNames === array('2') && $data === 2) {
                $lightTrace = true;
                $trace      = debug_backtrace();
            } elseif (is_array($data)) {
                $trace = $data; // test if the single parameter is result of debug_backtrace()
            }
        }

        if ($trace) {
            $trace = self::_parseTrace($trace);
        }

        $output = '';
        if ($decorator->areAssetsNeeded()) {
            $output .= $decorator->init();
        }
        $output .= $decorator->wrapStart();

        if ($trace) {
            $output .= $decorator->decorateTrace($trace, $lightTrace);
        } else {
            if (func_num_args() === 0) {
                SageParser::reset();
                $tmp            = microtime();
                $varData        = SageParser::process($tmp, '');
                $varData->type  = null;
                $varData->name  = 'Sage called with no arguments';
                $varData->value = null;
                $varData->size  = null;
                if (! empty($calleeInfo->callerStep['file'])) {
                    if (! empty($calleeInfo->callerStep['class']) && ! empty($calleeInfo->callerStep['type'])) {
                        $name = $calleeInfo->callerStep['class']
                            . $calleeInfo->callerStep['type']
                            . $calleeInfo->callerStep['function'];
                    } else {
                        $name = $calleeInfo->callerStep['function'];
                    }
                    $varData->name = $name . '( no parameters )';
                }
                $output .= $decorator->decorate($varData);
            } else {
                foreach (func_get_args() as $k => $argument) {
                    SageParser::reset();
                    // when the dump arguments take long to generate output, user might have changed the file and
                    // Sage might not parse the arguments correctly, so check if names are set and while the
                    // displayed names might be wrong, at least don't throw an error
                    $output .= $decorator->decorate(
                        SageParser::process($argument, empty($names[$k]) ? '???' : $names[$k])
                    );
                }
            }
        }

        $output .= $decorator->wrapEnd($calleeInfo);

        // now restore all on-the-fly settings and return

        if (self::$outputFile) {
            try {
                if (! isset(self::$_openedOutput[self::$outputFile])) {
                    self::$_openedOutput[self::$outputFile] = fopen(self::$outputFile, 'w');
                    $decorator->setAssetsNeeded($firstRunOldValue);
                }

                fwrite(self::$_openedOutput[self::$outputFile], $output);

                echo 'Sage -> ' . self::$outputFile . PHP_EOL;
            } catch (Throwable $e) {
                self::$outputFile = null;
                $output           .= "Error: Sage can't write file to " . self::$outputFile;
            } catch (Exception $e) {
                self::$outputFile = null;
                $output           .= "Error: Sage can't write file to " . self::$outputFile;
            }
        }

        self::enabled($enabledMode);

        $decorator->setAssetsNeeded(false);

        if ($calleeInfo->hasModifier('~')) {
            $decorator->setAssetsNeeded($firstRunOldValue);
        }

        if ($calleeInfo->hasModifier('+')) {
            self::$expandedByDefault = $expandedByDefaultOldValue;
        }

        if (isset($maxLevelsOldValue)) {
            self::$maxLevels = $maxLevelsOldValue;
        }

        if ($calleeInfo->hasModifier('print') && isset($calleeInfo->callerStep['file'])) {
            self::$outputFile = $outputFileOldValue;

            return 5463;
        }

        if ($calleeInfo->hasModifier('@')) {
            self::$returnOutput = $returnOldValue;
            $decorator->setAssetsNeeded($firstRunOldValue);

            return $output;
        }

        if (self::$returnOutput) {
            return $output;
        }

        if (self::$outputFile) {
            return 5463;
        }

        echo $output;

        return 5463;
    }

    /*
     *    SECTION: HELPERS
     *    ███╗   ███╗██╗███████╗ ██████╗    ██╗  ██╗███████╗██╗     ██████╗ ███████╗██████╗ ███████╗
     *    ████╗ ████║██║██╔════╝██╔════╝    ██║  ██║██╔════╝██║     ██╔══██╗██╔════╝██╔══██╗██╔════╝
     *    ██╔████╔██║██║███████╗██║         ███████║█████╗  ██║     ██████╔╝█████╗  ██████╔╝███████╗
     *    ██║╚██╔╝██║██║╚════██║██║         ██╔══██║██╔══╝  ██║     ██╔═══╝ ██╔══╝  ██╔══██╗╚════██║
     *    ██║ ╚═╝ ██║██║███████║╚██████╗    ██║  ██║███████╗███████╗██║     ███████╗██║  ██║███████║
     *    ╚═╝     ╚═╝╚═╝╚══════╝ ╚═════╝    ╚═╝  ╚═╝╚══════╝╚══════╝╚═╝     ╚══════╝╚═╝  ╚═╝╚══════╝
     *
     */

    public static function traceWithoutArgs()
    {
        self::dump(2);
    }

    public static function showEloquentQueries()
    {
        // maintain PHP5.1+ compatibility
        if (SageHelper::php53orLater()) {
            self::$aliases[] = __CLASS__ . '::' . __FUNCTION__;

            require SAGE_DIR . 'src/inc/eloquentListener.inc.php';
        }
    }

    /*
     *    SECTION: PRIVATE
     *    ██████╗ ██████╗ ██╗██╗   ██╗ █████╗ ████████╗███████╗
     *    ██╔══██╗██╔══██╗██║██║   ██║██╔══██╗╚══██╔══╝██╔════╝
     *    ██████╔╝██████╔╝██║██║   ██║███████║   ██║   █████╗
     *    ██╔═══╝ ██╔══██╗██║╚██╗ ██╔╝██╔══██║   ██║   ██╔══╝
     *    ██║     ██║  ██║██║ ╚████╔╝ ██║  ██║   ██║   ███████╗
     *    ╚═╝     ╚═╝  ╚═╝╚═╝  ╚═══╝  ╚═╝  ╚═╝   ╚═╝   ╚══════╝
     *
     */

    private static function _parseTrace($data)
    {
        $trace       = array();
        $traceFields = array('file', 'line', 'args', 'class');
        $fileFound   = false; // file element must exist in one of the steps
        $lastStep    = array();

        // validate whether a trace was indeed passed
        foreach ($data as $step) {
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
            return false;
        }

        if ($lastStep) {
            array_unshift($trace, $lastStep);
        }

        // now parse the trace into a usable format
        $output = array();
        foreach ($trace as $i => $step) {
            $output[] = new SageTraceStep($step, $i);
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
            $value = get_cfg_var('sage.' . $name);
            if (! $value) {
                $value = $default;
            }

            self::$$name = $value;
        }
    }

    private static $loadedParsers = 0;

    /** Called before each invocation */
    private static function _init()
    {
        SageHelper::buildAliases();

        $parsersCount = 0;
        foreach (Sage::$enabledParsers as $enabled) {
            if ($enabled) {
                $parsersCount++;
            }
        }

        if (self::$loadedParsers !== $parsersCount) {
            self::$loadedParsers = $parsersCount;
            foreach (Sage::$enabledParsers as $className => $enabled) {
                if ($enabled && file_exists($f = SAGE_DIR . 'src/parsers/' . $className . '.php')) {
                    require_once $f;
                }
            }
        }

        if (self::$_initialized) {
            return;
        }

        // first load defaults for configuration. In this order:
        // 1. If value is set, it means user explicitly set it
        // 2. TODO: composer.json
        // 3. If present in get_cfg_var means user put it into his php.ini
        // 4. Load default from Sage
        self::_initSetting(
            'editor',
            ini_get('xdebug.file_link_format') ? ini_get('xdebug.file_link_format') : 'phpstorm-remote'
        );
        self::_initSetting('fileLinkServerPath', null);
        self::_initSetting('fileLinkLocalPath', null);
        self::_initSetting('displayCalledFrom', true);
        self::_initSetting('maxLevels', 7);
        self::_initSetting('theme', self::THEME_ORIGINAL);
        self::_initSetting('expandedByDefault', false);
        self::_initSetting('cliDetection', true);
        self::_initSetting('cliColors', true);
        self::_initSetting(
            'charEncodings',
            array(
                'UTF-8',
                'Windows-1252', // Western; includes iso-8859-1, replace this with windows-1251 if you have Russian code
                'euc-jp',       // Japanese
            )
        );
        self::_initSetting('returnOutput', false);
        self::_initSetting('aliases', array());
    }
}

if (get_cfg_var('sage.enabled') !== false) {
    Sage::enabled(get_cfg_var('sage.enabled'));
}
