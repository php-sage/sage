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

        $reflection = new ReflectionFunction($variable);

        $result       = new SageParsedVariable();
        $result->type = 'Closure';

        $result->extendedView = new SageParsedVariableContents(
            SageParsedVariableContents::CONTENT_TYPE_STRING,
            'Source (beta!)',
            $this->fetchSource($reflection)
        );

        if (! SageHelper::isRichMode()) {
            return $result;
        }

        $internalsTab = new SageParsedVariableContents(
            SageParsedVariableContents::CONTENT_TYPE_RICH_ROWS,
            'Closure Internals'
        );

        if (method_exists($reflection, 'getClosureThis') && $val = $reflection->getClosureThis()) {
            $internalsTab->addRow(SageParser::parse($val, '$this'));
        }

        if ($val = $reflection->getStaticVariables()) {
            foreach ($val as $k => $item) {
                $internalsTab->addRow(SageParser::parse($item, '$' . $k));
            }
        }
        // if (method_exists($reflection, 'getClosureUsedVariables') && $val = $reflection->getClosureUsedVariables()) {
        //     foreach ($val as $k => $item) {
        //         $internalsTab->addRow(SageParser::parse($item, $k . ' (use)'));
        //     }
        // }

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
        //        $src    = 'function (';
        //        $params = array();
        //
        //        foreach ($reflection->getParameters() as $p) {
        //            $string = $this->getParameterType($p);
        //
        //            if ($p->isPassedByReference()) {
        //                $string .= '&';
        //            }
        //            $string .= '$' . $p->name;
        //            if (method_exists($p, 'isVariadic') && $p->isVariadic()) {
        //                $string = '...' . $string;
        //            } elseif ($p->isOptional()) {
        //                $string .= ' = ' . var_export($p->getDefaultValue(), true);
        //            }
        //            $params[] = $string;
        //        }
        //        $src .= implode(', ', $params) . ') ';
        //
        //        if (method_exists($reflection, 'getClosureUsedVariables') && $val = $reflection->getClosureUsedVariables()) {
        //            $src .= 'use ($';
        //            $src .= implode(', $', array_keys($val));
        //            $src .= ') ';
        //        }
        //        $src .= '{' . PHP_EOL;

        $src         = '';
        $file        = new SplFileObject($reflection->getFileName());
        $startLine   = $reflection->getStartLine();
        $endLine     = $reflection->getEndLine();
        $currentLine = $startLine;
        $file->seek($startLine - 1);
        while (! $file->eof()) {
            if ($currentLine > $endLine) {
                $file = null;
                break;
            }

            $line = $file->fgets();
            $currentLine++;

            //            if ($currentLine === $startLine) {
            //                preg_match('/^(\s+)/', $line, $whiteSpacePrefix);
            //                if (array_key_exists(1, $whiteSpacePrefix)) {
            //                    $src = $whiteSpacePrefix[1] . $src;
            //                }
            //            }
            $src .= $line;
        }

        return new SageHtmlable(SageHelper::esc($src));
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
