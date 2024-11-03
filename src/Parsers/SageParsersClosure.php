<?php

/** @internal */
class SageParsersClosure implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (! $variable instanceof Closure) {
            return null;
        }

        $result       = new SageParsedVariable();
        $result->type = 'Closure';
        $sourceTab    = new SageParsedVariableContents(SageParsedVariableContents::CONTENT_TYPE_STRING, 'Source');

        $reflection = new ReflectionFunction($variable);
        $sourceTab->setContent($this->fetchSource($reflection));

        if (! SageHelper::isRichMode()) {
            $result->extendedView = $sourceTab;

            return $result;
        }

        $internalsTab = new SageParsedVariableContents(
            SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS,
            'Closure Internals'
        );
        if ($val = $reflection->getStaticVariables()) {
            foreach ($val as $k => $item) {
                $internalsTab->addRow(SageParser::parse($item, $k . ' (static)'));
            }
        }
        if (method_exists($reflection, 'getClosureThis') && $val = $reflection->getClosureThis()) {
            $internalsTab->addRow(SageParser::parse($val, '$this'));
        }
        if (method_exists($reflection, 'getClosureUsedVariables') && $val = $reflection->getClosureUsedVariables()) {
            foreach ($val as $k => $item) {
                $internalsTab->addRow(SageParser::parse($item, $k . ' (use)'));
            }
        }

        $result->addAlternativeView($internalsTab);

        if ($reflection->getFileName()) {
            $result->value = SageHelper::ideLink($reflection->getFileName(), $reflection->getStartLine());
        }

        return $result;
    }

    /**
     * @param ReflectionFunction $reflection
     *
     * @return SageHtmlable
     */
    private function fetchSource($reflection)
    {
        $src    = 'function (';
        $params = [];

        foreach ($reflection->getParameters() as $p) {
            $s = $this->getParameterType($p);;

            if ($p->isPassedByReference()) {
                $s .= '&';
            }
            $s .= '$' . $p->name;
            if ($p->isOptional()) {
                $s .= ' = ' . var_export($p->getDefaultValue(), true);
            }
            $params[] = $s;
        }
        $src .= implode(', ', $params) . ') ';

        if (method_exists($reflection, 'getClosureUsedVariables') && $val = $reflection->getClosureUsedVariables()) {
            $src .= ') use (';
            $src .= implode(', ', array_keys($val));
            $src .= ') ';
        }
        $src .= '{' . PHP_EOL;

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

        return new SageHtmlable(
            '# ' . SageHelper::ideLink($reflection->getFileName(), $reflection->getStartLine())
            . PHP_EOL
            . SageHelper::esc($src)
        );
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
