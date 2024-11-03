<?php

/** @internal */
class SageParsedVariable
{
    // basics:
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var string */
    public $access;
    /** @var string */
    public $operator;
    /** @var int */
    public $size;
    /** @var string short inline value */
    public $value;
    /** @var string for exceptions like "depth limit", "blacklisted key", "recursion" */
    public $error = '';
    /** @var SageParsedTraceStep[] special case: dumped variable is a trace, it has a special representation */
    public $traceSteps = array();

    /**
     * Detailed (i.e. not-inline) information about the variable.
     *
     * The view is collapsed in Rich view, and if more alternative views exist, this one is put alongside them under
     * UI as tabs.
     *
     * The name will be HIDDEN if no alternatives exist (so don't put important information in the name!)
     *
     * @var SageParsedVariableContents
     */
    public $extendedView;

    /**
     * Holds alternative representations of the variable. For example, this would contain an array for a string
     * variable that's recognized as json.
     *
     * Each element is the same as $extendedView, but MUST have a name.
     *
     * @var SageParsedVariableContents[]
     */
    public $alternativeViews = array();

    public function addAlternativeView(SageParsedVariableContents $alternative)
    {
        if ($alternative->isEmpty()) {
            return;
        }

        $this->alternativeViews[] = $alternative;
    }

    /**
     * Special node type, represents an irrepresentable state, for example, a recursion.
     *
     * @param string $reason
     */
    public static function erroneous($reason)
    {
        $me        = new self();
        $me->error = $reason;

        return $me;
    }

    public function mergeFrom(self $from)
    {
        if (isset($from->name)) {
            $this->name = $from->name;
        }

        if (isset($from->type)) {
            $this->type = $from->type;
        }

        if (isset($from->access)) {
            $this->access = $from->access;
        }

        if (isset($from->operator)) {
            $this->operator = $from->operator;
        }

        if (isset($from->size)) {
            $this->size = $from->size;
        }

        if (isset($from->value)) {
            $this->value = $from->value;
        }

        if ($from->error) {
            $this->error = $from->error;
        }

        if ($from->traceSteps) {
            $this->traceSteps = $from->traceSteps;
        }

        if (isset($from->extendedView)) {
            $this->extendedView = $from->extendedView;
        }

        if ($from->alternativeViews) {
            $this->alternativeViews = array_merge($this->alternativeViews, $from->alternativeViews);
        }
    }

    public function getAllRepresentations()
    {
        $allRepresentations = array();
        if ($this->extendedView) {
            $allRepresentations[] = $this->extendedView;
        }

        return array_merge($allRepresentations, $this->alternativeViews);
    }
}
