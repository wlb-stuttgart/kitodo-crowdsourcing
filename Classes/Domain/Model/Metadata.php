<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use Wlb\Crowdsourcing\Domain\Model\Process;

class Metadata extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * process
     *
     * @var ObjectStorage<Process>
     * @cascade remove
     */
    protected $process = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getProcess(): ?ObjectStorage
    {
        return $this->process;
    }

    public function setProcess(?ObjectStorage $process): void
    {
        $this->process = $process;
    }
}
