<?php

declare(strict_types=1);

namespace Wlb\Crowdsourcing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ProcessImportService;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

/**
 * Command to clear the solr index.
 */
class ClearIndex extends Command
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
        private readonly SolrIndexer          $indexer
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
        // TODO error logging.
        // TODO optimize exception handling.
        try {
            $this->indexer->deleteAll();
            return Command::SUCCESS;
        } catch( \Throwable $throwable) {
            return Command::FAILURE;
        }

        return Command::FAILURE;
    }
}
