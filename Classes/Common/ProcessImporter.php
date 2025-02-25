<?php

namespace Wlb\Crowdsourcing\Common;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class ProcessImporter
{
    private ?ResourceFactory $resourceFactory = null;

    private ProcessRepository $processRepository;

    private PersistenceManager $persistenceManager;


    /**
     * @param ResourceFactory $resourceFactory
     * @return void
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param ProcessRepository $processRepository
     * @return void
     */
    public function injectProcessRepository(ProcessRepository $processRepository)
    {
        $this->processRepository = $processRepository;
    }


    /**
     * @param PersistenceManager $persistenceManager
     * @return void
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @param string $importDirectoryPath
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function import(string $importDirectoryPath)
    {
        if (!is_dir($this->directoryPath)) {
            throw new \Exception("The directory does not exist.");
            return;
        }

        // Root directory
        $dirIterator = new \DirectoryIterator($this->directoryPath);

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

                    if (isset($metadata['signature']) && !empty($metadata['signature']) && is_string($metadata['signature'])) {
                        // Avoid importing a process twice
                        if (!$this->processRepository->findOneByIdentifier(trim($metadata['signature']))) {
                            $process = new Process();
                            $process->setIdentifier(trim($metadata['signature']));
                            $process->setMetadata($json);
                            $process->setState(Process::STATE_NEW);
                            $process->setImages(serialize($images));
                            $this->processRepository->add($process);
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
