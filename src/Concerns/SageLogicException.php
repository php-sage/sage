<?php

/**
 * @internal
 */
class SageLogicException extends LogicException
{
    protected $data;

    public function __construct($message, $debugData = null, $previous = null)
    {
        parent::__construct($message, 0, $previous);

        if ($debugData === null) {
            $debugData = array();
        }

        $this->data = is_array($debugData) ? $debugData : array($debugData);
    }
}
