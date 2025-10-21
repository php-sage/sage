<?php

/** @internal */
class SageParsedVariableContents
{
    /** @var SageHtmlable|string */
    public $name = '';

    /**
     *  Depending on self::$displayType can be either:
     *  - string - escaped and put into <pre>;
     *  - SageHtmlable - contents put into <pre> without escaping;
     *  - SageParsedVariable[]|array{name: string, value: string} - depending on the contentType will be displayed in
     *    plain text rows or full dump rows
     *  - mixed (if CONTENT_TYPE_DUMP) will be just dumped verbatim
     *
     * @var string|SageHtmlable|SageParsedVariable[]|SageTrace|array{name: string, value: string}|mixed
     */
    public $contents = null;

    /**
     * @var self::STRING|self::PLAIN_TEXT_ROWS|self::RICH_ROWS|self::DUMP
     */
    public $displayType;

    /**
     * String, or html if you wrap the string in SageHtmlable
     */
    const STRING = 'string';
    /**
     * Will produce:
     *
     *   NameOfRow1: value
     *   row2      : value2
     */
    const PLAIN_TEXT_ROWS = 'plain-rows';
    /**
     * Will produce variable-dump rows
     */
    const RICH_ROWS = 'rich-rows';
    /**
     * Will just dump whatever you pass as the single item as content
     */
    const DUMP = 'dump';
    /**
     * Will dump whatever you pass but unwrap the topmost element
     */
    const DUMP_WITHOUT_TOP_PARENT = 'dump-unwrapped';
    /**
     * Will display passed trace
     */
    const TRACE = 'trave';

    /**
     * todo legacy constructor @param int $contentType
     *
     * @param string $name
     * @param mixed $content
     *
     * @see SageHtmlable
     */
    public function __construct($contentType, $name = '', $content = null)
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
            throw new SageLogicException('Cannot add rows to the current view type', get_defined_vars());
        }

        if ($this->contents === null) {
            $this->contents = array();
        }

        if ($this->displayType === self::RICH_ROWS) {
            if (! $content instanceof SageParsedVariable) {
                $content           = SageParser::parse($content, $name);
                $content->operator = $operator;
            }

            $this->contents[] = $content;
        } elseif ($this->displayType === self::PLAIN_TEXT_ROWS) {
            if (! is_string($content)) {
                throw new SageLogicException('Can only use text in this mode', $content);
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
                throw new SageLogicException('Please use addRow for this type of view', $content);
            }

            foreach ($content as $k => $row) {
                $this->addRow($row, $k, '=>');
            }

            return $this;
        }

        if (
            $this->displayType === self::DUMP
            || $this->displayType === self::DUMP_WITHOUT_TOP_PARENT
        ) {
            $content = SageParser::parse($content, $this->name);

            // if ($this->displayType === self::DUMP_WITHOUT_TOP_PARENT) {
            //     // todo a mode to dump just the first extended view.
            //     $content = reset$content->alternativeViews;
            // }
        }

        $this->contents = $content;

        return $this;
    }

    /**
     * @return bool
     */
    private function canHaveRows()
    {
        return $this->displayType === self::PLAIN_TEXT_ROWS
            || $this->displayType === self::RICH_ROWS;
    }
}
