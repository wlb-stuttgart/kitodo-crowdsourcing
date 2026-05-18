<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ProcessCleanupService;
use Wlb\Crowdsourcing\Services\SearchService;

class StatisticsController extends ActionController
{
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly SearchService $searchService,
        private readonly SolrIndexer $indexer,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ProcessCleanupService $processCleanupService,
        protected ResourceFactory $resourceFactory
    )
    {
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
    }


    public function indexAction(): ResponseInterface
    {
        return $this->moduleTemplate->renderResponse('Backend/Statistics/Index');;
    }
}
