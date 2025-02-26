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
use Wlb\Crowdsourcing\Common\ProcessImporter;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

/**
 * Command to import the meta data exported from Kitodo-Publication.
 */
class Import extends Command
{
    /**
     * @var ProcessRepository
     */
    protected ProcessRepository $processRepository;


    /**
     * @var ProcessImporter
     */
    protected ProcessImporter $processImporter;


    /**
     * @var PersistenceManager
     */
    protected PersistenceManager $persistenceManager;


    /**
     * @var ConfigurationManager
     */
    protected ConfigurationManager $configurationManager;


    /**
     * @var ResourceFactory
     */
    private ResourceFactory $resourceFactory;


    /**
     * @var Indexer
     */
    protected Indexer $indexer;


    /**
     * @param ProcessImporter $processImporter
     * @param ProcessRepository $processRepository
     * @param ConfigurationManager $configurationManager
     * @param PersistenceManager $persistenceManager
     * @param ResourceFactory $resourceFactory
     */
    public function __construct(
        ProcessImporter $processImporter,
        ProcessRepository $processRepository,
        ConfigurationManager $configurationManager,
        PersistenceManager $persistenceManager,
        ResourceFactory $resourceFactory,
        Indexer $indexer
    ) {
        parent::__construct();
        $this->processImporter = $processImporter;
        $this->processRepository = $processRepository;
        $this->configurationManager = $configurationManager;
        $this->persistenceManager = $persistenceManager;
        $this->resourceFactory = $resourceFactory;
        $this->indexer = $indexer;
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
        // TODO: How and where to configure the storage pid?
        $storagePid = 2;
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $frameworkConfiguration['persistence']['storagePid'] = MathUtility::forceIntegerInRange($storagePid, 0);
        $this->configurationManager->setConfiguration($frameworkConfiguration);

        $this->processImporter->import('/var/www/html/public/export');

        return Command::SUCCESS;
    }
}
