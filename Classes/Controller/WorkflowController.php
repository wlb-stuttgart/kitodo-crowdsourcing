<?php

namespace Wlb\Crowdsourcing\Controller;

use \DOMDocument;
use \DOMXPath;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Model\Campaign;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

class WorkflowController extends ActionController
{

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly MetadataConfigurationRepository $metadataConfigurationRepository,
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
        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) !== '/') {
        } else {
            $importedPath = $importedPath . '/';
        }
        $this->view->assign('importedPath', $importedPath);
        $this->view->assign('campaign', $campaign);
    }

    public function editMetadataAction(Process $process): ResponseInterface
    {
        $queryResult = $this->metadataConfigurationRepository->findAll();
        if ($queryResult->count() !== 0) {
            /** @var MetadataConfiguration $dbConfiguration */
            $dbConfiguration = $queryResult->getFirst();
            $dbConfigArray = json_decode($dbConfiguration->getJson(), true);

            $this->view->assign('dbConfig', $dbConfigArray);
        } else {
            throw new \Exception('Metadata configuration missing');
        }

        $this->view->assign('process', $process);

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
                            $formValues[$metadataKey][$i][$metadataChildValue->getAttribute('name')] = $metadataChildValue->nodeValue;
                        }
                    }
                    $i++;
                }
            }
        }

        $this->view->assign('formValues', $formValues);

        return $this->htmlResponse();
    }

    public function saveFormAction(): ResponseInterface
    {
        $untrustedMetadata = $this->request->getParsedBody()['metadata'];
        $trustedMetadata = $this->request->getArgument('metadata');

        debug($trustedMetadata);
        debug($untrustedMetadata);

        exit;

        return (new ForwardResponse('index'));
    }
}
