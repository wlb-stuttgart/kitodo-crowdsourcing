<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Wlb\Crowdsourcing\Domain\Model\Campaign;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;
use Wlb\Crowdsourcing\Services\SearchService;

class CampaignController extends ActionController
{
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly SearchService $searchService,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected ResourceFactory $resourceFactory
    )
    {
    }


    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
    }

    /**
     * Shows the form to edit an existing campaign.
     *
     * @param Campaign $campaign
     * @return ResponseInterface
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("campaign")
     */
    public function editAction(Campaign $campaign): ResponseInterface
    {
        $this->moduleTemplate->assign('campaign', $campaign);
        return $this->moduleTemplate->renderResponse('Backend/Campaign/Edit');
    }

    /**
     * Creates a new campaign.
     *
     * @param Campaign $campaign
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function updateAction(Campaign $campaign): ResponseInterface
    {
        if ($campaign->getImage() instanceof \TYPO3\CMS\Core\Resource\FileReference) {
            $originalImage = $campaign->getImage();
        }

        if ($this->request->hasArgument('image') && $this->request->getArgument('image') === '') {
            // Wenn das Bild gelöscht werden soll
            $campaign->setImage(null);
            if (isset($originalImage)) {
                // Lösche die FileReference
                $originalResource = $originalImage->getOriginalResource();
                $originalResource->delete();
            }
        }


        $this->campaignRepository->update($campaign);
        return $this->redirect('list');
    }

    /**
     * Shows the form to create a new campaign.
     *
     * @return ResponseInterface
     */
    public function newAction(): ResponseInterface
    {
        $campaign = new Campaign();
        $this->moduleTemplate->assign('campaign', $campaign);

        return $this->moduleTemplate->renderResponse('Backend/Campaign/New');
    }

    /**
     * Creates a new campaign.
     *
     * @param Campaign $campaign
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function createAction(Campaign $campaign): ResponseInterface
    {
        $this->campaignRepository->add($campaign);
        return $this->redirect('list');
    }


    /**
     * @param int $page
     * @return ResponseInterface
     */
    public function listAction(int $page = 0): ResponseInterface
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

        $this->moduleTemplate->assign('campaigns', $campaigns);
        $this->moduleTemplate->assign('currentPage', $currentPage);
        $this->moduleTemplate->assign('totalPages', $totalPages);
        $this->moduleTemplate->assign('itemsPerPage', $itemsPerPage);
        $this->moduleTemplate->assign('pageNumbers', $pageNumbers);
        $this->moduleTemplate->assign('previousPage', $previousPage);
        $this->moduleTemplate->assign('nextPage', $nextPage);

        return $this->moduleTemplate->renderResponse('Backend/Campaign/List');
    }

    /**
     * @param Campaign $campaign
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function listProcessesAction(Campaign $campaign): ResponseInterface
    {
        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }

        $this->moduleTemplate->assign("importedPath", $importedPath);
        //$this->moduleTemplate->assign("search", $search);
        $this->moduleTemplate->assign("campaign", $campaign);
        $this->moduleTemplate->assign("documents", $campaign->getProcesses());

        return $this->moduleTemplate->renderResponse('Backend/Campaign/ListProcesses');;
    }


    /**
     * @param Campaign $campaign
     * @param int $processUid
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function editProcessesAction(Campaign $campaign, int $processUid = 0): ResponseInterface
    {
        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }

        $processes = $this->processRepository->findAll();
        $this->moduleTemplate->assign("importedPath", $importedPath);
        $this->moduleTemplate->assign("processes", $processes);
        $this->moduleTemplate->assign("currentCampaign", $campaign);
        $this->moduleTemplate->assign("processUid", $processUid);

        return $this->moduleTemplate->renderResponse('Backend/Campaign/EditProcesses');;
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
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function addProcessToCampaignAction(Process $process, Campaign $campaign): ResponseInterface
    {
        $assignedCampaign = $process->getCampaign();
        if ($assignedCampaign) {
            $assignedCampaign->removeProcess($process);
            $this->campaignRepository->update($assignedCampaign);
        }

        $campaign->addProcess($process);
        $this->campaignRepository->update($campaign);

        return $this->redirect('editProcesses', null, null, [
            "campaign" => $campaign,
            "processUid" => $process->getUid()
        ]);
    }

    /**
     * @param Process $process
     * @param Campaign $campaign
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function removeProcessFromCampaignAction(Process $process, Campaign $campaign): ResponseInterface
    {
        $assignedCampaign = $process->getCampaign();
        if ($assignedCampaign) {
            $assignedCampaign->removeProcess($process);
        }

        $this->campaignRepository->update($campaign);

        return $this->redirect('editProcesses', null, null, [
            "campaign" => $campaign,
            "processUid" => $process->getUid()
        ]);
    }

    /**
     * @param Campaign $campaign
     * @param int $page
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function publishAction(Campaign $campaign, int $page): ResponseInterface
    {
        $campaign->changeWorkflowState(Campaign::WORKFLOW_STATE_PUBLISHED);
        $this->campaignRepository->update($campaign);
        return $this->redirect('list', null, null, ['page' => $page]);
    }

    /**
     * @param Campaign $campaign
     * @param int $page
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function closeAction(Campaign $campaign, int $page): ResponseInterface
    {
        $campaign->changeWorkflowState(Campaign::WORKFLOW_STATE_CLOSED);
        $this->campaignRepository->update($campaign);
        return $this->redirect('list', null, null, ['page' => $page]);
    }

    /**
     * @param Campaign $campaign
     * @param int $page
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function reopenAction(Campaign $campaign, int $page): ResponseInterface
    {
        $campaign->changeWorkflowState(Campaign::WORKFLOW_STATE_PUBLISHED);
        $this->campaignRepository->update($campaign);
        return $this->redirect('list', null, null, ['page' => $page]);
    }
}
