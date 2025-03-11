<?php

namespace Wlb\Crowdsourcing\Services;

use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Common\Indexer;
use Wlb\Crowdsourcing\Domain\Model\CampaignTask;
use Wlb\Crowdsourcing\Domain\Repository\CampaignTaskRepository;

class CampaignTaskImportService
{
    /**
     * The path to the directory where the data is moved after a single import is successfully completed.
     *
     * @var string
     */
    private $importedDir;


    /**
     * The path to the directory where the data is moved if a single import fails.
     *
     * @var string
     */
    private $failedDir;

    /**
     * The path to the directory containing the data to be imported.
     *
     * @var string
     */
    private $toImportDir;

    /**
     * The path to the directory holding the data for the next campaign task to be imported.
     *
     * @var string
     */
    private $processDir;


    /**
     * @param CampaignTaskRepository $campaignTaskRepository
     * @param PersistenceManager $persistenceManager
     * @param Indexer $indexer
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(
        private readonly CampaignTaskRepository $campaignTaskRepository,
        private readonly PersistenceManager     $persistenceManager,
        private readonly Indexer                $indexer
    )
    {
        $this->importedDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        $this->failedDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('failedDirectoryPath');
        $this->toImportDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('toImportDirectoryPath');
        $this->processDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('processDirectoryPath');
    }

    /**
     * Imports the file data for a single campaign task specified by its identifier.
     *
     * @param string $identifier
     * @return bool
     * @throws \Exception
     */
    protected function processTask(string $identifier): bool
    {
        // Check if process folder is ready for the next file import.
        if (count(array_diff(scandir($this->processDir), ['.', '..'])) != 0) {
            // TODO logging: "Process folder is not empty."
            throw new \Exception('Process is not empty');
        }

        $this->moveFilesFromToImpotToProcess($identifier);

        // Check for necessary subdirectories and JSON file
        $dataDir = $this->processDir . '/' . $identifier;
        $imagesDir = $dataDir . '/images';
        $jsonFilePath = $dataDir . '/' . $identifier . '.json';

        if (!is_dir($dataDir) || !is_dir($imagesDir) || !file_exists($jsonFilePath)) {
            $this->moveFilesFromProcessToFailed($identifier);
            // TODO logging "Invalid data for " . $identifier
            return false;
        }

        // Read the JSON file
        $jsonData = json_decode(file_get_contents($jsonFilePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->moveFilesFromProcessToFailed($identifier);
            // TODO logging "Invalid json file."
            return false;
        }

        try {
            $imageNames = $this->getImageNames($identifier);

            if (!$this->campaignTaskRepository->findOneByIdentifier($identifier)) {
                $campaignTask = new CampaignTask();
                $campaignTask->setIdentifier($identifier);
                $campaignTask->setMetadata(json_encode($jsonData));
                $campaignTask->setState(CampaignTask::STATE_NEW);
                $campaignTask->setImages(serialize($imageNames));
                $this->campaignTaskRepository->add($campaignTask);

                $this->indexer->indexDocument($campaignTask->getIdentifier(), $campaignTask->getMetadata());
            }
            $this->persistenceManager->persistAll();
        } catch (\Throwable $throwable) {
            $this->moveFilesFromProcessToFailed($identifier);
            return false;

        }

        $this->moveFilesFromProcessToImported($identifier);
        return true;
    }

    /**
     * Imports alle campaign tasks from the configured "toImport" directory.
     *
     * @return void
     * @throws \Exception
     */
    public function processTaskQueue()
    {
        if (!is_dir($this->importedDir) || !is_dir($this->failedDir) || !is_dir($this->toImportDir) ||!is_dir($this->processDir)) {
            throw new \Exception("Missing import directories. Check extension configuration.");
        }

        $toImportIterator = new \DirectoryIterator($this->toImportDir);

        foreach ($toImportIterator as $fileName) {
            if ($fileName->isDot()) {
                continue;
            }

            // Filename has to be an identifier.
            $this->processTask($fileName->getFilename());
        }
    }

    /**
     * Gets the names of all files in the images directory.
     *
     * @param string $identifier
     * @return array
     */
    protected function getImageNames(string $identifier): array
    {
        $imagesDir = $this->processDir . '/' . $identifier . '/images';
        $imageFiles = array_diff(scandir($imagesDir), ['.', '..']);
        $imageNames = [];

        foreach ($imageFiles as $image) {
            $imageNames[] = $image;
        }

        return $imageNames;
    }

    /**
     * Moves all files of a campaign task to the failed directory.
     *
     * @param string $identifier
     * @return void
     * @throws \Exception
     */
    protected function moveFilesFromProcessToFailed($identifier)
    {
        if (!rename($this->processDir . '/' . $identifier, $this->failedDir . '/' . $identifier)) {
            throw new \Exception('Could not move data from process to failed.');
        }
    }

    /**
     * Moves all files of a campaign task to the process directory.
     *
     * @param string $identifier
     * @return void
     * @throws \Exception
     */
    protected function moveFilesFromToImpotToProcess($identifier)
    {
        if (!rename($this->toImportDir . '/' . $identifier, $this->processDir . '/' . $identifier)) {
            throw new \Exception('Could not move data from toImport to process folder');
        }
    }

    /**
     * Moves all files of a campaign task to the imported directory.
     *
     * @param string $identifier
     * @return void
     * @throws \Exception
     */
    protected function moveFilesFromProcessToImported($identifier)
    {
        if (!rename($this->processDir . '/' . $identifier, $this->importedDir . '/' . $identifier)) {
            throw new \Exception('Could not move data from process to imported folder');
        }
    }
}
