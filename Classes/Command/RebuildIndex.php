<?php

declare(strict_types=1);

namespace Wlb\Crowdsourcing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ProcessImportService;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

/**
 * Command to clear the solr index.
 */
class RebuildIndex extends Command
{
    /**
     * @param ProcessRepository $processRepository
     * @param ConfigurationManager $configurationManager
     * @param PersistenceManager $persistenceManager
     * @param ResourceFactory $resourceFactory
     */
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly PersistenceManager   $persistenceManager,
        private readonly ResourceFactory      $resourceFactory,
        private readonly SolrIndexer          $indexer,
        private readonly ProcessRepository    $processRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('A command to clear the solr index.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storagePid = ExtensionConfigurationService::getInstance()->getConfigurationValue('storagePid');

        if (!is_numeric($storagePid) || $storagePid <= 0) {
            return Command::FAILURE;
        }

        // TODO error logging.
        // TODO optimize exception handling.
        try {
            $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
            $querySettings->setRespectStoragePage(true);
            $querySettings->setStoragePageIds([$storagePid]);

            $this->processRepository->setDefaultQuerySettings($querySettings);

            if (method_exists($this->indexer, 'applyQuerySettings')) {
                $this->indexer->applyQuerySettings($querySettings);
            }

            $this->indexer->deleteAll();
            $processes = $this->processRepository->findAll();
            foreach ($processes as $process) {
                $this->indexer->indexDocument($process);
            }
            return Command::SUCCESS;
        } catch( \Throwable $throwable) {
            return Command::FAILURE;
        }

        return Command::FAILURE;
    }
}
