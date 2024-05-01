<?php

/**
 * @internal
 */
class SageDynamicFacade
{
    /**
     * @var mixed|string
     */
    private $isSettingDefaults = false;
    private $configuredStateForOutput = array();

    /**
     * Stores reference to variable if user requested to save it instead of echo.
     */
    private $saveOutputToThisVariable = null;
    private static $saveAllOutputToVar = null;

    public function dump($data = null)
    {
        $params = func_get_args();

        if ($this->configuredStateForOutput) {
            $stateBackup = Sage::saveState();
            Sage::saveState($this->configuredStateForOutput);
        }

        if ($this->saveOutputToThisVariable !== null || self::$saveAllOutputToVar !== null) {
            $output = call_user_func_array(array('Sage', 'dump'), $params); # PROCEDURE: dump

            if ($this->saveOutputToThisVariable !== null) {
                $this->saveOutputToThisVariable = $output;
            }
            if (self::$saveAllOutputToVar !== null) {
                self::$saveAllOutputToVar .= $output;
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

    public function setDefaults()
    {
        $this->isSettingDefaults = true;

        return $this;
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
            self::$saveAllOutputToVar = &$variable;
        } else {
            $this->saveOutputToThisVariable = &$variable;
        }

        return $this;
    }


}
