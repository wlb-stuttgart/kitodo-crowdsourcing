<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
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
        private readonly SolrSearcher $solrSearcher
    )
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
     * @param array $uploadFile
     * @return void
     */
    public function updateAction(Campaign $campaign, $uploadFile = [])
    {
        if ($uploadFile) {
            $this->saveAndUploadFile($campaign, $uploadFile);
        }

        $this->campaignRepository->update($campaign);
        $this->redirect('list');
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
     * @param array $uploadFile
     * @return void
     */
    public function createAction(Campaign $campaign, $uploadFile = [])
    {
        if ($uploadFile) {
            $this->saveAndUploadFile($campaign, $uploadFile);
        }

        $this->campaignRepository->add($campaign);
        $this->redirect('list');
    }


    /**
     * @param int $page
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function listAction(int $page = 0)
    {
        $currentPage = $page > 0 ? $page : 1;
        $itemsPerPage = 10;
        $offset = ($currentPage - 1) * $itemsPerPage;
        $totalCampaigns = $this->campaignRepository->countAll();
        $totalPages = ceil($totalCampaigns / $itemsPerPage);
        $pageNumbers = range(1, $totalPages);
        $previousPage = max(1, $currentPage - 1);
        $nextPage = min($totalPages, $currentPage + 1);

        $campaigns = $this->campaignRepository->findByPage($offset, $itemsPerPage);

        $this->view->assign('campaigns', $campaigns);
        $this->view->assign('currentPage', $currentPage);
        $this->view->assign('totalPages', $totalPages);
        $this->view->assign('itemsPerPage', $itemsPerPage);
        $this->view->assign('pageNumbers', $pageNumbers);
        $this->view->assign('previousPage', $previousPage);
        $this->view->assign('nextPage', $nextPage);
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
    public function editProcessesAction(Campaign $campaign, int $processUid = 0) {

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

    public function publishAction(Campaign $campaign, int $page)
    {
        $campaign->changeWorkflowState(Campaign::WORKFLOW_STATE_PUBLISHED);
        $this->campaignRepository->update($campaign);
        $this->redirect('list', null, null, ['page' => $page]);
    }

    public function closeAction(Campaign $campaign, int $page)
    {
        $campaign->changeWorkflowState(Campaign::WORKFLOW_STATE_CLOSED);
        $this->campaignRepository->update($campaign);
        $this->redirect('list', null, null, ['page' => $page]);
    }

    public function reopenAction(Campaign $campaign, int $page)
    {
        $campaign->changeWorkflowState(Campaign::WORKFLOW_STATE_PUBLISHED);
        $this->campaignRepository->update($campaign);
        $this->redirect('list', null, null, ['page' => $page]);
    }

    /**
     * @param Campaign $campaign
     * @param array $uploadFile
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     */
    protected function saveAndUploadFile($campaign, $uploadFile)
    {
        $resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
        $defaultStorage = $resourceFactory->getDefaultStorage();
        $folder = $defaultStorage->getFolder("uploads/tx_crowdsourcing/");

        $tempFilePath = $uploadFile['tmp_name'];

        if (is_file($tempFilePath)) {
            $destinationPath = GeneralUtility::getFileAbsFileName(ltrim($folder->getPublicUrl(), '/') . basename($uploadFile['name']));

            if (move_uploaded_file($tempFilePath, $destinationPath)) {
                $file = $folder->getFile($uploadFile['name']);
                $campaign->setImage($file->getUid());
            }
        }
    }
}
