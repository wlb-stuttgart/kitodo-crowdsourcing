<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Model\Campaign;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

class CampaignController extends ActionController
{
    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly SolrSearcher $solrSearcher,
    )
    {
    }

    public function indexAction()
    {
    }


    /**
     * Shows the form to edit an existing campaign.
     *
     * @param Campaign $campaign
     * @return void
     */
    public function editAction(Campaign $campaign)
    {
        $this->view->assign('campaign', $campaign);
    }

    /**
     * Creates a new campaign.
     *
     * @param Campaign $campaign
     * @return void
     */
    public function updateAction(Campaign $campaign)
    {
        $this->campaignRepository->update($campaign);
        $this->redirect('index');
    }

    /**
     * Shows the form to create a new campaign.
     *
     * @return void
     */
    public function newAction()
    {
        $campaign = new Campaign();
        $this->view->assign('campaign', $campaign);
    }

    /**
     * Creates a new campaign.
     *
     * @param Campaign $campaign
     * @return void
     */
    public function createAction(Campaign $campaign)
    {
        $this->campaignRepository->add($campaign);
        $this->redirect('index');
    }

    public function listAction()
    {


        //$u = GeneralUtility::makeInstance(UriBuilder::class);
        //echo $u->buildUriFromRoute('/campaign_assignProcess');


        $campaigns = $this->campaignRepository->findAll();
        $this->view->assign('campaigns', $campaigns);
    }

    /**
     * @param Campaign $campaign
     * @return void
     */
    public function listProcessesAction(Campaign $campaign)
    {
        //$query = empty($search)? '*' : $search;

        // $results = [];

        // $results = $this->solrSearcher->searchWithFacets($query);

        // $documentIdentifiers = [];

        // foreach($results as $result) {
        //    $documentIdentifiers[] = $result->id;
        // }


        //$documents = $this->campaignRepository->findByUid();

        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }

        $this->view->assign("importedPath", $importedPath);
        //$this->view->assign("search", $search);
        $this->view->assign("campaign", $campaign);
        $this->view->assign("documents", $campaign->getProcesses());
    }


    /**
     * @param Campaign $campaign
     * @param int $processUid
     * @return void
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function editProcessesAction(Campaign $campaign, int $processUid = 10000) {

        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }

        $processes = $this->processRepository->findAll();
        $this->view->assign("importedPath", $importedPath);
        $this->view->assign("processes", $processes);
        $this->view->assign("currentCampaign", $campaign);
        $this->view->assign("processUid", $processUid);
    }

    /*
    public function ajaxAssignProcessAction(ServerRequestInterface $request): ResponseInterface
    {
        $result = ['test'];

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(['result' => $result], JSON_THROW_ON_ERROR));
        return $response;
    }
    */

    /**
     * @param Process $process
     * @param Campaign $campaign
     * @return void
     */
    public function addProcessToCampaignAction(Process $process, Campaign $campaign)
    {
        $assignedCampaign = $process->getCampaign();
        if ($assignedCampaign) {
            $assignedCampaign->removeProcess($process);
            $this->campaignRepository->update($assignedCampaign);
        }

        $campaign->addProcess($process);
        $this->campaignRepository->update($campaign);

        $this->redirect('editProcesses', null, null, [
            "campaign" => $campaign,
            "processUid" => $process->getUid()
        ]);
    }

    /**
     * @param Process $process
     * @param Campaign $campaign
     * @return void
     */
    public function removeProcessFromCampaignAction(Process $process, Campaign $campaign)
    {
        $assignedCampaign = $process->getCampaign();
        if ($assignedCampaign) {
            $assignedCampaign->removeProcess($process);
        }

        $this->campaignRepository->update($campaign);

        $this->redirect('editProcesses', null, null, [
            "campaign" => $campaign,
            "processUid" => $process->getUid()
        ]);
    }


}
