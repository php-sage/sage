<?php

/**
 * @internal
 */
class SageInvoker
{
    /**
     * @var array $parameterNames parameter names/expressions which were passed to be dumped
     */
    public $parameterNames = array();
    /**
     * @var array $miniTrace trace only up to sage, and only file, line, class, function
     */
    public $trace = array();
    public $sageMethodCalled = '';

    /**
     * Fetches the public properties defined above.
     *
     * @param array $rawTrace
     *
     * @return self
     */
    public static function from($rawTrace)
    {
        $self                   = new self();
        $insideTemplateDetected = null;

        // go from back of trace forward to find first occurrence of call to Sage or its wrappers
        while ($step = array_pop($rawTrace)) {
            if (
                isset($step['args'][0])
                && is_string($step['args'][0])
                && substr($step['args'][0], -strlen('.blade.php')) === '.blade.php'
            ) {
                $insideTemplateDetected = $step['args'][0];
            }

            if (isset($step['file'], $step['line'])) {
                unset($step['object'], $step['args']);
                array_unshift($self->trace, $step);
            }

            if (SageHelper::isStepInternal($step)) {
                $self->sageMethodCalled = strtolower($step['function']);
                break;
            }
        }

        if (! isset($step['file']) || ! is_readable($step['file'])) {
            return $self;
        }

        SageHelper::detectProjectRoot($self->getUserLandInvoker('file'));

        if (SageHelper::php82orLater()) {
            $self->parameterNames = SageParameterNameParser::fetch($self->trace[0], $self->sageMethodCalled);
        } else {
            $self->parameterNames = SageParameterNameParserLegacy::fetch($self->trace[0]);
        }

        if ($insideTemplateDetected) {
            $self->trace[1]['file'] = $insideTemplateDetected;
            $self->trace[1]['line'] = null;
        }

        return $self;
    }

    /**
     * Gets the trace step where Sage was invoked.
     *
     * @param 'all'|'file'|'line'|'function'|'class' $whichElement fetch specific element not the whole step
     *
     * @return null|array|string|int trace step where sage was called from
     */
    public function getUserLandInvoker($whichElement = 'all')
    {
        $step = count($this->trace) > 1 ? $this->trace[1] : array();
        if ($whichElement === 'all') {
            return $step;
        }

        if (array_key_exists($whichElement, $step)) {
            return $step[$whichElement];
        }

        return null;
    }

    /**
     * @param int $parameterIndex
     *
     * @return null|string
     */
    public function getParameterName($parameterIndex)
    {
        // when the dump arguments take long to generate output, user might have changed the file and
        // Sage might not parse the arguments correctly, so check if names are set and while the
        // displayed names might be wrong, at least don't throw an error
        $name = array_key_exists($parameterIndex, $this->parameterNames)
            ? $this->parameterNames[$parameterIndex]
            : '???';

        if (strlen($name) > 60) {
            $name =
                SageHelper::substr($name, 0, 27)
                . '...'
                . SageHelper::substr($name, -28, null);
        }

        return $name;
    }

    private static function detectProjectRoot($calledFromFile)
    {
        // Find common path with Sage dir
        self::$projectRootDir = '';

        if (! $calledFromFile) {
            return;
        }

        $sagePathParts = explode('/', str_replace('\\', '/', SAGE_DIR));
        $filePathParts = explode('/', $calledFromFile);
        foreach ($filePathParts as $i => $filePart) {
            if (! isset($sagePathParts[$i]) || $sagePathParts[$i] !== $filePart) {
                break;
            }

            self::$projectRootDir .= $filePart . '/';
        }
    }

}
