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

    /**
     * Costructor for ancient PHP versions support
     */
    public function SageDynamicFacade()
    {
        //        if (! in_array('SageDynamicFacade::*', Sage::$aliases, true)) {
        //            Sage::$aliases[] = 'SageDynamicFacade::*';
        //        }
    }

    public function __construct()
    {
        $this->SageDynamicFacade();
    }

    public function dump($data = null)
    {
        $params = func_get_args();

        if ($this->configuredStateForOutput) {
            $stateBackup = Sage::saveState();
            Sage::saveState($this->configuredStateForOutput);
        }

        if ($this->saveOutputToThisVariable !== null || self::$saveAllOutputToThisVariable !== null) {
            $output = call_user_func_array(array('Sage', 'dump'), $params); # PROCEDURE: dump

            if (self::$saveAllOutputToThisVariable !== null) {
                self::$saveAllOutputToThisVariable .= $output;
            } elseif ($this->saveOutputToThisVariable !== null) {
                $this->saveOutputToThisVariable = $output;
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
     * Shorthand for dump()
     */
    public function d($data = null)
    {
        $params = func_get_args();

        return call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
    }

    public function dd($data = null)
    {
        $params = func_get_args();

        call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump

        die;
    }

    /**
     * Display trace
     */
    public function trace($onlyFilePaths = false)
    {
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
        Sage::dump(2);
    }

    /**
     * Laravel helper. Will dump all DB queries from this point forward.
     */
    public function showEloquentQueries()
    {
        Sage::showEloquentQueries();

        return $this;
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
            Sage::saveState(self::$defaultSettings);
        }
    }

    public function displaySimpleHtml($data = null)
    {
        $stateBackup = $this->saveSettingsPt1();
        Sage::enabled(Sage::MODE_PLAIN_HTML);
        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        if (func_num_args()) {
            $params = func_get_args();
            call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
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
        $params = func_get_args();

        return call_user_func_array(array($this, 'displayPlainText'), $params);
    }

    /**
     * Makes the output be RICH-HTML and all nodes will be expanded.
     */
    public function displayRichExpanded($data = null) // todo what will func_num_args across PHP versions return?
    {
        $stateBackup             = $this->saveSettingsPt1();
        Sage::$expandedByDefault = true;
        Sage::enabled(Sage::MODE_RICH);
        $this->saveSettingsPt2($stateBackup);

        if (func_num_args()) {
            $params = func_get_args();
            call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
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
     * Sage output will not be echoed, but stored into the passed variable by reference.
     */
    public function saveOutputTo(&$variable)
    {
        $stateBackup        = $this->saveSettingsPt1();
        Sage::$returnOutput = true;
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
            $file   = debug_backtrace(2)[0]['file'] ?? '';
            $dir    = dirname($file);
            $saveTo = $dir;
        }

        if (is_dir($saveTo)) {
            $saveTo = $saveTo . DIRECTORY_SEPARATOR . 'sage.html';
        }
        Sage::$outputFile = $saveTo;
        Sage::enabled(Sage::MODE_RICH);

        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        return $this;
    }

    /**
     * Will save rich output to sage.html in directory of the file it was called from
     */
    public function writeToFileInCurrentDir($data = null)
    {
        $stateBackup = $this->saveSettingsPt1();

        $file             = debug_backtrace(2)[0]['file'] ?? '';
        $dir              = dirname($file);
        Sage::$outputFile = $dir . DIRECTORY_SEPARATOR . 'sage.html';
        Sage::enabled(Sage::MODE_RICH);

        $this->saveSettingsPt2($stateBackup); # END PROCEDURE: save sage settings

        if (func_num_args()) {
            $params = func_get_args();
            call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
        }

        return $this;
    }

    /**
     * Change settings as needed and immediately run saveSettingsPt2($stateBackup)
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
