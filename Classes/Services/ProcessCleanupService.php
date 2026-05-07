<?php

namespace Wlb\Crowdsourcing\Services;

use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\ProcessHistoryRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class ProcessCleanupService
{
    public function __construct(
        private readonly ProcessHistoryRepository $processHistoryRepository,
        private readonly ProcessHistoryService $processHistoryService,
        private readonly ProcessRepository $processRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly SolrIndexer $solrIndexer,
        private readonly StatisticService $statisticService,
    ) {
    }

    /**
     * Cleans up a process by resetting the user, restoring the previous state,
     * and updating the repository accordingly.
     *
     * @param Process $process
     * @param string $abortType
     */
    public function cleanupSingleProcess(Process $process, string $abortType = 'cleanup_abort'): void
    {
        // Log the abort
        $this->statisticService->logWorkflowAction($abortType, $process, null, []);

        $lastHistoryProcess = $this->processHistoryRepository->getLastHistory($process->getRecordIdentifier());

        $data = $lastHistoryProcess->toArray();

        $this->processHistoryService->restoreFromArray($process, $data, false);
        // The current state of a process is one state after the state of the last history entry.
        $process->setNextState();
        $process->resetFeUser();
        $this->processRepository->update($process);
        $this->persistenceManager->persistAll();

        $this->solrIndexer->indexDocument($process);
    }


    /**
     * Cleans up stale processes by resetting their user, restoring their previous state,
     * and updating the repository accordingly.
     *
     * @param array $staleProcesses An array of stale processes to be cleaned up.
     * @return int The count of stale processes that were successfully cleaned up.
     */
    public function cleanupMultipleProcesses(array $staleProcesses): int
    {
        $cleanedCount = 0;

        foreach ($staleProcesses as $process) {
            $this->cleanupSingleProcess($process);
            $cleanedCount++;
        }

        return $cleanedCount;
    }
}