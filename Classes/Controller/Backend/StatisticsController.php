<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Wlb\Crowdsourcing\Domain\Repository\ClickStatisticRepository;
use Wlb\Crowdsourcing\Domain\Repository\FrontendUserRepository;
use Wlb\Crowdsourcing\Services\StatisticService;

class StatisticsController extends ActionController
{
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly StatisticService $statisticService,
        private readonly ClickStatisticRepository $clickStatisticRepository,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly BackendConfigurationManager $backendConfigurationManager,
        protected readonly LanguageServiceFactory $languageServiceFactory
    )
    {
    }

    private function getSettings(ServerRequestInterface $request): array
    {
        $rawSetup = $this->backendConfigurationManager->getTypoScriptSetup($request);
        return $rawSetup['module.']['tx_crowdsourcing.']['settings.'] ?? [];
    }

    private function getYearParameter(ServerRequestInterface $request): int
    {
        $params = $request->getQueryParams();
        return (int)($params['year'] ?? date('Y'));
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
    }

    public function indexAction(): ResponseInterface
    {
        for ($m = 1; $m <= 12; $m++) {
            $llKey = 'statisticsModule.month.' . $m;
            $monthsArray[] = LocalizationUtility::translate($llKey, "crowdsourcing");
        }
        $this->moduleTemplate->assign('monthsArray', json_encode($monthsArray));

        $activeUserGroup = $this->getSettings($this->request)['activeUserGroup'] ?? 0;

        $registrationMinYear = $this->frontendUserRepository->getFirstRegistrationYear($activeUserGroup);
        $registrationYears = range($registrationMinYear, date('Y'));
        $this->moduleTemplate->assign('registrationYears', $registrationYears);

        $clickStatisticMinYear = $this->clickStatisticRepository->getMinYear();
        $clickStatisticYears = range($clickStatisticMinYear, date('Y'));
        $this->moduleTemplate->assign('clickStatisticYears', $clickStatisticYears);

        $this->moduleTemplate->assign('currentYear', date('Y'));

        $viewOnlyUsers = $this->frontendUserRepository->countUsersWithoutProcessHistory($activeUserGroup);
        $this->moduleTemplate->assign('viewOnlyUsers', $viewOnlyUsers);

        $averageDwellTime = $this->clickStatisticRepository->getAverageDwellTime();
        $this->moduleTemplate->assign('averageDwellTime', $averageDwellTime);

        $averageProcessingTime = $this->clickStatisticRepository->getAverageProcessingTime();
        $this->moduleTemplate->assign('averageProcessingTime', $averageProcessingTime);

        return $this->moduleTemplate->renderResponse('Backend/Statistics/Index');
    }


    public function getActiveUsersDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $activeUserGroup = $this->getSettings($request)['activeUserGroup'] ?? 0;
        $selectedYear    = $this->getYearParameter($request);
        $registeredUsersByYear = $this->frontendUserRepository->numberActiveUsersPerMonthForYear($selectedYear, $activeUserGroup);

        $data = [];
        foreach ($registeredUsersByYear as $year => $monthData) {
            $data[] = $monthData['total_users'] ?? 0;
        }

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(
            json_encode($data, JSON_THROW_ON_ERROR),
        );
        return $response;
    }

    public function getPageViewsDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $selectedYear    = $this->getYearParameter($request);
        $pageViews = $this->clickStatisticRepository->getMonthlyPageViewsForYear($selectedYear);

        $data = array_fill(0, 12, 0);
        foreach ($pageViews as $pv) {
            $month = (int)$pv['month'] - 1;
            if ($month >= 0 || $month > 11) {
                $data[$month] = (int)$pv['page_views'];
            }
        }

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(
            json_encode($data, JSON_THROW_ON_ERROR),
        );
        return $response;
    }
}
