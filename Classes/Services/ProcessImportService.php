<?php

namespace Wlb\Crowdsourcing\Services;

use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class ProcessImportService
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
     * The path to the directory holding the data for the next process to be imported.
     *
     * @var string
     */
    private $processDir;


    /**
     * @param ProcessRepository $processRepository
     * @param PersistenceManager $persistenceManager
     * @param SolrIndexer $indexer
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(
        private readonly ProcessRepository  $processRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly SolrIndexer        $indexer
    )
    {
        $this->importedDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        $this->failedDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('failedDirectoryPath');
        $this->toImportDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('toImportDirectoryPath');
        $this->processDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('processDirectoryPath');
    }

    /**
     * Imports the file data for a single process specified by its identifier.
     *
     * @param string $identifier
     * @return bool
     * @throws \Exception
     */
    protected function processProcess(string $identifier): bool
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

            if (!$this->processRepository->findOneByIdentifier($identifier)) {
                $process = new Process();
                $process->setIdentifier($identifier);
                $process->setMetadata(json_encode($jsonData));
                $process->setState(Process::STATE_NEW);
                $process->setImages($imageNames);
                $this->processRepository->add($process);

                $this->indexer->indexDocument($process->getIdentifier(), $process->getMetadata());
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
     * Imports all processes from the configured "toImport" directory.
     *
     * @return void
     * @throws \Exception
     */
    public function processProcessQueue()
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
            $this->processProcess($fileName->getFilename());
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
     * Moves all files of a processes to the failed directory.
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
     * Moves all files of a processes to the process directory.
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
     * Moves all files of a processes to the imported directory.
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
