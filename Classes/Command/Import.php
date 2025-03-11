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
use Wlb\Crowdsourcing\Common\Indexer;
use Wlb\Crowdsourcing\Domain\Repository\CampaignTaskRepository;
use Wlb\Crowdsourcing\Services\CampaignTaskImportService;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

/**
 * Command to import the meta data exported from Kitodo-Publication.
 */
class Import extends Command
{
    /**
     * @param CampaignTaskRepository $processRepository
     * @param ConfigurationManager $configurationManager
     * @param PersistenceManager $persistenceManager
     * @param ResourceFactory $resourceFactory
     */
    public function __construct(
        private readonly CampaignTaskRepository    $processRepository,
        private readonly ConfigurationManager      $configurationManager,
        private readonly PersistenceManager        $persistenceManager,
        private readonly ResourceFactory           $resourceFactory,
        private readonly Indexer                   $indexer,
        private readonly CampaignTaskImportService $campaignTaskImportService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('A command to import processes with their data.');
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

        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $frameworkConfiguration['persistence']['storagePid'] = MathUtility::forceIntegerInRange($storagePid, 0);
        $this->configurationManager->setConfiguration($frameworkConfiguration);

        // TODO error logging.
        // TODO optimize exception handling.
        try {
            if ($this->campaignTaskImportService->processTaskQueue()) {
                return Command::SUCCESS;
            }
        } catch( \Throwable $throwable) {
            throw $throwable;
            return Command::FAILURE;
        }

        return Command::FAILURE;
    }
}
