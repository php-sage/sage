<?php

/** @internal */
class SageParsedVariable
{
    // basics:
    /** @var string|SageHtmlable */
    public $name;
    /** @var string|SageHtmlable */
    public $type;
    /** @var string|SageHtmlable */
    public $subtype;
    /** @var string|SageHtmlable */
    public $hash;
    /** @var string|SageHtmlable */
    public $access;
    /** @var string|SageHtmlable the '=>' for array, '->' for object, can be any string */
    public $operator;
    /** @var int */
    public $size;
    /** @var string|SageHtmlable short inline value */
    public $value;
    /** @var string|SageHtmlable for exceptions like "depth limit", "blacklisted key", "recursion" */
    public $error;
    /** @var null|SageTrace special case: the variable is a debug backtrace, it has a special representation */
    public $trace;

    /**
     * Holds alternative representations of the variable. For example, this would display an array for a string
     * that's recognized as json.
     *
     * @var SageParsedVariableContents[]
     */
    public $alternativeViews = array();

    /**
     * Will add an alternative representation to the variable (as a tab in Rich view).
     *
     * @param string $contents literal string (html) contents of the alternative.
     * @param string $name
     *
     * @return self
     */
    public function addTabView__String($contents = '', $name = '')
    {
        $this->addTabView(
            new SageParsedVariableContents(SageParsedVariableContents::STRING, $name, $contents)
        );

        return $this;
    }

    /**
     * Will add an alternative representation to the variable (as a tab in Rich view).
     *
     * Each row of the array will be dumped and displayed in rows.
     *
     * @param array $contents each row of the array will be dumped and displayed as "$key: dump($value)"
     * @param string $name
     *
     * @return self
     */
    public function addTabView__DumpedRows($contents = array(), $name = '')
    {
        $this->addTabView(
            new SageParsedVariableContents(SageParsedVariableContents::RICH_ROWS, $name, $contents)
        );

        return $this;
    }

    /**
     * Will add an alternative representation to the variable (as a tab in Rich view).
     *
     * Will produce:
     *
     *   NameOfRow1: value
     *   row2      : value2
     *
     * @param array<string, string> $contents the key => value rows will be output as plain text
     * @param string $name
     *
     * @return self
     */
    public function addTabView__PlaintextRows($contents = array(), $name = '')
    {
        $this->addTabView(
            new SageParsedVariableContents(SageParsedVariableContents::PLAIN_TEXT_ROWS, $name, $contents)
        );

        return $this;
    }

    /**
     * Will add an alternative representation to the variable (as a tab in Rich view).
     *
     * Will just dump whatever you pass as the single item as content.
     *
     * @param mixed $contents this will be dumped.
     * @param string $name
     *
     * @return self
     */
    public function addTabView__Dump($contents, $name = '')
    {
        $this->addTabView(
            new SageParsedVariableContents(SageParsedVariableContents::DUMP, $name, $contents)
        );

        return $this;
    }

    /**
     * Will add an alternative representation to the variable (as a tab in Rich view).
     *
     * Will just dump whatever you pass as the single item as content but unwrap the topmost element.
     *
     * @param mixed $contents this will be dumped but unwraped from the topmost element.
     * @param string $name
     *
     * @return self
     */
    public function addTabView__UnwrappedDump($contents, $name = '')
    {
        $this->addTabView(
            new SageParsedVariableContents(SageParsedVariableContents::DUMP_WITHOUT_TOP_PARENT, $name, $contents)
        );

        return $this;
    }

    /**
     * Will add an alternative representation to the variable (as a tab in Rich view).
     *
     * Will show as trace.
     *
     * @param SageTrace $contents this will be dumped but unwraped from the topmost element.
     * @param string $name
     *
     * @return self
     */
    public function addTabView__Trace(SageTrace $contents, $name = '')
    {
        $this->addTabView(
            new SageParsedVariableContents(SageParsedVariableContents::TRACE, $name, $contents)
        );

        return $this;
    }

    public function addTabView(SageParsedVariableContents $alternative)
    {
        if ($alternative->contents === null) {
            return;
        }

        $this->alternativeViews[] = $alternative;
    }

    /**
     * Special node type, represents an irrepresentable state, for example, a recursion.
     *
     * @param string $reason
     */
    public static function erroneous($reason)
    {
        $me        = new self();
        $me->error = $reason;

        return $me;
    }

    public function mergeFrom(self $from)
    {
        if ($from->name !== null) {
            $this->name = $from->name;
        }

        if ($from->type !== null) {
            $this->type = $from->type;
        }

        if ($from->subtype !== null) {
            $this->subtype = $from->subtype;
        }

        if ($from->hash !== null) {
            $this->hash = $from->hash;
        }

        if ($from->access !== null) {
            $this->access = $from->access;
        }

        if ($from->operator !== null) {
            $this->operator = $from->operator;
        }

        if ($from->size !== null) {
            $this->size = $from->size;
        }

        if ($from->value !== null) {
            $this->value = $from->value;
        }

        if ($from->error !== null) {
            $this->error = $from->error;
        }

        if ($from->trace !== null) {
            $this->trace = $from->trace;
        }

        if ($from->alternativeViews) {
            $this->alternativeViews = array_merge($this->alternativeViews, $from->alternativeViews);
        }

        return $this;
    }
}
