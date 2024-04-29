<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */

class SageVariableData
{
    /** @var string */
    public $type;
    /** @var string */
    public $access;
    /** @var string */
    public $name;
    /** @var string */
    public $operator;
    /** @var int */
    public $size;
    /** @var array|string full variable representation */
    public $extendedValue;
    /** @var string short inline value */
    public $value;

    /** @var array extra views of the same variable data used in rich view. Keys are tab names, values is content */
    private $alternativeRepresentations = array();

    /**
     * @param string       $name
     * @param string|array $value
     *
     * @return void
     */
    public function addTabToView($originalVariable, $tabName, $value)
    {
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
        $prepared = array();

        if (! empty($this->extendedValue)) {
            $prepared['Contents'] = $this->extendedValue;
        }
        if (! empty($this->alternativeRepresentations)) {
            $prepared = array_merge($prepared, $this->alternativeRepresentations);
        }

        return $prepared;
    }
}
