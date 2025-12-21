<?php

/**
 * Wraps strings so it does not get escaped when passed to SageHelper::esc()
 *
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

    public function __toString()
    {
        return $this->html;
    }
}
