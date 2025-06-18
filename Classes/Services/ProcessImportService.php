<?php

namespace Wlb\Crowdsourcing\Services;

use Symfony\Component\Filesystem\Filesystem;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Model\ProcessHistory;
use Wlb\Crowdsourcing\Domain\Repository\ProcessHistoryRepository;
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
     *  The path to the directory holding the data for the next process to be imported.
     *
     * @var string
     */
    private $exportDir;

    /**
     *  The path to the directory holding the completed data
     *
     * @var string
     */
    private $archiveDir;

    /**
     * The filesystem object.
     *
     * @var Filesystem
     */
    private $filesystem;


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
        private readonly SolrIndexer        $indexer,
        private readonly ProcessHistoryRepository $processHistoryRepository
    )
    {
        $this->filesystem = new Filesystem();

        $this->importedDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        $this->failedDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('failedDirectoryPath');
        $this->toImportDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('toImportDirectoryPath');
        $this->processDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('processDirectoryPath');
        $this->exportDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('exportDirectoryPath');
        $this->archiveDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('archiveDirectoryPath');
    }

    /**
     * Imports the file data for a single process specified by its identifier.
     *
     * @param string $identifier
     * @return bool
     * @throws \Exception
     */
    protected function importProcess(string $identifier): bool
    {
        // Check if process folder is ready for the next file import.
        if (count(array_diff(scandir($this->processDir), ['.', '..'])) != 0) {
            // TODO logging: "Process folder is not empty."
            throw new \Exception('Process is not empty');
        }

        $this->moveFilesFromToImportToProcess($identifier);

        // Check for necessary subdirectories and XML file
        $dataDir = $this->processDir . '/' . $identifier;
        $imagesDir = $dataDir . '/images/default';
        $xmlFilePath = $dataDir . '/meta.xml';

        if (!is_dir($dataDir) || !is_dir($imagesDir) || !file_exists($xmlFilePath)) {
            $this->moveFilesFromProcessToFailed($identifier);
            // TODO logging "Invalid data for " . $identifier
            return false;
        }

        // Read the XML file
        libxml_use_internal_errors(true);

        $xmlDoc = new \DOMDocument();

        if (!$xmlDoc->load($xmlFilePath)) {
            $this->moveFilesFromProcessToFailed($identifier);
            // TODO logging "Invalid xml file."
            // $errors = libxml_get_errors();
            return false;
        }

        // TODO: Extract kitodo xmlns from meta.xml and add it to kitodo:kitodo
        // TODO: Otherwise, adding new child nodes becomes problematic
        $xpathDoc = new \DOMXPath($xmlDoc);
        $xpathDoc->registerNamespace('kitodo', 'http://meta.kitodo.org/v1/');
        $xpathDoc->registerNamespace('mets', 'http://www.loc.gov/METS/');

        $kitodoNodes = $xpathDoc->query('//kitodo:kitodo');
        $typeNodes   = $xpathDoc->query('/mets:mets/mets:structMap[@TYPE="LOGICAL"]/mets:div/@TYPE');

        if ($kitodoNodes->count() <= 0 || $typeNodes->count() <= 0) {
            $this->moveFilesFromProcessToFailed($identifier);
            // TODO logging "Invalid xml file."
            var_dump("Invalid xml file");
            return false;
        }

        $xmlData = new \DOMDocument();
        $xmlData->preserveWhiteSpace = true;
        $xmlData->formatOutput = true;
        $importedNode = $xmlData->importNode($kitodoNodes->item(0), true);
        $xmlData->appendChild($importedNode);

        $xpathData = new \DOMXPath($xmlData);

        try {
            $imageNames = $this->getImageNames($identifier);

            if (!$this->processRepository->findOneByRecordIdentifier($identifier)) {
                $process = new Process();
                $process->setRecordIdentifier($identifier);
                $process->setMetadata($xmlData->saveXML());
                $process->setState(Process::WORKFLOW_STATE_NEW);
                $process->setImages($imageNames);
                $process->setType($typeNodes->item(0)->nodeValue);
                $this->processRepository->add($process);

                // Set initial history dataset
                $processHistory = new ProcessHistory();
                $processHistory->setRecordIdentifier($identifier);
                $processHistory->setMetadata($xmlData->saveXML());
                $processHistory->setState(Process::WORKFLOW_STATE_NEW);
                $processHistory->setImages($imageNames);
                $processHistory->setType($typeNodes->item(0)->nodeValue);
                $this->processHistoryRepository->add($processHistory);

                $this->indexer->indexDocument($process);
            }
            $this->persistenceManager->persistAll();
        } catch (\Throwable $throwable) {
            $this->moveFilesFromProcessToFailed($identifier);
            var_dump($throwable);
            return false;

        }

        $this->moveFilesFromProcessToImported($identifier);
        return true;
    }

    /**
     * Imports all processes from the configured "toImport" directory.
     *
     * @return bool
     * @throws \Exception
     */
    public function importProcessQueue()
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
            $this->importProcess($fileName->getFilename());
        }

        return true;
    }

    /**
     * Gets the names of all files in the images directory.
     *
     * @param string $identifier
     * @return array
     */
    protected function getImageNames(string $identifier): array
    {
        $imagesDir = $this->processDir . '/' . $identifier . '/images/default';
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
    protected function moveFilesFromToImportToProcess($identifier)
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

    /**
     * Moves all files of a processes to the exported directory.
     *
     * @param string $identifier
     * @return void
     * @throws \Exception
     */
    public function symlinkFilesFromProcessToExported($identifier)
    {
        $this->filesystem->symlink($this->importedDir . '/' . $identifier, $this->exportDir . '/' . $identifier);
    }

    public function moveFilesFromProcessToArchive($identifier)
    {
        $this->filesystem->rename($this->importedDir . '/' . $identifier, $this->archiveDir . '/' . $identifier);
    }
}
