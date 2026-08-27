<?php

/**
 * {@see SageInstance::enabledParsers} to enable/disable.
 *
 * @internal
 */
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

        $result->addTabView__String(
            $this->fetchSource($reflection),
            'Source definition'
        );

        if (! SageHelper::isRichMode()) {
            return $result;
        }

        $internalsTab = new SageParsedVariableContents(
            SageParsedVariableContents::RICH_ROWS,
            'Closure Internals'
        );

        if (method_exists($reflection, 'getClosureThis') && $val = $reflection->getClosureThis()) {
            $internalsTab->addRow($val, '$this');
        }

        if ($val = $reflection->getStaticVariables()) {
            foreach ($val as $k => $item) {
                $internalsTab->addRow($item, '$' . $k);
            }
        }
        $result->addTabView($internalsTab);

        if ($reflection->getFileName()) {
            $result->value = SageHelper::getIdeLink($reflection->getFileName(), $reflection->getStartLine());
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
        $filename    = $reflection->getFileName();
        $file        = new SplFileObject($filename);
        $startLine   = $reflection->getStartLine();
        $endLine     = $reflection->getEndLine();
        $src         = '# ' . SageHelper::shortenPath($filename) . ':' . $startLine . "\n";
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
