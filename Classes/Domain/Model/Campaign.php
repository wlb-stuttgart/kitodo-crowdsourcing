<?php

namespace Wlb\Crowdsourcing\Domain\Model;

class Campaign extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
