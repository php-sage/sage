<?php

/** @internal */
class SageVariableExtendedView
{
    /** @var string */
    public $name;

    /**
     * @var string|SageHtmlable|SageAlternativeViewRow[]|mixed - can be one of
     *  string - escaped and put into <pre>;
     *  SageHtmlable - contents put into <pre> without escaping;
     *  SageAlternativeViewRow - depending on the contentType will be displayed in plain text rows or full dump rows
     *  mixed (if CONTENT_TYPE_DUMP) will be just dumped verbatim
     */
    public $contents = null;

    /**
     * @var int
     */
    private $contentType;

    const CONTENT_TYPE_STRING = 0;
    const CONTENT_TYPE_PLAIN_TEXT_ROWS = 1;
    const CONTENT_TYPE_RICH_ROWS = 2;
    const CONTENT_TYPE_DUMP = 3;

    public function __construct(int $contentType, string $name = '', $content = null)
    {
        $this->contentType = $contentType;
        if ($name) {
            $this->setName($name);
        }
        if ($content) {
            $this->setContent($content);
        }
    }

    /**
     * Name required for alternative view
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function addRow($content, string $name = '')
    {
        if (! $this->canHaveRows()) {
            throw new SageLogicException('Cannot add rows to the current view type');
        }

        if ($content instanceof SageVariableData) {
            if ($name) {
                throw new SageLogicException('If passing parsed variable, set its name instead of passing it here!');
            }
            $name = $content->name;
        }

        if ($this->contents === null) {
            $this->contents = array();
        }

        $this->contents[] = new SageAlternativeViewRow($name, $content);

        return $this;
    }

    public function setContent($content)
    {
        if ($this->canHaveRows()) {
            throw new SageLogicException('Please use addRow for this type of view');
        }

        $this->contents = $content;

        return $this;
    }

    public function isEmpty()
    {
        return $this->contents === null;
    }

    private function canHaveRows()
    {
        return $this->contentType === self::CONTENT_TYPE_PLAIN_TEXT_ROWS
            || $this->contentType === self::CONTENT_TYPE_RICH_ROWS;
    }
}
