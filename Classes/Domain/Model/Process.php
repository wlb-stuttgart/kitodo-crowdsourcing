<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Process extends AbstractEntity
{
    const STATE_NEW = 'NEW';
    const STATE_ENTRY = 'ENTRY';
    const STATE_FIRST_CORRECTION  = 'FIRST_CORRECTION';
    const STATE_SECOND_CORRECTION = 'SECOND_CORRECTION';

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string List of images
     */
    protected $images;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string Metadata in JSON-Format
     */
    protected $metadata;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getImages(): string
    {
        return $this->images;
    }

    public function setImages(string $images): void
    {
        $this->images = $images;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getMetadata(): string
    {
        return $this->metadata;
    }

    public function setMetadata(string $metadata): void
    {
        $this->metadata = $metadata;
    }

}
