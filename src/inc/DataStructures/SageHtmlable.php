<?php

/**
 * @internal
 */
class SageHtmlable
{
    private $html;

    public function SageHtmlable($html = '')
    {
        $this->html = $html;
    }

    public function __construct($html = '')
    {
        $this->SageHtmlable($html);
    }

    public function toHtml()
    {
        return $this->html;
    }

    public function isEmpty()
    {
        return $this->html === '';
    }

    public function isNotEmpty()
    {
        return ! $this->isEmpty();
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}
