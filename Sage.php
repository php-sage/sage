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

// todo https://www.php.net/manual/en/language.oop5.magic.php#object.debuginfo

if (defined('SAGE_DIR')) {
    return;
}

define('SAGE_DIR', dirname(__FILE__) . '/src/');

// autoloder php5.1 style!
require SAGE_DIR . 'Parsers/SageCustomParserInterface.php';
require SAGE_DIR . 'Decorators/SageDecoratorsInterface.php';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SAGE_DIR)) as $file) {
    if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
        require_once $file->getPathname();
    }
}

class Sage
{
    const MODE_RICH = 'r';
    const MODE_TEXT_ONLY = 'w';
    const MODE_CLI = 'c';
    const MODE_PLAIN_HTML = 'p';

    const THEME_ORIGINAL = 'original';
    const THEME_LIGHT = 'aante-light';
    const THEME_ORIGINAL_LIGHT = 'original-light';
    const THEME_SOLARIZED_DARK = 'solarized-dark';
    const THEME_SOLARIZED = 'solarized';

    const STATUS_ERROR = 5463;

    private static $isBootstrapped = false;

    /** @var SageInstance */
    private static $settings;

    # region Settings

    /**
     * Enables or disables Sage, and forces display mode. Also returns currently active mode.
     *
     * @param bool|Sage::MODE_* $forceMode possible values:
     *  - null or void - return current mode
     *  - false        - disable Sage
     *  - true         - enable Sage and allow it to auto-detect the best output mode
     *  - Sage::MODE_* - enable and force selected mode:
     *    -      {@see Sage::MODE_RICH}         Rich Text HTML
     *    -      {@see Sage::MODE_PLAIN}        Plain-view, HTML formatted output
     *    -      {@see Sage::MODE_CLI}          Console-formatted colored output
     *    -      {@see Sage::MODE_TEXT_ONLY}    Non-escaped plain text mode
     *
     * @return bool|Sage::MODE_* previously set value
     */
    public static function enabled($forceMode = null)
    {
        self::bootstrap();

        $currentMode = self::settings()->enabled;

        if ($forceMode !== null) {
            self::settings()->enabled = $forceMode;

            return $currentMode;
        }

        return $currentMode;
    }

    /**
     * Usage
     * ```
     *   $previousSettings = Sage::saveState();
     *   Sage::settings()->cliDetectionEnabled (false); // change any needed settings
     *   sage($var);
     *   Sage::saveState($previousSettings); // revert to previous configuration
     * ```
     *
     * @param null|SageInstance $savedState
     *
     * @return SageInstance
     */
    public static function saveState($savedState = null)
    {
        self::bootstrap();

        $oldState = clone self::$settings;

        if ($savedState) {
            self::$settings = $savedState;
        }

        return $oldState;
    }

    /**
     * Set settings for all subsequent calls to Sage.
     *
     * @return SageInstance
     */
    public static function settings()
    {
        self::bootstrap();

        /** @noinspection PhpUndefinedMethodInspection internal method */
        return self::$settings->resetRevert();
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
        /** @see self::getWhatToDump() */
        return self::dump($trace);
    }

    public static function traceWithoutArgs()
    {
        self::dump(2);
    }

    public static function simpleTrace()
    {
        self::dump(2);
    }

    public static function showEloquentQueries()
    {
        sage()->showEloquentQueries();
    }

    /**
     * Dump information about variables, accepts any number of parameters.
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
        } /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */ catch (Throwable $e) {
            if (file_exists(SAGE_DIR . '../.dev-mode')) {
                /** @noinspection ForgottenDebugOutputInspection */
                dd($e);
            }
        } /** @noinspection PhpWrongCatchClausesOrderInspection */ catch (Exception $e) {
        }

        return self::STATUS_ERROR;
    }

    private function doDump($data = null)
    {
        $previousMode = self::enabled();

        if (! $previousMode) {
            return self::STATUS_ERROR;
        }

        $this->initBeforeDump();

        if (SageServe::isServing()) {
            SageServe::setSettings();
        }

        $output         = '';
        $backtrace      = SageHelper::php53orLater() ? debug_backtrace(true) : debug_backtrace();
        $invoker        = SageInvoker::from($backtrace);
        $decorator      = SageHelper::detectDisplayMode();
        $decoratorState = SageHelper::initDecorator($decorator);
        $arguments      = $this->getWhatToDump($invoker, func_get_args(), $backtrace);

        if ($decorator->areAssetsNeeded()) {
            $output .= $decorator->init();
        }
        $output .= $decorator->wrapStart();

        foreach ($arguments as $k => $argument) {
            SageParser::$level = 0;

            // self::getWhatToDump can return an array of prepared SageParsedVariable
            if (! $argument instanceof SageParsedVariable) {
                $name = $invoker->getParameterName($k);

                $argument = SageParser::parse($argument, $name);
            }

            $output .= $decorator->decorate($argument);
        }

        $output .= $decorator->wrapEnd($invoker);

        if (self::settings()->outputToFile) {
            try {
                SageHelper::saveOutputToFile($output, $decorator, $decoratorState);
            } /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */ catch (Throwable $e) {
                self::settings()->outputToFile = null;
                $output                        .= "Error: Sage can't write file to " . self::settings()->outputToFile;
            } /** @noinspection PhpWrongCatchClausesOrderInspection */ catch (Exception $e) {
                self::settings()->outputToFile = null;
                $output                        .= "Error: Sage can't write file to " . self::settings()->outputToFile;
            }
        }

        self::enabled($previousMode);

        $decorator->setAssetsNeeded(false);

        if (self::settings()->returnOutput) {
            return $output;
        }

        if (self::settings()->outputToFile) {
            return self::STATUS_ERROR;
        }

        echo $output;

        return self::STATUS_ERROR;
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

    private function getWhatToDump(SageInvoker $caller, array $arguments, array $backtraceData)
    {
        /** @see self::trace() */
        if ($caller->sageMethodCalled === 'trace') {
            $parsedTrace        = new SageParsedVariable();
            $parsedTrace->trace = SageTraceBuilder::full(
                $arguments === array(null)
                    ? $backtraceData
                    : $arguments[0]
            );
            $parsedTrace->name  = 'Debug backtrace';

            return array($parsedTrace);
        }

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
                $parsedTrace        = new SageParsedVariable();
                $parsedTrace->trace = SageTraceBuilder::full($backtraceData);
                $parsedTrace->name  = 'Debug backtrace';

                return array($parsedTrace);
            }

            // Sage::dump(2) shorthand
            if ($caller->parameterNames === array('2') && $arguments[0] === 2) {
                $parsedTrace        = new SageParsedVariable();
                $parsedTrace->trace = SageTraceBuilder::minimal($backtraceData);
                $parsedTrace->name  = 'Debug backtrace (only paths)';

                return array($parsedTrace);
            }

            if (SageHelper::isValidTrace($arguments[0])) {
                $parsedTrace        = new SageParsedVariable();
                $parsedTrace->trace = SageTraceBuilder::full($arguments[0]);

                return array($parsedTrace);
            }
        }

        return $arguments;
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

    # region BOOTSTRAP

    /** Called before each invocation */
    private function initBeforeDump()
    {
        SageHelper::buildAliases();

        self::bootstrap();
    }

    private static function bootstrap()
    {
        if (self::$isBootstrapped) {
            return;
        }
        self::$isBootstrapped = true;

        self::$settings = new SageInstance();

        return; //todo

        self::settings(self::enabled());

        // first load defaults for configuration. In this order:
        // 1. If value is set, it means user explicitly set it
        // 2. TODO: .env
        // 3. If present in get_cfg_var means user put it into his php.ini
        // 4. Load default from Sage
        self::initSetting(
            'editor',
            ini_get('xdebug.file_link_format') ? ini_get('xdebug.file_link_format') : self::$settings->editor
        );
        self::initSetting('fileLinkServerPath', self::$settings->ideLinkServerPath);
        self::initSetting('fileLinkLocalPath', self::$settings->ideLinkLocalPath);
        self::initSetting('displayCalledFrom', self::$settings->displayCalledFrom);
        self::initSetting('maxLevels', self::$settings->maxLevels);
        self::initSetting('theme', self::$settings->theme);
        self::initSetting('expandedByDefault', self::$settings->expandedByDefault);
        self::initSetting('cliDetectionEnabled', self::$settings->cliDetectionEnabled);
        self::initSetting('cliColors', self::$settings->cliColors);
        self::initSetting('charEncodings', self::$settings->charEncodings);
        self::initSetting('returnOutput', self::$settings->returnOutput);
        self::initSetting('aliases', self::$settings->aliases);
    }

    private static function initSetting($name, $default)
    {
        if (! isset(self::$$name)) {
            // gets from php.ini
            $value = get_cfg_var('sage.' . $name);
            if (! $value) {
                $value = $default;
            }

            self::$$name = $value;
        }
    }
}

if (get_cfg_var('sage.enabled') !== false) {
    Sage::enabled(get_cfg_var('sage.enabled'));
}
