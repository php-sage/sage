<?php

/** @internal */
class SageVariableData
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

    /**
     * Detailed information container.
     *
     * The view is collapsed in Rich view, and if more alternative views exist, it's put alongside them under UI tabs.
     *
     * The name will be HIDDEN if no alternatives exist (so don't put important information in the name!)
     *
     * @var SageVariableExtendedView
     */
    public $extendedView;

    /** @var SageVariableExtendedView[] each element is the same as $extendedView, only MUST have a name */
    public $alternativeViews;

    public function addAlternativeView(SageVariableExtendedView $details)
    {
        if ($details->isEmpty()) {
            return;
        }

        $this->alternativeViews[] = $details;
    }

    public static function erroneous(string $reason)
    {
        $me        = new self();
        $me->error = $reason;

        return $me;
    }
}
