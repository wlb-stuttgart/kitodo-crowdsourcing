<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Process extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var Metadata
     */
    public $metadata;

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }
}