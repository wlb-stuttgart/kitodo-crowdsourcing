<?php

namespace Wlb\Crowdsourcing\Services;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Domain\Model\ClickStatistic;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\ClickStatisticRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessHistoryRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class StatisticService
{
    public function __construct(
        private readonly ProcessRepository $processRepository,
        private readonly ProcessHistoryRepository $processHistoryRepository,
        private readonly ClickStatisticRepository $clickStatisticRepository,
        private readonly PersistenceManager $persistenceManager
    ) {
    }

    /**
     * @return array
     */
    public function getStatistics()
    {
        $statisticsArray = [];

        // Bestehende Prozess-Statistiken
        $statisticsArray = $this->processRepository->countStatisticsGroupedByCampaign();

        // Neue Klick-Statistiken
        $statisticsArray['totalClicks'] = $this->clickStatisticRepository->countAll();
        $statisticsArray['clicksByActionType'] = $this->clickStatisticRepository->getClickSummaryByActionType();
        $statisticsArray['clicksByDate'] = $this->clickStatisticRepository->getClickSummaryByDate();
        $statisticsArray['topTenEditorsByCampaign'] = $this->getTopTenEditorsByCampaign();
        $statisticsArray['topTenEditorsByCampaignLastMonth'] = $this->getTopTenEditorsByCampaignLastMonth();
        $statisticsArray['dwellTime'] = $this->clickStatisticRepository->getAverageDwellTime();
        $statisticsArray['averageProcessingTime'] = $this->clickStatisticRepository->getAverageProcessingTime();

        return $statisticsArray;
    }
    
    public function getPersonalStatistics(FrontendUser $feUser): array
    {
        $editedCountsByCampaign = $this->processHistoryRepository
            ->countEditedProcessesByFeUserGroupedByCampaign($feUser->getUid());

         $editedLastMonthByCampaign = $this->processHistoryRepository
            ->countEditedProcessesByFeUserGroupedByCampaignLastMonth($feUser->getUid());

        return [
            'editedCount'      => $editedCountsByCampaign,
            'editedLastMonth'       => $editedLastMonthByCampaign
        ];
    }

    /**
     * Gibt den Frontend-User zurück, der insgesamt die meisten Prozesse bearbeitet hat.
     *
     * @return array{fe_user: int, edit_count: int}|null
     */
    public function getMostActiveUserAllTime(): ?array
    {
        return $this->processHistoryRepository->findMostActiveFeUserAllTime();
    }

    /**
     * Gibt den Frontend-User zurück, der im letzten Kalendermonat die meisten Prozesse bearbeitet hat.
     *
     * @return array{fe_user: int, edit_count: int}|null
     */
    public function getMostActiveUserLastMonth(): ?array
    {
        return $this->processHistoryRepository->findMostActiveFeUserLastMonth();
    }

    /**
     * Returns the top 10 editors for each campaign.
     *
     * @return array<int, array<int, array{fe_user: int, edit_count: int}>>
     */
    public function getTopTenEditorsByCampaign(): array
    {
        return $this->processHistoryRepository->findTopTenEditorsByCampaign();
    }

    /**
     * Returns the top 10 editors for each campaign in the previous calendar month.
     *
     * @return array<int, array<int, array{fe_user: int, edit_count: int}>>
     */
    public function getTopTenEditorsByCampaignLastMonth(): array
    {
        return $this->processHistoryRepository->findTopTenEditorsByCampaignLastMonth();
    }


    /**
     * Protokolliert einen Klick in der Datenbank
     */
    public function logClick(
        string $actionType,
        string $actionIdentifier,
        ?ServerRequestInterface $request = null,
        int $processUid = 0,
        int $campaignUid = 0,
        array $additionalData = [],
        string $processState = ''
    ): void {
        $clickStatistic = new ClickStatistic();
        
        // Basis-Informationen
        $clickStatistic->setActionType($actionType);
        $clickStatistic->setActionIdentifier($actionIdentifier);
        $clickStatistic->setProcessState($processState);

        $feUserUid = 0;

        // Request-Informationen
        if ($request) {
            $clickStatistic->setUri((string)$request->getUri());
            $clickStatistic->setUserAgent($request->getHeaderLine('User-Agent'));
            $clickStatistic->setReferrer($request->getHeaderLine('Referer'));
            
            // Frontend-User (falls eingeloggt) - Priorität für den aktuell angemeldeten User
            $requestFeUser = $request->getAttribute('frontend.user');
            if ($requestFeUser && $requestFeUser->user && isset($requestFeUser->user['uid'])) {
                $feUserUid = (int)$requestFeUser->user['uid'];
            }
        } else {
            $clickStatistic->setUri('CLI/Background');
            $clickStatistic->setUserAgent('CLI-Task');
            $clickStatistic->setReferrer('');
        }
        
        $clickStatistic->setFeUserUid($feUserUid);

        // Session-ID
        $clickStatistic->setSessionId(session_id());
        
        // Kontext-Informationen
        $clickStatistic->setProcessUid($processUid);
        $clickStatistic->setCampaignUid($campaignUid);
        
        // Zusätzliche Daten als JSON
        if (!empty($additionalData)) {
            $clickStatistic->setAdditionalData(json_encode($additionalData));
        }
        
        // Speichern
        $this->clickStatisticRepository->add($clickStatistic);
        $this->persistenceManager->persistAll();
    }

    /**
     * Vereinfachte Methode zum Protokollieren von Seitenaufrufen
     */
    public function log(?ServerRequestInterface $request = null): void
    {
        $this->logClick(
            'page_view',
            'page_hit',
            $request
        );
    }

    /**
     * Protokolliert spezifische Aktionen im Workflow
     */
    public function logWorkflowAction(
        string $action,
        Process $process,
        ?ServerRequestInterface $request = null,
        array $additionalData = []
    ): void {
        $campaignUid = $process->getCampaign() ? $process->getCampaign()->getUid() : 0;
        
        $this->logClick(
            'workflow_action',
            $action,
            $request,
            $process->getUid(),
            $campaignUid,
            $additionalData,
            $process->getState()
        );
    }

    /**
     * Protokolliert Button-Klicks
     */
    public function logButtonClick(
        string $buttonId,
        ?ServerRequestInterface $request = null,
        int $processUid = 0,
        int $campaignUid = 0,
        array $additionalData = []
    ): void {
        $this->logClick(
            'button_click',
            $buttonId,
            $request,
            $processUid,
            $campaignUid,
            $additionalData
        );
    }

    /**
     * Ermittelt die Client-IP-Adresse
     */
    private function getClientIpAddress(?ServerRequestInterface $request): string
    {
        if (!$request) {
            return '127.0.0.1';
        }
        $serverParams = $request->getServerParams();
        
        // Verschiedene Header prüfen (für Proxy-Setups)
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ip = $serverParams[$header];
                // Bei mehreren IPs die erste nehmen
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return '';
    }
}