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
        $countAll = $this->processRepository->countAllActiveGroupedByCampaign();
        $statisticsArray['countAll'] = $countAll;

        $countNew = $this->processRepository->countAllActiveByStateGroupedByCampaign(Process::WORKFLOW_STATE_NEW);
        $statisticsArray['countUnedited'] = $countNew;

        $countCorrectionByCampaign = $this->processRepository->countAllActiveByStateGroupedByCampaign(Process::WORKFLOW_STATE_CORRECTION);
        $countFinalCorrectionByCampaign = $this->processRepository->countAllActiveByStateGroupedByCampaign(Process::WORKFLOW_STATE_FINAL_CORRECTION);

        $countInProgressByCampaign = [];

        foreach ($countCorrectionByCampaign as $campaignUid => $countCorrection) {
            $countInProgressByCampaign[$campaignUid] = $countCorrection;
        }

        foreach ($countFinalCorrectionByCampaign as $campaignUid => $countFinalCorrection) {
            $countInProgressByCampaign[$campaignUid] = ($countInProgressByCampaign[$campaignUid] ?? 0)
                + $countFinalCorrection;
        }

        $statisticsArray['countInProgress'] = $countInProgressByCampaign;

        $countCompleted = $this->processRepository->countAllActiveByStateGroupedByCampaign(Process::WORKFLOW_STATE_COMPLETED);
        $statisticsArray['countCompleted'] = $countCompleted;

        // Neue Klick-Statistiken
        $statisticsArray['totalClicks'] = $this->clickStatisticRepository->countAll();
        $statisticsArray['clicksByActionType'] = $this->clickStatisticRepository->getClickSummaryByActionType();
        $statisticsArray['clicksByDate'] = $this->clickStatisticRepository->getClickSummaryByDate();

        return $statisticsArray;
    }

    public function getStatisticsByUser(FrontendUser $feUser): array
    {
        $countsByState = $this->processHistoryRepository->countDistinctByFeUserGroupedByState($feUser->getUid());

        $countNew             = $countsByState[Process::WORKFLOW_STATE_NEW]              ?? 0;
        $countCorrection      = $countsByState[Process::WORKFLOW_STATE_CORRECTION]       ?? 0;
        $countFinalCorrection = $countsByState[Process::WORKFLOW_STATE_FINAL_CORRECTION] ?? 0;
        $countCompleted       = $countsByState[Process::WORKFLOW_STATE_COMPLETED]        ?? 0;

        return [
            'countAll'             => $countNew + $countCorrection + $countFinalCorrection + $countCompleted,
            'countNew'             => $countNew,
            'countCorrection'      => $countCorrection,
            'countFinalCorrection' => $countFinalCorrection,
            'countCompleted'       => $countCompleted,
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
     * Protokolliert einen Klick in der Datenbank
     */
    public function logClick(
        string $actionType,
        string $actionIdentifier,
        ServerRequestInterface $request,
        int $processUid = 0,
        int $campaignUid = 0,
        array $additionalData = []
    ): void {
        $clickStatistic = new ClickStatistic();
        
        // Basis-Informationen
        $clickStatistic->setActionType($actionType);
        $clickStatistic->setActionIdentifier($actionIdentifier);

        // Request-Informationen
        $clickStatistic->setUri((string)$request->getUri());
        $clickStatistic->setIpAddress($this->getClientIpAddress($request));
        $clickStatistic->setUserAgent($request->getHeaderLine('User-Agent'));
        $clickStatistic->setReferrer($request->getHeaderLine('Referer'));
        
        // Session-ID
        $clickStatistic->setSessionId(session_id());
        
        // Frontend-User (falls eingeloggt)
        if ($request->getAttribute('frontend.user') && $request->getAttribute('frontend.user')->user) {
            $clickStatistic->setFeUserUid($request->getAttribute('frontend.user')->user['uid']);
        }
        
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
    public function log(ServerRequestInterface $request): void
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
        ServerRequestInterface $request,
        array $additionalData = []
    ): void {
        $campaignUid = $process->getCampaign() ? $process->getCampaign()->getUid() : 0;
        
        $this->logClick(
            'workflow_action',
            $action,
            $request,
            $process->getUid(),
            $campaignUid,
            $additionalData
        );
    }

    /**
     * Protokolliert Button-Klicks
     */
    public function logButtonClick(
        string $buttonId,
        ServerRequestInterface $request,
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
    private function getClientIpAddress(ServerRequestInterface $request): string
    {
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