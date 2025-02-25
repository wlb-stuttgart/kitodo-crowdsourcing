<?php

namespace Wlb\Crowdsourcing\Common;

class IndexFieldMapping
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $subpaths;

    /**
     * @param string $name
     * @param string $path
     * @param string $subpaths
     * @param bool   $multivalue
     */
    public function __construct(string $name, string $path, string $subpaths, bool $multivalue)
    {
        $this->name = $name;
        $this->path = $path;
        $this->subpaths = $subpaths;
        $this->multivalue = $multivalue;
    }

    public function getName(): string
    {
        return trim($this->name);
    }

    public function getPath(): string
    {
        return trim($this->path);
    }

    public function getSubpaths(): array
    {
        $subpaths = trim(trim($this->subpaths));

        if (!empty($subpaths)) {
            return array_map('trim', explode(',', $this->subpaths));
        }

        return [];
    }

    public function isMultivalue(): bool
    {
        return $this->multivalue;
    }
}