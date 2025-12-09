<?php

declare(strict_types=1);

namespace Wlb\Crowdsourcing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\ProcessHistoryRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ProcessHistoryService;

class CleanupStaleProcessesCommand extends BaseCommand
{
    protected static $defaultName = 'crowdsourcing:cleanup-stale-processes';

    public function __construct(
        private readonly ProcessRepository $processRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly ProcessHistoryRepository $processHistoryRepository,
        private readonly ProcessHistoryService $processHistoryService,
        private readonly SolrIndexer $indexer,
    ) {
        parent::__construct();

        $querySettings = $this->getQuerySettings($this->getStoragePid());

        $this->processRepository->setDefaultQuerySettings($querySettings);
        $this->processHistoryRepository->setDefaultQuerySettings($querySettings);

        if (method_exists($this->indexer, 'applyQuerySettings')) {
           $this->indexer->applyQuerySettings($querySettings);
        }
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Bereinigt sich in Bearbeitung befindende Prozesse mit fe_user, die älter als 24 Stunden sind')
            ->setHelp('Dieser Command sucht nach Prozessen, die einen fe_user gesetzt haben und deren letzte Änderung älter als 24 Stunden ist, und führt entsprechende Bereinigungsaktionen durch.')
            ->addOption(
                'hours',
                'H',
                InputOption::VALUE_OPTIONAL,
                'Anzahl der Stunden nach denen ein Prozess als veraltet gilt',
                24
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Zeigt nur an, welche Prozesse bereinigt werden würden, ohne sie tatsächlich zu bereinigen'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Setzt Processe ohne Nachfrage direkt zurück'
            );
    }

    /**
     * Executes the command to clean up stale processes.
     *
     * @param InputInterface $input The input interface.
     * @param OutputInterface $output The output interface.
     * @return int The exit status code (Command::SUCCESS or Command::FAILURE).
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Bereinigung veralteter Prozesse');

        $hours = (int) $input->getOption('hours');
        $dryRun = $input->getOption('dry-run');

        try {
            $staleProcesses = $this->findStaleProcesses($hours);
            
            if (empty($staleProcesses)) {
                $io->success('Keine veralteten Prozesse gefunden.');
                return Command::SUCCESS;
            }

            $io->section(sprintf('Gefundene veraltete Prozesse (älter als %d Stunden): %d', $hours, count($staleProcesses)));
            
            // Show details about the stale processes
            $this->displayProcessDetails($staleProcesses, $io);

            if ($dryRun) {
                $io->note('Dry-Run Modus aktiviert - keine Änderungen werden durchgeführt.');
                return Command::SUCCESS;
            }

            // Do not ask for confirmation if the force option is set
            if (!$input->getOption('force')) {
                // Ask for confirmation before proceeding with the cleanup
                if (!$io->confirm(sprintf('Möchten Sie diese %d Prozesse bereinigen?', count($staleProcesses)))) {
                    $io->note('Bereinigung abgebrochen.');
                    return Command::SUCCESS;
                }
            }

            // Clean up stale processes
            $cleanedCount = $this->cleanupStaleProcesses($staleProcesses);

            $io->success(sprintf('Erfolgreich %d sich in Bearbeitung befindende Prozesse zurückgesetzt.', $cleanedCount));

        } catch (\Exception $e) {
            $io->error('Fehler beim Bereinigen der veralteten Prozesse: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Finds stale processes that have not been updated within the specified time frame.
     *
     * @param int $hours The number of hours to consider a process as stale.
     * @return array An array of stale processes found within the specified time range.
     */
    private function findStaleProcesses(int $hours): array
    {
        $cutoffTime = new \DateTime();
        $cutoffTime->modify(sprintf('-%d hours', $hours));
        
        return $this->processRepository->findStaleProcessesWithFeUser($cutoffTime);
    }

    /**
     * Displays detailed information about the provided processes in a table format.
     *
     * @param array $processes An array of Process objects to display details for.
     * @param SymfonyStyle $io An instance of SymfonyStyle used to render the output table.
     *
     * @return void
     */
    private function displayProcessDetails(array $processes, SymfonyStyle $io): void
    {
        $tableRows = [];
        
        /** @var Process $process */
        foreach ($processes as $process) {
            $feUser = $process->getFeUser();
            $lastUpdate = $process->getLastAccessed();
            $hoursAgo = (time() - $lastUpdate) / 3600;

            $tableRows[] = [
                $process->getUid(),
                $process->getRecordIdentifier(),
                $process->getState(),
                $feUser ? $feUser->getUid() : 'N/A',
                $feUser ? $feUser->getUsername() : 'N/A',
                date('Y-m-d H:i:s', $lastUpdate),
                number_format($hoursAgo, 1) . 'h'
            ];
        }
        
        $io->table(
            ['ID', 'Record ID', 'Status', 'FE-User ID', 'Username', 'Letztes Update', 'Vor'],
            $tableRows
        );
    }

    /**
     * Cleans up stale processes by resetting their user, restoring their previous state,
     * and updating the repository accordingly.
     *
     * @param array $staleProcesses An array of stale processes to be cleaned up.
     * @return int The count of stale processes that were successfully cleaned up.
     */
    private function cleanupStaleProcesses(array $staleProcesses): int
    {
        $cleanedCount = 0;

        /** @var Process $process */
        foreach ($staleProcesses as $process) {
            $process->resetFeUser();
            $lastHistoryProcess = $this->processHistoryRepository->getLastHistory($process->getRecordIdentifier());

            $data = $lastHistoryProcess->toArray();
            $this->processHistoryService->restoreFromArray($process, $data);
            $this->processRepository->update($process);
            $this->persistenceManager->persistAll();

            $this->indexer->indexDocument($process);

            $cleanedCount++;
        }

        return $cleanedCount;
    }
}