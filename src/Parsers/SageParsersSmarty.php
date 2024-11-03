<?php

/** @internal */
class SageParsersSmarty implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (! $variable instanceof Smarty
            || ! defined('Smarty::SMARTY_VERSION') // lower than 3.x
        ) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->name = 'Smarty v' . Smarty::SMARTY_VERSION;

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

        $result->addAlternativeView(
            new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_DUMP,
                'Assigned to view',
                $assigned
            )
        );
        $result->addAlternativeView(
            new SageParsedVariableContents(
                SageParsedVariableContents::CONTENT_TYPE_DUMP,
                'Assigned globally',
                $globalAssigns
            )
        );
        $result->addAlternativeView(
            (new SageParsedVariableContents(SageParsedVariableContents::CONTENT_TYPE_PLAIN_TEXT_ROWS, 'Configuration'))
                ->addRow(
                    'Compiled files stored in',
                    isset($variable->compile_dir)
                        ? $variable->compile_dir
                        : $variable->getCompileDir()
                )
        );

        return $result;
    }
}
