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

if (defined('SAGE_DIR')) {
    return;
}
define('SAGE_DIR', dirname(__FILE__) . '/');

// Welcome to our autoloader!
// J/K it's not an autoloader, we just include all of the files (except the parsers)!
// With PHP 5.1++ compatibility in mind we don't use namespaces and do the autoloading manually
require SAGE_DIR . 'src/inc/SageLogicException.php';
require SAGE_DIR . 'src/DataStructure/SageCallerData.php';
require SAGE_DIR . 'src/inc/SageDynamicFacade.php';
require SAGE_DIR . 'src/DataStructure/SageParsedVariable.php';
require SAGE_DIR . 'src/DataStructure/SageParsedVariableContents.php';
require SAGE_DIR . 'src/DataStructure/SageParsedTraceStep.php';
require SAGE_DIR . 'src/DataStructure/SageHtmlable.php';
require SAGE_DIR . 'src/DataStructure/SageTrace.php';
require SAGE_DIR . 'src/inc/SageParser.php';
require SAGE_DIR . 'src/inc/SageNativeTypesParser.php';
require SAGE_DIR . 'src/inc/SageHelper.php';
require SAGE_DIR . 'src/inc/SageSqlFormatter.php';
require SAGE_DIR . 'src/inc/shorthands.inc.php';
require SAGE_DIR . 'src/Decorators/SageDecoratorsInterface.php';
require SAGE_DIR . 'src/Decorators/SageDecoratorsRich.php';
require SAGE_DIR . 'src/Decorators/SageDecoratorsPlain.php';
require SAGE_DIR . 'src/Parsers/SageCustomParserInterface.php';

class Sage
{
    private static $_initialized = false;
    private static $_enabledMode = true;
    private static $_openedOutput;

    /*
     *   region CONFIG
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
     *    region WIP
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

    /**
     * The ordering matters, each variable and its children are processed by each from top to bottom
     *
     * @var class-string<SageParser>[]
     */
    public static $enabledParsers = array(
        'SageParsersTrace' => true,

        'SageParsersSmarty'                    => true,
        'SageParsersSplFileInfo'               => true,
        'SageParsersClosure'                   => true,
        'SageParsersEloquent'                  => true,
        'SageParsersDateTime'                  => true,
        'SageParsersSplObjectStorage'          => true,
        'SageParsersTimestamp'                 => true,
        'SageParsersFilePath'                  => true,
        'SageParsersEloquentCollection'        => true,
        'SageParsersLaravelCollection'         => true,
        // above this line are only those parsers that $replacesAllOtherParsers

        // now we run the blacklist
        'SageParsersBlacklist'                 => true,

        // all the rest
        // SageParsersXml'                       => true,
        'SageParsersIterable'                  => true,
        'SageParsersClassStatics'              => true,
        'SageParsersColor'                     => true,
        'SageParsersJson'                      => true,
        'SageParsersXml'                       => true,
        'SageParsersClassName'                 => true,
        'SageParsersMicrotime'                 => true,
        'SageParsersInvisibleStringCharacters' => true,
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
     *   region CONSTANTS
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
    const MODE_PLAIN_HTML = 'p';

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
     * It's not zero because it doesn't matter, plus if you find this number somewhere in your logs or something - you
     * know who to blame :))
     */
    const STATUS_ERROR = 5463;

    /*
     *    region ENABLE
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
        if ($forceMode !== null) {
            $before             = self::$_enabledMode;
            self::$_enabledMode = $forceMode;

            return $before;
        }

        // ...and a getter
        return self::$_enabledMode;
    }

    /*
     *    region DUMP
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
     * Dump information about variables, accepts any number of parameters.
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
     * @see Sage::STATUS_ERROR for explanation for 5463
     */
    public static function dump($data = null)
    {
        try {
            $params = func_get_args();

            return call_user_func_array(array(new self(), 'doDump'), $params);
        } catch (Throwable $e) {
            if (file_exists(SAGE_DIR . '/.dev-mode')) {
                dd($e);
            }
        } catch (Exception $e) {
        }

        return self::STATUS_ERROR;
    }

    /**
     * @internal use Sage::dump() instead
     */
    private function doDump($data = null)
    {
        $enabledMode = self::enabled();

        if (! $enabledMode) {
            return self::STATUS_ERROR;
        }

        $this->settingsInit();

        $output           = '';
        $backtraceData    = SageHelper::php53orLater() ? debug_backtrace(true) : debug_backtrace();
        $caller           = SageCallerData::process($backtraceData);
        $decorator        = $this->detectDisplayMode($enabledMode);
        $firstRunOldValue = $this->initDecorator($decorator);
        $arguments        = $this->getWhatToDump($caller, func_get_args(), $backtraceData);

        if ($decorator->areAssetsNeeded()) {
            $output .= $decorator->init();
        }
        $output .= $decorator->wrapStart();

        foreach ($arguments as $k => $argument) {
            SageParser::$level = 0;

            // self::getWhatToDump can return an array of prepared SageParsedVariable
            if (! $argument instanceof SageParsedVariable) {
                // when the dump arguments take long to generate output, user might have changed the file and
                // Sage might not parse the arguments correctly, so check if names are set and while the
                // displayed names might be wrong, at least don't throw an error
                $name = $this->getParameterName($caller, $k);

                $argument = SageParser::parse($argument, $name);
            }

            $output .= $decorator->decorate($argument);
        }

        $output .= $decorator->wrapEnd($caller);

        // now restore all on-the-fly settings and return

        if (self::$outputFile) {
            $saveTo = self::$outputFile;
            try {
                if (! isset(self::$_openedOutput[$saveTo])) {
                    $decorator->setAssetsNeeded($firstRunOldValue);
                    self::$_openedOutput[$saveTo] = fopen($saveTo, 'w');
                }

                fwrite(self::$_openedOutput[$saveTo], $output);

                echo 'Sage -> ' . $saveTo . PHP_EOL;
            } catch (Throwable $e) {
                self::$outputFile = null;
                $output           .= "Error: Sage can't write file to " . $saveTo;
            } catch (Exception $e) {
                self::$outputFile = null;
                $output           .= "Error: Sage can't write file to " . $saveTo;
            }
        }

        self::enabled($enabledMode);

        $decorator->setAssetsNeeded(false);

        if (self::$returnOutput) {
            return $output;
        }

        if (self::$outputFile) {
            return;
        }

        echo $output;

        return self::STATUS_ERROR;
    }

    /**
     * @return SageDecoratorsPlain|SageDecoratorsRich
     */
    private function detectDisplayMode($enabledMode)
    {
        // auto-detect mode if not explicitly set
        if ($enabledMode === true) {
            if (self::$outputFile && substr(self::$outputFile, -5) === '.html') {
                $newMode = self::MODE_RICH;
            } else {
                $newMode = PHP_SAPI === 'cli' && self::$cliDetection === true
                    ? self::MODE_CLI
                    : self::MODE_RICH;
            }

            if (self::$simplifyDisplay) {
                switch ($newMode) {
                    case self::MODE_RICH:
                        $newMode = self::MODE_PLAIN_HTML;
                        break;
                    case self::MODE_CLI:
                        $newMode = self::MODE_TEXT_ONLY;
                        break;
                }
            }

            // change mode globally
            self::enabled($newMode);
        }

        $decoratorClass = self::enabled() === self::MODE_RICH ? 'SageDecoratorsRich' : 'SageDecoratorsPlain';
        /** @var SageDecoratorsPlain|SageDecoratorsRich $decorator */
        $decorator = new $decoratorClass();

        return $decorator;
    }

    private function initDecorator(SageDecoratorsPlain|SageDecoratorsRich $decorator): bool
    {
        $firstRunOldValue = $decorator->areAssetsNeeded();

        // self::$returnOutput can be true, can be a string to put multiple dumps together
        if (self::$returnOutput) {
            if (self::$returnOutput === true) {
                $decorator->setAssetsNeeded(true);
            } elseif (! isset(self::$_openedOutput[self::$returnOutput])) {
                $decorator->setAssetsNeeded(true);

                self::$_openedOutput[self::$returnOutput] = true;
            }
        }

        if (self::$outputFile && ! isset(self::$_openedOutput[self::$outputFile])) {
            $firstRunOldValue = $decorator->areAssetsNeeded();

            $decorator->setAssetsNeeded(true);
        }

        return $firstRunOldValue;
    }

    /*
     *    region HELPERS
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

    private function getParameterName(SageCallerData $caller, $k)
    {
        $name = array_key_exists($k, $caller->parameterNames)
            ? $caller->parameterNames[$k]
            : '???';

        if (strlen($name) > 60) {
            $name =
                SageHelper::substr($name, 0, 27)
                . '...'
                . SageHelper::substr($name, -28, null);
        }

        return $name;
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

    private static $loadedParsers = 0;

    /** Called before each invocation */
    private function settingsInit()
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
                if ($enabled && file_exists($f = SAGE_DIR . 'src/Parsers/' . $className . '.php')) {
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

    private function getWhatToDump(SageCallerData $caller, array $arguments, array $backtraceData)
    {
        if (count($arguments) === 0) {
            $tmp            = microtime();
            $varData        = SageParser::parse($tmp, '');
            $varData->type  = null;
            $varData->name  = 'Sage called with no arguments';
            $varData->value = null;
            $varData->size  = null;
            if ($caller->getUserLandInvoker('file')) {
                if ($caller->getUserLandInvoker('class') && $caller->getUserLandInvoker('type')) {
                    $name = $caller->getUserLandInvoker('class')
                        . $caller->getUserLandInvoker('type')
                        . $caller->getUserLandInvoker('function');
                } else {
                    $name = $caller->getUserLandInvoker('function');
                }
                $varData->name = $name . '( no parameters )';
            }

            return array($varData);
        }

        if (count($arguments) === 1) {
            // Sage::dump(1) shorthand
            if ($caller->parameterNames === array('1') && $arguments[0] === 1) {
                $caller->parameterNames = array('Debug backtrace');

                return array(SageTrace::full($backtraceData));
            }

            // Sage::dump(2) shorthand
            if ($caller->parameterNames === array('2') && $arguments[0] === 2) {
                $caller->parameterNames = array('Minimal trace (file & line only)');

                return array(SageTrace::minimal($backtraceData));
            }
        }

        return $arguments;
    }
}

if (get_cfg_var('sage.enabled') !== false) {
    Sage::enabled(get_cfg_var('sage.enabled'));
}
