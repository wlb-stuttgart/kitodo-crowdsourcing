<?php

namespace Wlb\Crowdsourcing\Common;

class IndexField
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

    /**
     * @return string
     */
    public function getName(): string
    {
        return trim($this->name);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return trim($this->path);
    }

    /**
     * @return array
     */
    public function getSubpaths(): array
    {
        $subpaths = trim(trim($this->subpaths));

        if (!empty($subpaths)) {
            return array_map('trim', explode(',', $this->subpaths));
        }

        return [];
    }

    /**
     * @return bool
     */
    public function isMultivalue(): bool
    {
        return $this->multivalue;
    }
}
