<?php

/** @internal */
class SageParsedVariableContents
{
    /** @var SageHtmlable|string */
    public $name;

    /**
     *  Depending on self::$displayType can be either:
     *  - string - escaped and put into <pre>;
     *  - SageHtmlable - contents put into <pre> without escaping;
     *  - SageParsedVariable[]|array{name: string, value: string} - depending on the contentType will be displayed in
     *    plain text rows or full dump rows
     *  - mixed (if CONTENT_TYPE_DUMP) will be just dumped verbatim
     *
     * @var string|SageHtmlable|SageParsedVariable[]|array{name: string, value: string}|mixed
     */
    public $contents = null;

    /**
     * @var self::CONTENT_TYPE_STRING|self::CONTENT_TYPE_PLAIN_TEXT_ROWS|self::CONTENT_TYPE_RICH_ROWS|self::CONTENT_TYPE_DUMP
     */
    public $displayType;

    /**
     * String, or html if you wrap the string in SageHtmlable
     */
    const CONTENT_TYPE_STRING = 'string';
    /**
     * Will produce:
     *
     *   NameOfRow1: value
     *   row2      : value2
     */
    const CONTENT_TYPE_PLAIN_TEXT_ROWS = 'plain-rows';
    /**
     * Will produce variable-dump rows
     */
    const CONTENT_TYPE_RICH_ROWS = 'rich-rows';
    /**
     * Will just dump whatever you pass as the single item as content
     */
    const CONTENT_TYPE_DUMP = 'dump';
    /**
     * Will dump whatever you pass but unwrap the topmost element
     */
    const CONTENT_TYPE_DUMP_WITHOUT_TOP_PARENT = 'dump';

    /**
     * todo legacy constructor @param int $contentType
     *
     * @param string $name
     * @param mixed $content
     *
     * @see SageHtmlable
     */
    public function __construct($contentType, $name = 'Contents', $content = null)
    {
        $this->displayType = $contentType;
        if ($name) {
            $this->setName($name);
        }
        if ($content) {
            $this->setContent($content);
        }
    }

    /**
     * @param string $name required for alternative view
     *
     * @return SageParsedVariableContents
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param mixed $content
     * @param string $name
     *
     * @return $this
     */
    public function addRow($content, $name = '', $operator = ':')
    {
        if (! $this->canHaveRows()) {
            throw new SageLogicException('Cannot add rows to the current view type');
        }

        if ($this->contents === null) {
            $this->contents = array();
        }

        if ($this->displayType === self::CONTENT_TYPE_RICH_ROWS) {
            if (! $content instanceof SageParsedVariable) {
                $content           = SageParser::parse($content, $name);
                $content->operator = $operator;
            }

            $this->contents[] = $content;
        } elseif ($this->displayType === self::CONTENT_TYPE_PLAIN_TEXT_ROWS) {
            if (! is_string($content)) {
                throw new SageLogicException('Can only use text in this mode');
            }

            $this->contents[] = array(
                'name'  => $name,
                'value' => $content,
            );
        }

        return $this;
    }

    /**
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        if ($this->canHaveRows()) {
            if (! is_array($content)) {
                throw new SageLogicException('Please use addRow for this type of view');
            }

            foreach ($content as $row) {
                $this->addRow($row);
            }

            return $this;
        }

        if (
            $this->displayType === self::CONTENT_TYPE_DUMP
            || $this->displayType === self::CONTENT_TYPE_DUMP_WITHOUT_TOP_PARENT
        ) {
            $content = SageParser::parse($content, $this->name);
        }

        $this->contents = $content;

        return $this;
    }

    /**
     * @return bool
     */
    private function canHaveRows()
    {
        return $this->displayType === self::CONTENT_TYPE_PLAIN_TEXT_ROWS
            || $this->displayType === self::CONTENT_TYPE_RICH_ROWS;
    }
}
