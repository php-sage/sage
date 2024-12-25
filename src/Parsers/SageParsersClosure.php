<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
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
            'Source definition',
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

        $src = rtrim($src, "\n");

        return new SageHtmlable(SageHelper::esc($src));
    }
}
