<?php

namespace Wlb\Crowdsourcing\Common;

class IndexFields implements \Iterator
{
    /**
     * @var array The index field configuration data
     */
    private $array;

    /**
     * @var int The current data pointer position.
     */
    private $position = 0;

    /**
     * @param array $configData
     */
    public function __construct(array $configData) {
        $this->array = $configData;
        $this->position = 0;
    }

    /**
     * @return void
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * @return IndexField
     */
    public function current(): IndexField {
        $indexFeldMapping = new IndexField(
            ($this->array[$this->position]['indexField'] ?? ""),
            ($this->array[$this->position]['path'] ?? ""),
            ($this->array[$this->position]['subpaths'] ?? ""),
            ($this->array[$this->position]['multivalue'] ?? false)
        );

        return $indexFeldMapping;
    }

    /**
     * @return int|mixed|string|null
     */
    public function key() {
        return array_keys($this->array)[$this->position];
    }

    /**
     * @return void
     */
    public function next() {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid() {
        return isset($this->array[array_keys($this->array)[$this->position]]);
    }
}
