<?php

/**
 * @internal
 */
class SageDynamicFacade
{
    private $isSettingDefaults = false;
    private static $defaultSettings = array();
    private $configuredStateForOutput = array();

    /**
     * @var string Stores reference to variable if user requested to save it instead of echo.
     */
    private $saveOutputToThisVariable = null;
    /** @var string Same but globally */
    private static $saveAllOutputToThisVariable = null;

    private $alreadyDumped = false;

    /**
     * Constructor for ancient PHP versions support
     */
    public function SageDynamicFacade()
    {
    }

    public function __construct()
    {
        $this->SageDynamicFacade();
    }

    # region Dump

    public function dump($data = null)
    {
        $params = func_get_args();

        $this->alreadyDumped = true;

        if ($this->configuredStateForOutput) {
            $stateBackup = Sage::saveState();
            Sage::saveState($this->configuredStateForOutput);
        }

        if ($this->saveOutputToThisVariable !== null || self::$saveAllOutputToThisVariable !== null) {
            $output = call_user_func_array(array('Sage', 'dump'), $params); # PROCEDURE: dump

            if (self::$saveAllOutputToThisVariable !== null) {
                self::$saveAllOutputToThisVariable .= $output;
            } elseif ($this->saveOutputToThisVariable !== null) {
                $this->saveOutputToThisVariable .= $output;
            }
        } else {
            call_user_func_array(array('Sage', 'dump'), $params); # PROCEDURE: dump
        }

        if ($this->configuredStateForOutput) {
            Sage::saveState($stateBackup);
        }

        return $this;
    }

    /**
     * @param array $ignoreKeys
     * @param mixed $data any number of parameters
     *
     * @return $this
     */
    public function dumpWithKeyBlacklist($ignoreKeys, $data = null)
    {
        $params = func_get_args();
        unset($params[0]);

        $stateBackup = $this->saveSettingsPt1();
        Sage::settings()
            ->enabledParsers(array())
            ->keysBlacklist($ignoreKeys)
            ->overrideTranslations(array('key_blacklisted' => 'Skipped'))
        ;
        $this->saveSettingsPt2($stateBackup);

        call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump

        Sage::saveState($stateBackup);

        return $this;
    }

    /**
     * Shorthand for dump()
     */
    public function d($data = null)
    {
        $params = func_get_args();

        return call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
    }

    public function dd($data = null)
    {
        if ($this->alreadyDumped && func_num_args() === 0) {
            die;
        }

        $params = func_get_args();

        call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump

        die;
    }

    public function saged($data = null)
    {
        call_user_func_array(array($this, 'dd'), func_get_args()); # PROCEDURE: dump
    }

    # region Trace

    /**
     * Display trace
     */
    public function trace($onlyFilePaths = false)
    {
        $this->alreadyDumped = true;

        if ($onlyFilePaths) {
            Sage::dump(2);
        } else {
            Sage::trace();
        }
    }

    /**
     * Display paths-only trace
     */
    public function simpleTrace()
    {
        $this->alreadyDumped = true;

        Sage::dump(2);
    }

    # region Settings

    /**
     * Get global settings.
     *
     * @return SageSettings
     */
    public function settings()
    {
        return Sage::settings();
    }

    /**
     * Makes all changes to Sage configuration persist for all future instances.
     *
     * E.g. use it to set a theme globally.
     */
    public function setDefaults()
    {
        $this->isSettingDefaults = true;

        if (! self::$defaultSettings) {
            self::$defaultSettings = Sage::saveState();
        }

        if ($this->configuredStateForOutput) {
            Sage::saveState($this->configuredStateForOutput);
        }

        self::$saveAllOutputToThisVariable = &$this->saveOutputToThisVariable;

        return $this;
    }

    /**
     * Reset all custom settings.
     */
    public function resetToDefaults()
    {
        if (self::$defaultSettings) {
            Sage::settings(new SageSettings());
        }
    }

    public function displaySimpleHtml($data = null)
    {
        $stateBackup = $this->saveSettingsPt1();
        Sage::enabled(Sage::MODE_PLAIN_HTML);
        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        if (func_num_args()) {
            call_user_func_array(array($this, 'dump'), func_get_args()); # PROCEDURE: dump
        }

        return $this;
    }

    public function displayPlainText($data = null)
    {
        $stateBackup = $this->saveSettingsPt1();
        Sage::enabled(Sage::MODE_TEXT_ONLY);
        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        if (func_num_args()) {
            $params = func_get_args();
            call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
        }

        return $this;
    }

    public function displaySimplest($data = null)
    {
        if ($this->alreadyDumped) {
            $this->dump('Sage usage warning: Your code called displaySimplest() AFTER the dump!');

            return $this;
        }

        return call_user_func_array(array($this, 'displayPlainText'), func_get_args());
    }

    /**
     * Makes the output be RICH-HTML and all nodes will be expanded.
     */
    public function displayRichExpanded($data = null)
    {
        if ($this->alreadyDumped) {
            $this->dump('Sage usage warning: Your code called displayRichExpanded() AFTER the dump!');

            return $this;
        }

        $stateBackup = $this->saveSettingsPt1();
        Sage::settings()
            ->expandedByDefault(true)
            ->enabled(Sage::MODE_RICH)
        ;
        $this->saveSettingsPt2($stateBackup);

        if (func_num_args()) {
            call_user_func_array(array($this, 'dump'), func_get_args()); # PROCEDURE: dump
        }

        return $this;
    }

    /**
     * Makes the output be RICH-HTML.
     */
    public function displayRichHtml($data = null)
    {
        $stateBackup = $this->saveSettingsPt1();
        Sage::enabled(Sage::MODE_RICH);
        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        if (func_num_args()) {
            $params = func_get_args();
            call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
        }

        return $this;
    }

    /**
     * Laravel helper. Will dump all DB queries from this point forward.
     */
    public function showEloquentQueries()
    {
        // maintain PHP5.1+ compatibility
        if (! SageHelper::php53orLater()) {
            return $this;
        }

        Sage::settings()->addAlias(__CLASS__ . '::' . __FUNCTION__);
        Sage::dump('Started showing Eloquent queries');

        $queryNumber = 0;
        DB::listen(function($query) use (&$queryNumber) {
            $state = Sage::saveState();
            Sage::settings()->displayCalledFrom(false);

            $callee = 'unknown';
            $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            foreach ($trace as $step) {
                if (array_key_exists('file', $step) && strpos($step['file'], '/vendor/laravel/') === false) {
                    $callee = $step['file'];
                    if (array_key_exists('line', $step)) {
                        $callee .= ':' . $step['line'];
                    }
                    break;
                }
            }

            $substituteBindings = function($sql, $bindings) {
                foreach ($bindings as $binding) {
                    if ($binding instanceof DateTime) {
                        $bindings[] = $binding->format('Y-m-d H:i:s');
                        continue;
                    }

                    if ($binding === null) {
                        $binding = 'NULL';
                    }

                    $bindings[] = (string) $binding;
                }

                return sprintf(str_replace('?', "'%s'", $sql), ...$bindings);
            };

            $EloquentQuery = array(
                '#'             => $queryNumber++,
                'sql'           => PHP_EOL
                    . SageSqlFormatter::format($substituteBindings($query->sql, $query->bindings), false),
                'plain_sql'     => PHP_EOL . $query->sql,
                'bindings'      => $query->bindings,
                'called_from'   => $callee,
                'duration_in_s' => $query->time / 1000,
                'connection'    => $query->connectionName,
            );
            Sage::dump($EloquentQuery);

            Sage::saveState($state);
        });

        return $this;
    }

    # region Save output

    /**
     * Sage output will not be echoed, but stored into the passed variable by reference.
     */
    public function saveOutputTo(&$variable)
    {
        $stateBackup = $this->saveSettingsPt1();
        Sage::settings()->returnOutput(true);
        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        if ($variable === null) {
            $variable = '';
        }

        if ($this->isSettingDefaults) {
            self::$saveAllOutputToThisVariable = &$variable;
        } else {
            $this->saveOutputToThisVariable = &$variable;
        }

        return $this;
    }

    /**
     * Will save rich output to sage.html
     *
     * @param string $dirName __DIR__ by default
     */
    public function saveOutputAsFile($dirName = null)
    {
        $stateBackup = $this->saveSettingsPt1();

        $saveTo = $dirName;
        if ($saveTo === null) {
            $debugBacktrace = debug_backtrace(2, 1);
            $file           = isset($debugBacktrace[0]['file']) ? $debugBacktrace[0]['file'] : '';
            $dir            = dirname($file);
            $saveTo         = $dir;
        }

        if (is_dir($saveTo)) {
            $saveTo = $saveTo . DIRECTORY_SEPARATOR . 'sage.html';
        }
        Sage::settings()
            ->outputToFile($saveTo)
            ->enabled(Sage::MODE_RICH)
        ;

        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        return $this;
    }

    /**
     * Will save rich output to sage.html in directory of the file it was called from
     */
    public function writeToFileInCurrentDir($data = null)
    {
        $stateBackup = $this->saveSettingsPt1();

        $debugBacktrace = debug_backtrace(2, 1);
        $file           = isset($debugBacktrace[0]['file']) ? $debugBacktrace[0]['file'] : '';
        $dir            = dirname($file);
        Sage::settings()
            ->outputToFile($dir . DIRECTORY_SEPARATOR . 'sage.html')
            ->enabled(Sage::MODE_RICH)
        ;

        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        if (func_num_args()) {
            $params = func_get_args();
            call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
        }

        return $this;
    }

    # region Settings management

    /**
     * This will "magically" preserve setting for the entire chained()->call() and revert when chain invocation is over.
     *
     * 1. Run this
     * 2. Change settings as needed
     * 3. Run saveSettingsPt2($stateBackup)
     * 4. Dump ard/or return.
     *
     * Example:
     * ```
     *  $stateBackup = $this->saveSettingsPt1();
     *  Sage::enabled(Sage::MODE_PLAIN_HTML);
     *  $this->saveSettingsPt2($stateBackup);
     *
     *  call_user_func_array(array($this, 'dump'), func_get_args());
     * ```
     *
     * No need to do anything else.
     *
     *
     * @return array|null
     */
    private function saveSettingsPt1()
    {
        $stateBackup = null;
        if (! $this->isSettingDefaults) {# PROCEDURE: save sage settings
            $stateBackup = Sage::saveState();
            if ($this->configuredStateForOutput) {
                Sage::saveState($this->configuredStateForOutput);
            }
        }

        return $stateBackup;
    }

    private function saveSettingsPt2($stateBackup)
    {
        if (! $this->isSettingDefaults) {
            $this->configuredStateForOutput = Sage::saveState();
            Sage::saveState($stateBackup);
        } # END PROCEDURE: save sage settings
    }
}
