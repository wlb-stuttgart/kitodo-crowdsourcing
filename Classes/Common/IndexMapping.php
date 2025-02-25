<?php

namespace Wlb\Crowdsourcing\Common;

class IndexMapping implements \Iterator
{
    private $array = [
        [
            'indexField' => 'id',
            'path' => '$.signature',
            'subpaths' => '',
            'multivalue' => false
        ],
        [
            'indexField' => 'author_tsi',
            'path' => '$.person',
            'subpaths' => '',
            'multivalue' => true
        ],
        [
        'indexField' => 'publicationPlace_tsi',
        'path' => '$.publicationPlace',
        'subpaths' => '',
        'multivalue' => true
        ]
    ];

    private $position = 0;

    public function __construct() {
        $this->array = $this->array;
        $this->position = 0;
    }

    public function rewind() {
        $this->position = 0;
    }

    /**
     * @return IndexFieldMapping
     */
    public function current(): IndexFieldMapping {
        $indexFeldMapping = new IndexFieldMapping(
            ($this->array[$this->position]['indexField'] ?? ""),
            ($this->array[$this->position]['path'] ?? ""),
            ($this->array[$this->position]['subpaths'] ?? ""),
            ($this->array[$this->position]['multivalue'] ?? false)
        );

        return $indexFeldMapping;
    }

    public function key() {
        return array_keys($this->array)[$this->position];
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->array[array_keys($this->array)[$this->position]]);
    }
}
