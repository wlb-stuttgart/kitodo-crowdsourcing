<?php

namespace Wlb\Crowdsourcing\Controller;

use \DOMDocument;
use \DOMXPath;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Wlb\Crowdsourcing\Common\Solr\SolrIndexer;
use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Model\Campaign;
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
use Wlb\Crowdsourcing\Services\SearchService;

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
        private readonly SolrIndexer $solrIndexer
    )
    {
    }

    protected function initializeAction()
    {
        parent::initializeAction();

        //if (!$this->accessControlService->isCrowdsourcingUser()) {
        //    die("Access denied");
        //}
    }

    public function indexAction()
    {
    }

    /**
     * @return void
     */
    public function landingPageAction()
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
     * @param string $query
     * @return void
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function listProcessesAction(string $query = '')
    {
        $userId = $this->context->getPropertyFromAspect('frontend.user', 'id');
        /** @var FrontendUser $user */
        $feUser = $this->frontendUserRepository->findByUid($userId);
        $this->view->assign('currentUser', $feUser);

        $facetsFields = $this->searchService->getFacetFields();
        $activeFacets = $this->request->getArguments()['facet'];
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
                            // Save facet value counter and remove it from array
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

        $this->view->assign('editAllowed', $processEditAllowedByCurrentUser);
        $this->view->assign("processes", $processes);

        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }

        $this->view->assign("importedPath", $importedPath);
        $this->view->assign("query", $query);
    }

    /**
     * @param Campaign $campaign
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
    }

    /**
     * @throws AspectNotFoundException
     * @throws UnknownObjectException
     * @throws IllegalObjectTypeException
     */
    public function editMetadataAction(Process $process): ResponseInterface
    {
        $userId = $this->context->getPropertyFromAspect('frontend.user', 'id');
        /** @var FrontendUser $user */
        $feUser = $this->frontendUserRepository->findByUid($userId);

        $processType = $process->getType();

        if ($process->hasFeUser() && $process->getFeUser() !== $feUser) {
            throw new \Exception('Process already taken');
        }

        // check if user is currently editing
        $currentlyEditingProcess = $this->processRepository->findOneByFeUser($feUser);
        if ($currentlyEditingProcess && $currentlyEditingProcess !== $process) {
            // TODO: The user should be asked whether the process currently being processed should be released
            throw new \Exception('User is already editing another process');
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
                $tab = $meta['tab'] === '' ? 'default' : $meta['tab'];
                if (!isset($sorted[$tab])) {
                    $sorted[$tab] = [];
                }
                $sorted[$tab][$fieldName] = $meta;
            }

            // set default at the end
            asort($sorted);
            $defaultValues = $sorted['default'];
            unset($sorted['default']);
            $sorted['default'] = $defaultValues;
            $dbConfigArraySorted[$processType] = $sorted;

            $this->view->assign('dbConfigTabSorted', $dbConfigArraySorted);

        } else {
            throw new \Exception('Metadata configuration missing');
        }

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
        $this->view->assign("processImagesInfo", $this->processImageInfo($process));
        $this->view->assign('process', $process);
        $this->view->assign('formValues', $formValues);

        return $this->htmlResponse();
    }

    public function processImageInfo(Process $process, $imageType = 'default')
    {
        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) === '/') {
            $importedPath = $importedPath . '/';
        }
        $processImagesInfo = [];
        $i = 0;
        foreach ($process->getImages() as $image) {
            $path = $importedPath .'/'. $process->getRecordIdentifier() . '/images/' . $imageType . '/' . $image;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $processImagesInfo[$i]['image'] = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $imageSize = getimagesize($path);
            $processImagesInfo[$i]['width'] = $imageSize[0];
            $processImagesInfo[$i]['height'] = $imageSize[1];
            $i++;
        }

        return $processImagesInfo;
    }

    /**
     * @throws UnknownObjectException
     * @throws NoSuchArgumentException
     * @throws IllegalObjectTypeException
     * @throws \Exception
     */
    public function saveFormAction(): ResponseInterface
    {
        $trustedMetadata = $this->request->getArgument('metadata');
        $processId = $this->request->getArgument('process');
        /* @var $process \Wlb\Crowdsourcing\Domain\Model\Process */
        $process = $this->processRepository->findByUid($processId);

        if ($this->request->hasArgument('abort')) {
            $process->resetFeUser();

            $lastHistoryProcess = $this->processHistoryRepository->getLastHistory($process->getRecordIdentifier());
            $data = $lastHistoryProcess->toArray();

            $this->processHistoryService->restoreFromArray($process, $data);
            $this->persistenceManager->persistAll();
        }

        if ($this->request->hasArgument('cache')) {
            $process->updateMetadata($trustedMetadata);
            $this->persistenceManager->persistAll();

            // index data
            $this->solrIndexer->indexDocument($process);
        }

        if ($this->request->hasArgument('save')) {
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

            // index data
            $this->solrIndexer->indexDocument($process);

        }

        $this->processRepository->update($process);

        return (new ForwardResponse('index'));
    }
}
