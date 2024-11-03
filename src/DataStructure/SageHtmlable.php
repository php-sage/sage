<?php

/**
 * @internal wrap string so it does not get escaped when passed to SageHelper::esc()
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
