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

    /** @var SageRichViewTab[] */
    public $richViewTabs;

    /** @var string|array string is <pre> contents, array will be parsed further */
    public $fullContents;

    public function addTabToView($originalVariable, $tabName, $value)
    {
        if (Sage::enabled() !== Sage::MODE_RICH) {
            return;
        }

        if (is_array($value)) {
            if (! (reset($value) instanceof self)) {
                // convert to SageVariableData[]
                $value = SageParser::alternativesParse($originalVariable, $value);
            }
        } elseif (is_string($value)) {
            // do nothin'
        } else {
            // ERROR: incorrect parser
        }

        $this->alternativeRepresentations[$tabName] = $value;
    }

    public function getAllRepresentations()
    {
        # if alternative displays exist, push extendedValue to their front and display it as one of alternatives
        $result = array();

        if (! empty($this->extendedValue)) {
            $result['Contents'] = $this->extendedValue;
        }
        if (! empty($this->alternativeRepresentations)) {
            $result = array_merge($result, $this->alternativeRepresentations);
        }

        return $result;
    }
}
