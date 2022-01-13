<?php

/**
 * @internal
 */
class SageParsersSmarty extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (! $variable instanceof Smarty
            || ! defined('Smarty::SMARTY_VERSION') // lower than 3.x
        ) {
            return false;
        }

        $varData->name = 'Smarty v'.Smarty::SMARTY_VERSION;

        $assigned = $globalAssigns = array();
        foreach ($variable->tpl_vars as $name => $var) {
            $assigned[$name] = $var->value;
        }
        foreach (Smarty::$global_tpl_vars as $name => $var) {
            if ($name === 'SCRIPT_NAME') {
                continue;
            }

            $globalAssigns[$name] = $var->value;
        }

        $varData->addTabToView('Assigned to view', $assigned);
        $varData->addTabToView('Assigned globally', $globalAssigns);
        $varData->addTabToView('Configuration', array(
                'Compiled files stored in' => isset($variable->compile_dir)
                    ? $variable->compile_dir
                    : $variable->getCompileDir(),
            )
        );

        return true;
    }
}
