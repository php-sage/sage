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
    private $alternativesPrepared = false;
    private $_prep = array();

    /**
     * @param string       $name
     * @param string|array $value
     *
     * @return void
     */
    public function addTabToView($name, $value)
    {
        $this->alternativeRepresentations[$name] = $value;
        $this->alternativesPrepared = false;
    }


    public function getAllRepresentations()
    {
        # if alternative displays exist, push extendedValue to their front and display it as one of alternatives
        if (! $this->alternativesPrepared) {
            $this->alternativesPrepared = true;
            $this->_prep = array();

            if (! empty($this->extendedValue)) {
                $this->_prep['Contents'] = $this->extendedValue;
            }
            if (! empty($this->alternativeRepresentations)) {
                $this->_prep = array_merge($this->_prep, $this->alternativeRepresentations);
            }
        }

        return $this->_prep;
    }
}
