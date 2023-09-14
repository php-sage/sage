<?php

/**
 * @internal
 */
class SageParsersSmarty implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable, $varData)
    {
        if (! $variable instanceof Smarty
            || ! defined('Smarty::SMARTY_VERSION') // lower than 3.x
        ) {
            return false;
        }

        $varData->name = 'Smarty v' . Smarty::SMARTY_VERSION;

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

        $varData->addTabToView($variable, 'Assigned to view', $assigned);
        $varData->addTabToView($variable, 'Assigned globally', $globalAssigns);
        $varData->addTabToView($variable, 'Configuration', array(
                'Compiled files stored in' => isset($variable->compile_dir)
                    ? $variable->compile_dir
                    : $variable->getCompileDir(),
            )
        );
    }
}
