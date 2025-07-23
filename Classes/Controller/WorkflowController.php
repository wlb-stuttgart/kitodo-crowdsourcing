<?php

namespace Wlb\Crowdsourcing\Controller;

use \DOMDocument;
use \DOMXPath;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Model\Campaign;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Model\MetadataConfiguration;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Model\ProcessHistory;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\FrontendUserRepository;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessHistoryRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\AccessControlService;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;
use Wlb\Crowdsourcing\Services\ProcessHistoryService;
use Wlb\Crowdsourcing\Services\ProcessImportService;
use Wlb\Crowdsourcing\Services\RulesetService;
use Wlb\Crowdsourcing\Services\SearchService;
use Wlb\Crowdsourcing\Services\StatisticService;

class WorkflowController extends ActionController
{
    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly MetadataConfigurationRepository $metadataConfigurationRepository,
        private readonly SearchService $searchService,
        private readonly AccessControlService $accessControlService,
        private readonly Context $context,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly ProcessHistoryRepository $processHistoryRepository,
        private readonly ProcessHistoryService $processHistoryService,
        private readonly SolrIndexer $solrIndexer,
        private readonly ProcessImportService $processImportService,
        private readonly RulesetService $rulesetService,
        private readonly StatisticService $statisticService,
        private readonly ConnectionPool $connectionPool
    ) {
    }

    protected function initializeAction()
    {
        parent::initializeAction();
        
        // log controller action
        $this->statisticService->logClick(
            'controller_action',
            $this->actionMethodName,
            $this->request->getAttribute('originalRequest') ?? $this->request
        );
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     * @return void
     */
    protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view)
    {
        $this->view->assign('currentView', preg_replace('/Action$/', '', $this->actionMethodName));
    }

    public function indexAction()
    {
        $this->statisticService->logClick(
            'page_view',
            'workflow_index',
            $this->request->getAttribute('originalRequest') ?? $this->request
        );
    }

    public function landingPageAction()
    {
        $this->statisticService->logClick(
            'page_view',
            'landing_page',
            $this->request->getAttribute('originalRequest') ?? $this->request
        );

        $infoBoxes = [];
        if (isset($this->settings['infoBox'])) {
            foreach ($this->settings['infoBox'] as $key => $contentUid) {
                if (is_numeric($contentUid)) {
                    $infoBoxes[$key]  = $this->getContentForInfoBox((int)$contentUid);
                }
            }
        }

        $this->view->assign('infoBoxes', $infoBoxes);


    }

    public function initializeListCampaignsAction()
    {
        if (!$this->accessControlService->isCrowdsourcingUser()) {
            die("Access denied");
        }
    }

    public function listCampaignsAction()
    {
        $campaigns = $this->campaignRepository->findByWorkflowState(Campaign::WORKFLOW_STATE_PUBLISHED);
        $this->view->assign('campaigns', $campaigns);
        
        $this->statisticService->logClick(
            'page_view',
            'list_campaigns',
            $this->request->getAttribute('originalRequest') ?? $this->request,
            0,
            0,
            ['campaign_count' => count($campaigns)]
        );
    }

    public function dashboardAction()
    {
        $this->statisticService->logClick(
            'page_view',
            'dashboard',
            $this->request->getAttribute('originalRequest') ?? $this->request
        );

        $userId = $this->context->getPropertyFromAspect('frontend.user', 'id');
        /** @var FrontendUser $user */
        $feUser = $this->frontendUserRepository->findByUid($userId);

        if ($feUser) {
            $currentProcess = $this->processRepository->findCurrentProcessByFeUser($feUser);

            if ($currentProcess) {
                if ($this->processHistoryService->hasUserAlreadyEdited($currentProcess, $feUser)) {
                    $currentProcess = null;
                }
            }
        }

        /** @var Campaign $campaign */
        $campaign = $this->campaignRepository->findAll()->getFirst();

        $this->view->assign('statistic', $this->statisticService->getStatistics());
        $this->view->assign('campaign', $campaign);
        $this->view->assign('currentProcess', $currentProcess);

    }

    public function initializeListProcessesAction()
    {
        if (!$this->accessControlService->isCrowdsourcingUser()) {
            die("Access denied");
        }
    }

    /**
     * Lists processes based on the provided query and active facets, and prepares the data for rendering in the view.
     *
     * @param string $query The search query used to filter the listed processes. Default is an empty string.
     * @return void
     */
    public function listProcessesAction(string $query = '')
    {
        $userId = $this->context->getPropertyFromAspect('frontend.user', 'id');
        /** @var FrontendUser $user */
        $feUser = $this->frontendUserRepository->findByUid($userId);
        $this->view->assign('currentUser', $feUser);

        $facetsFields = $this->searchService->getFacetFields();
        $activeFacets = $this->request->getArguments()['facet'] ?? [];
        $searchResult = $this->searchService->searchProcesses($query, $facetsFields, $activeFacets);
        $processes = $searchResult['processes'];
        $facets = $searchResult['facets'];

        // Process facets for frontend use
        $facetValueCounter = [];
        foreach ($facetsFields as $facetLabel => $facetField) {
            foreach ($facetField as $fieldName => $value) {
                if (array_key_exists($fieldName, $facets)) {
                    $facetsFields[$facetLabel][$fieldName] = $facets[$fieldName];
                    $i = 0;
                    foreach ($facetsFields[$facetLabel][$fieldName] as $key => $facetValue) {
                        if ($i % 2 == 1) {
                            $facetValueCounter[$fieldName][] = $facetValue;
                            unset($facetsFields[$facetLabel][$fieldName][$key]);
                        }
                        $i++;
                    }
                }
            }
        }

        $this->view->assign("activeFacets", $activeFacets);
        $this->view->assign("facetCounters", $facetValueCounter);
        $this->view->assign("facets", $facetsFields);

        $processEditAllowedByCurrentUser = [];
        foreach ($processes as $process) {
            $allowed = $this->processHistoryService->hasUserAlreadyEdited($process, $feUser);
            $processEditAllowedByCurrentUser[$process->getUid()] = !$allowed;
        }

        $this->view->assign('editAllowedByCurrentUser', $processEditAllowedByCurrentUser);
        $this->view->assign("processes", $processes);

        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }

        $this->view->assign("importedPath", $importedPath);
        $this->view->assign("query", $query);

        if ($this->request->getArguments()['errorMessage'] === 'editAnotherProcess') {
            $this->view->assign('errorMessage', 'editAnotherProcess');
            // get current edit process
            $editingByCurrentUser = $this->processRepository->findCurrentProcessByFeUser($feUser);
            $this->view->assign('processCurrentUserEditing', $editingByCurrentUser);


        } else if ($this->request->getArguments()['errorMessage'] === 'processTaken') {
            $this->view->assign('errorMessage', 'processTaken');
        } else if ($this->request->getArguments()['errorMessage'] === 'noRandomProcessAvailable') {
            $this->view->assign('errorMessage', 'noRandomProcessAvailable');
        }

        if ($this->request->getArguments()['currentProcess'] !== null) {
            $this->view->assign('currentProcess', $this->request->getArguments()['currentProcess']);
        }

        if ($this->request->getArguments()['requestedProcess'] !== null) {
            $this->view->assign('requestedProcess', $this->request->getArguments()['requestedProcess']);
        }


        // log search action
        $this->statisticService->logClick(
            'search_action',
            'list_processes',
            $this->request->getAttribute('originalRequest') ?? $this->request,
            0,
            0,
            [
                'search_query' => $query,
                'active_facets' => $activeFacets,
                'result_count' => count($processes)
            ]
        );
    }

    /**
     * Displays the details of a campaign.
     *
     * @param Campaign $campaign The campaign entity to be displayed.
     * @return void
     */
    public function showCampaignDetailsAction(Campaign $campaign)
    {
        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }
        $this->view->assign('importedPath', $importedPath);
        $this->view->assign('campaign', $campaign);
        
        // log campaign details view
        $this->statisticService->logClick(
            'page_view',
            'campaign_details',
            $this->request->getAttribute('originalRequest') ?? $this->request,
            0,
            $campaign->getUid(),
            ['campaign_title' => $campaign->getTitle()]
        );
    }

    /**
     * Handles the metadata editing action for a process.
     * Assigns configuration, form values, and process details to the view.
     * Logs the metadata edit action for statistics purposes.
     *
     * @param Process $process The process entity containing metadata to be edited
     * @return ResponseInterface Returns the response object after processing the action
     * @throws \Exception If the process is already taken, user is editing another process, or metadata configuration is missing
     */
    public function editMetadataAction(Process $process): ResponseInterface
    {
        $userId = $this->context->getPropertyFromAspect('frontend.user', 'id');
        /** @var FrontendUser $user */
        $feUser = $this->frontendUserRepository->findByUid($userId);

        $processType = $process->getType();

        if ($process->hasFeUser() && $process->getFeUser() !== $feUser) {
//            throw new \Exception('Process already taken');
            $this->redirect('listProcesses', null, null, ['errorMessage' => 'processTaken']);
        }

        if ($currentlyEditingProcess = $this->processRepository->findOneByFeUser($feUser)) {
            if ($currentlyEditingProcess !== $process) {
//                throw new \Exception('User is already editing another process');
                $this->redirect(
                    'listProcesses',
                    null,
                    null,
                    [
                        'errorMessage' => 'editAnotherProcess',
                        'currentProcess' => $currentlyEditingProcess->getUid(),
                        'requestedProcess' => $process->getUid()
                    ]
                );
            }
        }

        $process->setFeUser($feUser);
        $this->processRepository->update($process);

//        $this->persistenceManager->persistAll();

        $queryResult = $this->metadataConfigurationRepository->findAll();
        if ($queryResult->count() !== 0) {
            /** @var MetadataConfiguration $dbConfiguration */
            $dbConfiguration = $queryResult->getFirst();
            $dbConfigArray = json_decode($dbConfiguration->getJson(), true);

            $this->view->assign('dbConfig', $dbConfigArray);

            // configuration preprocessing (tabs and active metadata)
            $sorted = [];

            foreach ($dbConfigArray[$processType] as $fieldName => $meta) {
                if ($meta['minOccurs'] === '') {
                    $meta['minOccurs'] = 0;
                }
                if ($meta['maxOccurs'] === '') {
                    $meta['maxOccurs'] = 1;
                }

                $tab = $meta['tab'] === '' ? 'default' : $meta['tab'];
                if (!isset($sorted[$tab])) {
                    $sorted[$tab] = [];
                }
                $sorted[$tab][$fieldName] = $meta;
            }

            // set default at the end
            ksort($sorted);
            $defaultValues = $sorted['default'];
            unset($sorted['default']);
            $sorted['default'] = $defaultValues;
            $dbConfigArraySorted[$processType] = $sorted;

            $this->view->assign('dbConfigTabSorted', $dbConfigArraySorted);

        } else {
            throw new \Exception('Metadata configuration missing');
        }

        $this->view->assign('rulesetDefinitions', $this->rulesetService->getRulesetDefinitions());

        // build value array for each active configuration
        $formValues = [];

        // load process xml
        $doc = new DOMDocument();
        $doc->loadXML($process->getMetadata());
        $xpath = new DOMXPath($doc);

        foreach ($dbConfigArray[$process->getType()] as $metadataKey => $metadataConfig) {
            if (!key_exists('children', $metadataConfig)) {
                foreach ($xpath->query('//*[@name="'.$metadataKey.'"]') as $metadataValue) {
                    $formValues[$metadataKey][] = $metadataValue->nodeValue;
                }
            } else {
                $i = 0;
                /** @var \DOMElement $metadataValue */
                foreach ($xpath->query('//*[@name="'.$metadataKey.'"]') as $metadataValue) {
                    foreach ($metadataValue->childNodes as $metadataChildValue) {
                        if ($metadataChildValue->nodeType != XML_TEXT_NODE) {
                            $formValues[$metadataKey][$i][$metadataChildValue->getAttribute('name')][] = $metadataChildValue->nodeValue;
                        }
                    }
                    $i++;
                }
            }
        }

        // Get images as base64 with width and height info
        $this->view->assign("processImagesInfo", $process->getImageInfos());
        $this->view->assign('process', $process);
        $this->view->assign('formValues', $formValues);

        $this->view->assign('reportMail', ExtensionConfigurationService::getInstance()->getConfigurationValue('reportMail'));

        // log metadata edit action
        $this->statisticService->logWorkflowAction(
            'edit_metadata',
            $process,
            $this->request->getAttribute('originalRequest') ?? $this->request,
            ['process_type' => $processType]
        );

        return $this->htmlResponse();
    }

    /**
     * Handles the saving of a form action by processing metadata, updating process states,
     * managing process history, exporting data when necessary, and indexing the updated process.
     *
     * Depending on the request arguments, this method supports aborting, caching, and saving
     * operations for a given process. It also performs necessary file operations and logs the action.
     *
     * @return void
     * @throws IllegalObjectTypeException
     * @throws NoSuchArgumentException
     * @throws UnknownObjectException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function saveFormAction()
    {
        $trustedMetadata = $this->request->getArgument('metadata');
        $processId = $this->request->getArgument('process');
        /* @var $process \Wlb\Crowdsourcing\Domain\Model\Process */
        $process = $this->processRepository->findByUid($processId);

        $actionTaken = '';
        
        if ($this->request->hasArgument('abort')) {
            $actionTaken = 'abort';
            $this->resetProcessFromHistory($process);
        }

        if ($this->request->hasArgument('cache')) {
            $actionTaken = 'cache';
            $process->updateMetadata($trustedMetadata);
            $this->persistenceManager->persistAll();
        }

        if ($this->request->hasArgument('save')) {
            $actionTaken = 'save';
            $process->updateMetadata($trustedMetadata);

            $processHistory = new ProcessHistory();
            $data = $process->toArray();

            // add data from process to processHistory
            $this->processHistoryService->restoreFromArray($processHistory, $data);

            // save process history
            $this->processHistoryRepository->add($processHistory);
            $this->persistenceManager->persistAll();

            // Remove user
            $process->resetFeUser();
            // Set process to next state
            $process->setNextState();

            // Check if process is finished
            // Move directory to exported directory
            if ($process->getState() === $process::WORKFLOW_STATE_COMPLETED) {
                // TODO: Convert the next lines into a export service??
                // save metadata to file
                $metadata = $process->getMetadata();
                $identifier = $process->getRecordIdentifier();

                $importedDir = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
                // Check for necessary subdirectories and XML file
                $dataDir = $importedDir . '/' . $identifier;
                $imagesDir = $dataDir . '/images/default';
                $xmlFilePath = $dataDir . '/meta.xml';

                $xmlDoc = new \DOMDocument();
                if (!$xmlDoc->load($xmlFilePath)) {
                    throw new \Exception('Could not load XML file');
                }
                $xpathDoc = new \DOMXPath($xmlDoc);

                $xmlDataNode = $xpathDoc->query('//mets:xmlData');
                $xmlDataNode->item(0)->removeChild($xmlDataNode->item(0)->firstChild);

                $dbDoc = new \DOMDocument();
                $dbDoc->loadXML($metadata);

                $xmlDataNode->item(0)->appendChild($xmlDoc->importNode($dbDoc->documentElement, TRUE));

                if (!$xmlDoc->save($xmlFilePath)) {
                    throw new \Exception('Could not save XML file');
                }

                $this->processImportService->moveFilesFromProcessToArchive($process->getRecordIdentifier());
                $this->processImportService->symlinkFilesFromProcessToExported($process->getRecordIdentifier());
            }
        }

        // index data
        $this->solrIndexer->indexDocument($process);

        $this->processRepository->update($process);

        // log form save action
        $this->statisticService->logWorkflowAction(
            $actionTaken,
            $process,
            $this->request->getAttribute('originalRequest') ?? $this->request,
            ['metadata_fields' => count($trustedMetadata)]
        );

        $this->redirect('listProcesses', null, null);
    }


    public function editRandomProcessAction()
    {
        $userId = $this->context->getPropertyFromAspect('frontend.user', 'id');
        /** @var FrontendUser $user */
        $feUser = $this->frontendUserRepository->findByUid($userId);
        $process = $this->processRepository->findRandomForNonCurrentUser($feUser);
        if ($process) {
            $this->redirect('editMetadata', 'Workflow', 'Crowdsourcing', ['process' => $process->getUid()]);
        } else {
            $this->redirect('listProcesses', null, null, ['errorMessage' => 'noRandomProcessAvailable']);
        }
    }


    /**
     * @param Process $currentProcess
     * @param Process $newProcess
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function abortAndEditNewProcessAction(Process $currentProcess, Process $newProcess): void
    {
        $this->resetProcessFromHistory($currentProcess);
        $this->solrIndexer->indexDocument($currentProcess);
        $this->processRepository->update($currentProcess);
        $this->redirect('editMetadata', 'Workflow', 'Crowdsourcing', ['process' => $newProcess->getUid()]);
    }


    /**
     * @param int $uid
     * @return array
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getContentForInfoBox(int $uid): array
    {
        $connection = $this->connectionPool->getConnectionForTable('tt_content');

        $contentElement = $connection->select(
            ['header', 'bodytext'],
            'tt_content',
            ['uid' => $uid]
        )->fetchAllAssociative();

        if ($contentElement) {
            $contentElement = $contentElement[0];
        }
        return $contentElement;
    }

    private function resetProcessFromHistory($process)
    {
        $process->resetFeUser();
        $lastHistoryProcess = $this->processHistoryRepository->getLastHistory($process->getRecordIdentifier());
        $data = $lastHistoryProcess->toArray();
        $this->processHistoryService->restoreFromArray($process, $data);
        $this->persistenceManager->persistAll();
    }
}
