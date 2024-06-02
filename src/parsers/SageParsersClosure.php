<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersClosure implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable, $varData)
    {
        if (! $variable instanceof Closure) {
            return false;
        }

        $varData->type = 'Closure';
        $reflection    = new ReflectionFunction($variable);

        if (! SageHelper::isRichMode()) {
            $varData->extendedValue = [
                'source' => $this->fetchSource($reflection),
            ];

            return;
        }

        $uses = [];
        if ($val = $reflection->getStaticVariables()) {
            foreach ($val as $k => $item) {
                $uses[] = SageParser::process($item, $k . ' (static)');
            }
        }
        if (method_exists($reflection, 'getClousureThis') && $val = $reflection->getClosureThis()) {
            $uses[] = SageParser::process($val, '$this');
        }
        if (method_exists($reflection, 'getClosureUsedVariables') && $val = $reflection->getClosureUsedVariables()) {
            foreach ($val as $k => $item) {
                $uses[] = SageParser::process($item, $k . ' (use)');
            }
        }
        if (! empty($uses)) {
            $varData->addTabToView($variable, 'Closure Internals', $uses);
        }

        $varData->addTabToView($variable, 'Source', $this->fetchSource($reflection));

        if ($reflection->getFileName()) {
            $varData->value = SageHelper::ideLink($reflection->getFileName(), $reflection->getStartLine());
        }
    }

    /**
     * @param ReflectionFunction $reflection
     *
     * @return string
     */
    private function fetchSource($reflection)
    {
        $src    = 'function (';
        $params = [];
        foreach ($reflection->getParameters() as $p) {
            $s = '';
            $s .= $this->getParameterType($p);
            $s = ' ';

            if ($p->isPassedByReference()) {
                $s .= '&';
            }
            $s .= '$' . $p->name;
            if ($p->isOptional()) {
                $s .= ' = ' . var_export($p->getDefaultValue(), true);
            }
            $params[] = $s;
        }
        $src .= implode(', ', $params);
        $src .= ') ';
        if (method_exists($reflection, 'getClosureUsedVariables') && $val = $reflection->getClosureUsedVariables()) {
            $src .= ') use (';
            $src .= implode(', ', array_keys($val));
            $src .= ') ';
        }
        $src   .= '{' . PHP_EOL;
        $lines = file($reflection->getFileName());

        $file        = new SplFileObject($reflection->getFileName());
        $currentLine = 0;
        $startLine   = $reflection->getStartLine();
        $endLine     = $reflection->getEndLine();
        while (! $file->eof()) {
            $currentLine++;
            if ($currentLine < $startLine) {
                continue;
            }
            if ($currentLine > $endLine) {
                $file = null;
                break;
            }

            $line = $file->fgets();

            if ($currentLine === $startLine) {
                preg_match('/^(\s+)/', $line, $whiteSpacePrefix);
                if (array_key_exists(1, $whiteSpacePrefix)) {
                    $src = $whiteSpacePrefix[1] . $src;
                }
            }
            $src .= $line;
        }

        return $src;
    }

    /**
     * @param ReflectionParameter $param
     *
     * @return string
     */
    function getParameterType($param)
    {
        // eg. "Parameter #0 [ <required> string $string ]"
        $toString = $param->__toString();

        preg_match('/\[\s<\w+>\s(\w+)/', $toString, $matches);

        return isset($matches[1]) ? $matches[1] : '';
    }
}
