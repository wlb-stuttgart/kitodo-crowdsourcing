<?php

namespace Wlb\Crowdsourcing\Domain\Model;

class MetadataConfiguration extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $json;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getJson(): string
    {
        return $this->json;
    }

    public function setJson(string $json): void
    {
        $this->json = $json;
    }


}