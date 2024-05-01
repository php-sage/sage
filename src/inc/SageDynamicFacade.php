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

        return call_user_func_array(array($this, 'dump'), $params);
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
        if (! $this->isSettingDefaults) { # PROCEDURE: save sage settings
            $stateBackup = Sage::saveState();
            if ($this->configuredStateForOutput) {
                Sage::saveState($this->configuredStateForOutput);
            }
        }
        Sage::enabled(Sage::MODE_PLAIN);
        if (! $this->isSettingDefaults) {
            $this->configuredStateForOutput = Sage::saveState();
            Sage::saveState($stateBackup);
        } # END PROCEDURE: save sage settings

        if (func_num_args()) {
            $params = func_get_args();
            call_user_func_array(array($this, 'dump'), $params); # PROCEDURE: dump
        }

        return $this;
    }

    public function displayPlainText($data = null)
    {
        if (! $this->isSettingDefaults) { # PROCEDURE: save sage settings
            $stateBackup = Sage::saveState();
            if ($this->configuredStateForOutput) {
                Sage::saveState($this->configuredStateForOutput);
            }
        }
        Sage::enabled(Sage::MODE_TEXT_ONLY);
        if (! $this->isSettingDefaults) {
            $this->configuredStateForOutput = Sage::saveState();
            Sage::saveState($stateBackup);
        } # END PROCEDURE: save sage settings

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
        if (! $this->isSettingDefaults) { # PROCEDURE: save sage settings
            $stateBackup = Sage::saveState();
            if ($this->configuredStateForOutput) {
                Sage::saveState($this->configuredStateForOutput);
            }
        }
        Sage::$expandedByDefault = true;
        Sage::enabled(Sage::MODE_RICH);
        if (! $this->isSettingDefaults) {
            $this->configuredStateForOutput = Sage::saveState();
            Sage::saveState($stateBackup);
        } # END PROCEDURE: save sage settings

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
        if (! $this->isSettingDefaults) { # PROCEDURE: save sage settings
            $stateBackup = Sage::saveState();
            if ($this->configuredStateForOutput) {
                Sage::saveState($this->configuredStateForOutput);
            }
        }
        Sage::enabled(Sage::MODE_RICH);
        if (! $this->isSettingDefaults) {
            $this->configuredStateForOutput = Sage::saveState();
            Sage::saveState($stateBackup);
        } # END PROCEDURE: save sage settings

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
        if (! $this->isSettingDefaults) {# PROCEDURE: save sage settings
            $stateBackup = Sage::saveState();
            if ($this->configuredStateForOutput) {
                Sage::saveState($this->configuredStateForOutput);
            }
        }
        Sage::$returnOutput = true;
        if (! $this->isSettingDefaults) {
            $this->configuredStateForOutput = Sage::saveState();
            Sage::saveState($stateBackup);
        } # END PROCEDURE: save sage settings

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
}
