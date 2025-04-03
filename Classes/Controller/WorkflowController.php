<?php

namespace Wlb\Crowdsourcing\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Model\Campaign;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

class WorkflowController extends ActionController
{

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly SolrSearcher $solrSearcher
    )
    {

    }


    /**
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * @return void
     */
    public function listCampaignsAction()
    {
        $campaigns = $this->campaignRepository->findByWorkflowState(Campaign::WORKFLOW_STATE_PUBLISHED);
        $this->view->assign('campaigns', $campaigns);
    }

    /**
     * @return void
     */
    public function listProcessesAction()
    {
        $query = empty($search)? '*' : $search;
        $results = [];

        $results = $this->solrSearcher->searchWithFacets($query);

        $documentIdentifiers = [];

        foreach($results as $result) {
            $documentIdentifiers[] = $result->id;
        }

        $processes = $this->processRepository->findByIdentifierList($documentIdentifiers);
        $this->view->assign("processes", $processes);

        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }

        $this->view->assign("importedPath", $importedPath);
    }

    /**
     * @param Campaign $campaign
     * @return void
     */
    public function showCampaignDetailsAction(Campaign $campaign)
    {
        $this->view->assign('campaign', $campaign);
    }
}
