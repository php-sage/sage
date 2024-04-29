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
    private $stateBackup = array();
    private $stateForOutput = array();

    private $saveOutputToVariableRef = null;
    private static $saveAllOutputToVar = null;

    public function dump()
    {
        $params = func_get_args();

        if ($this->stateForOutput) {
            $stateBackup = Sage::saveState();
            Sage::saveState($this->stateForOutput);
        }

        if ($this->saveOutputToVariableRef !== null || self::$saveAllOutputToVar !== null) {
            $output = call_user_func_array(array('Sage', 'dump'), $params); # PROCEDURE: dump
            if ($this->saveOutputToVariableRef !== null) {
                $this->saveOutputToVariableRef = $output;
            }
            if (self::$saveAllOutputToVar !== null) {
                self::$saveAllOutputToVar .= $output;
            }
        } else {
            call_user_func_array(array('Sage', 'dump'), $params); # PROCEDURE: dump
        }

        if ($this->stateForOutput) {
            Sage::saveState($stateBackup);
        }

        return $this;
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

    /**
     * Makes the output be RICH-HTML and all nodes will be expanded.
     */
    public function expandAll($data = null) // todo what will FNA across versions return?
    {
        if (! $this->isSettingDefaults) { # PROCEDURE: save sage settings
            $stateBackup = Sage::saveState();
            if ($this->stateForOutput) {
                Sage::saveState($this->stateForOutput);
            }
        }
        Sage::$expandedByDefault = true;
        Sage::enabled(Sage::MODE_RICH);
        if (! $this->isSettingDefaults) {
            $this->stateForOutput = Sage::saveState();
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
            if ($this->stateForOutput) {
                Sage::saveState($this->stateForOutput);
            }
        }
        Sage::$returnOutput = true;
        if (! $this->isSettingDefaults) {
            $this->stateForOutput = Sage::saveState();
            Sage::saveState($stateBackup);
        } # END PROCEDURE: save sage settings

        if ($variable === null) {
            $variable = '';
        }

        if ($this->isSettingDefaults) {
            self::$saveAllOutputToVar = &$variable;
        } else {
            $this->saveOutputToVariableRef = &$variable;
        }

        return $this;
    }
}
