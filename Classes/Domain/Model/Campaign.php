<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Wlb\Crowdsourcing\Domain\Model\Process>
     */
    protected $processes;

    public function __construct()
    {
        // Initialize the processes collection
        $this->processes = new ObjectStorage();
    }

    /**
     * Add a process to the campaign.
     *
     * @param \Wlb\Crowdsourcing\Domain\Model\Process $process
     */
    public function addProcess(\Wlb\Crowdsourcing\Domain\Model\Process $process): void
    {
        $this->processes->attach($process);
    }

    /**
     * Remove a process from the campaign.
     *
     * @param \Wlb\Crowdsourcing\Domain\Model\Process $process
     */
    public function removeProcess(\Wlb\Crowdsourcing\Domain\Model\Process $process): void
    {
        $this->processes->detach($process);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Wlb\Crowdsourcing\Domain\Model\Process>
     */
    public function getProcesses(): ObjectStorage
    {
        return $this->processes;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
