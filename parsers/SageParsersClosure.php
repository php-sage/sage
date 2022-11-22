<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersClosure extends SageParser
{
    public static $replacesAllOtherParsers = true;

    protected static function parse(&$variable, $varData)
    {
        if (! $variable instanceof Closure) {
            return false;
        }

        $varData->type = 'Closure';
        $reflection    = new ReflectionFunction($variable);

        $parameters = array();
        foreach ($reflection->getParameters() as $parameter) {
            $parameters = $parameter->name;
        }
        if (! empty($parameters)) {
            $varData->addTabToView($variable, 'Parameters', $parameters);
        }

        $uses = array();
        if ($val = $reflection->getStaticVariables()) {
            $uses = $val;
        }
        if (method_exists($reflection, 'getClousureThis') && $val = $reflection->getClosureThis()) {
            $uses[] = SageParser::process($val, '$this');
        }
        if (! empty($uses)) {
            $varData->addTabToView($variable, 'Parameters', $uses);
        }

        if ($reflection->getFileName()) {
            $varData->value = SageHelper::ideLink($reflection->getFileName(), $reflection->getStartLine());
        }
    }
}
