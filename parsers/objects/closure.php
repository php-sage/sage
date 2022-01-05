<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class Sage_Objects_Closure extends SageObject
{
    public function parse(&$variable)
    {
        if (! $variable instanceof Closure) {
            return false;
        }

        $this->name = 'Closure';
        $reflection = new ReflectionFunction($variable);
        $ret = array(
            'Parameters' => array()
        );
        if ($val = $reflection->getParameters()) {
            foreach ($val as $parameter) {
                // todo http://php.net/manual/en/class.reflectionparameter.php
                $ret['Parameters'][] = $parameter->name;
            }

        }
        if ($val = $reflection->getStaticVariables()) {
            $ret['Uses'] = $val;
        }
        if (method_exists($reflection, 'getClousureThis') && $val = $reflection->getClosureThis()) {
            $ret['Uses']['$this'] = $val;
        }
        if ($val = $reflection->getFileName()) {
            $this->value = SageHelper::shortenPath($val).':'.$reflection->getStartLine();
        }

        return $ret;
    }
}
