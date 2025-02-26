<?php

namespace Wlb\Crowdsourcing\Common;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class ProcessImporter
{
    /**
     * @var ResourceFactory|null
     */
    private ResourceFactory $resourceFactory;

    /**
     * @var ProcessRepository
     */
    private ProcessRepository $processRepository;

    /**
     * @var PersistenceManager
     */
    private PersistenceManager $persistenceManager;

    /**
     * @var Indexer
     */
    private Indexer $indexer;

    /**
     * @param ResourceFactory $resourceFactory
     * @param ProcessRepository $processRepository
     * @param PersistenceManager $persistenceManager
     * @param Indexer $indexer
     */
    public function __construct(
        ResourceFactory $resourceFactory,
        ProcessRepository $processRepository,
        PersistenceManager $persistenceManager,
        Indexer $indexer
    )
    {
        $this->resourceFactory = $resourceFactory;
        $this->processRepository = $processRepository;
        $this->persistenceManager = $persistenceManager;
        $this->indexer = $indexer;
    }

    /**
     * @param string $importDirectoryPath
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function import(string $importDirectoryPath)
    {
        if (!is_dir($importDirectoryPath)) {
            throw new \Exception("The directory does not exist.");
            return;
        }

        // The import root directory
        $dirIterator = new \DirectoryIterator($importDirectoryPath);

        foreach ($dirIterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            if ($item->isDir()) {

                $imageDir = $item->getPathname() . DIRECTORY_SEPARATOR . 'images';
                $metadataFile = $item->getPathname() . DIRECTORY_SEPARATOR . 'metadata.json';

                $images = [];

                if (is_dir($imageDir)) {
                    $imageIterator = new \DirectoryIterator($imageDir);

                    foreach ($imageIterator as $imageItem) {
                        if ($imageItem->isDot()) {
                            continue;
                        }
                        $image['fileName'] = $imageItem->getFilename();
                        $image['path'] = $imageItem->getPathname();
                        $images[] = $image;
                    }
                }

                if (file_exists($metadataFile)) {

                    // TODO: Validate metadata?

                    $json = file_get_contents($metadataFile);
                    $metadata = json_decode($json, true);

                    // TODO: Make it configurable where the ID is in the data or perhaps find a completely different way to do it.
                    if (isset($metadata['signature']) && !empty($metadata['signature']) && is_string($metadata['signature'])) {
                        // Avoid importing a process twice
                        if (!$this->processRepository->findOneByIdentifier(trim($metadata['signature']))) {
                            $process = new Process();
                            $process->setIdentifier(trim($metadata['signature']));
                            $process->setMetadata($json);
                            $process->setState(Process::STATE_NEW);
                            $process->setImages(serialize($images));
                            $this->processRepository->add($process);
                            $this->indexer->indexDocument($json);

                            // TODO move files to imported folder.
                        }
                    }
                } else {
                   // TODO: No metadata found -> no process. Logger? Exception?
                }
            }
        }

        $this->persistenceManager->persistAll();
    }
}
