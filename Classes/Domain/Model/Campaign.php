<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Extbase\Annotation\FileUpload;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Campaign extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    const WORKFLOW_STATE_NEW = 'NEW';
    const WORKFLOW_STATE_PUBLISHED = 'PUBLISHED';
    const WORKFLOW_STATE_CLOSED = 'CLOSED';

    /**
     * @var string
     */
    protected string $title;

    /**
     * @var string
     */
    protected string $subtitle;

    /**
     * @var string
     */
    protected string $description;

    /**
     * @var string
     */
    protected string $shortDescription;

    /**
     * @var string
     */
    protected string $workflowState;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Wlb\Crowdsourcing\Domain\Model\Process>
     */
    protected $processes;

    #[FileUpload([
        'validation' => [
            'required' => false,
            'maxFiles' => 1,
            'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
            'mimeType' => ['allowedMimeTypes' => [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'image/svg+xml',
                    'image/bmp',
                    'image/tiff'
                ]
            ],
            'imageDimensions' => ['maxWidth' => 4096, 'maxHeight' => 4096]
        ],
        'uploadFolder' => '1:/uploads/tx_crowdsourcing/',
        'addRandomSuffix' => false,
        'duplicationBehavior' => DuplicationBehavior::RENAME,
    ])]
    protected $image;

    public function __construct()
    {
        // Initialize the processes collection
        $this->processes     = new ObjectStorage();
        $this->workflowState = self::WORKFLOW_STATE_NEW;
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
     * @return int
     */
    public function getProcessCount(): int
    {
        return $this->processes->count();
    }

    public function setImage(?FileReference $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\File|null
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     */
    public function getImageObject()
    {
        if ($this->getImage() > 0) {
            $resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
            return $resourceFactory->getFileObject($this->getImage());
        }

        return null;
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

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): void
    {
        $this->shortDescription = $shortDescription;
    }

    public function getWorkflowState(): string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * @param string $newState
     * @return void
     */
    public function changeWorkflowState(string $newState)
    {
        $currentState = $this->getWorkflowState();

        switch ($currentState) {
            case self::WORKFLOW_STATE_NEW:
            case self::WORKFLOW_STATE_CLOSED:
                if ($newState !== self::WORKFLOW_STATE_PUBLISHED) {
                    throw new \Exception(
                        "Campaign state transition not allowed: " . $currentState . "->" . $newState
                    );
                } else {
                    $this->setWorkflowState($newState);
                }
                break;
            case self::WORKFLOW_STATE_PUBLISHED:
                if ($newState !== self::WORKFLOW_STATE_CLOSED) {
                    throw new \Exception(
                        "Campaign state transition not allowed: " . $currentState . "->" . $newState
                    );
                } else {
                    $this->setWorkflowState($newState);
                }
                break;
            default:
                throw new \Exception("Invalid current campaign workflow state");
        }

    }

    public function isNew() {
        return $this->workflowState === self::WORKFLOW_STATE_NEW;
    }

    public function isPublished() {
        return $this->workflowState === self::WORKFLOW_STATE_PUBLISHED;
    }

    public function isClosed() {
        return $this->workflowState === self::WORKFLOW_STATE_CLOSED;
    }
}
