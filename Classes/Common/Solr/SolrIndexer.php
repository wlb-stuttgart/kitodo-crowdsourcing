<?php

namespace Wlb\Crowdsourcing\Common\Solr;

use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use Wlb\Crowdsourcing\Common\XMLExtractor;
use Wlb\Crowdsourcing\Domain\Model\Campaign;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessHistoryRepository;
use Wlb\Crowdsourcing\Services\IndexFieldConfigReader;

class SolrIndexer
{
    /**
     * Holds the configuration of the index fields
     *
     * @var array
     */
    private $config;

    /**
     * @param IndexFieldConfigReader $indexFieldConfigReader
     */
    public function __construct(
        private readonly IndexFieldConfigReader $indexFieldConfigReader,
        private readonly XMLExtractor $xmlExtractor,
        private readonly MetadataConfigurationRepository $metadataConfigurationRepository,
        private readonly ProcessHistoryRepository $processHistoryRepository
    )
    {
        $this->config = $this->indexFieldConfigReader->getConfig();
    }

    /**
     * @param Typo3QuerySettings $querySettings
     * @return void
     */
    public function applyQuerySettings(Typo3QuerySettings $querySettings)
    {
        $this->metadataConfigurationRepository->setDefaultQuerySettings($querySettings);
        $this->processHistoryRepository->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param string $xmlData The XML metadata to be indexed
     * @return void
     * @throws \Exception
     */
    public function indexDocument(Process $process)
    {
        $this->addDocument($process);
    }

    /**
     * @param string $identifier
     * @param array $indexData
     * @return void
     * @throws \Exception
     */
    public function addDocument(Process $process)
    {
        $solr   = SolrClient::getInstance();
        $update = $solr->getClient()->createUpdate();
        $doc    = $update->createDocument();

        $identifier = $process->getRecordIdentifier();
        $indexData = $this->getDocument($process);

        if (!isset($identifier) || empty($identifier)) {
            throw new \Exception('Error while indexing: Document with no ID.');
        }

        $doc->setField('id', $identifier);
        $doc->setField('uid', $process->getUid());

        $campaign = $process->getCampaign();
        if ($campaign instanceof Campaign) {
            $doc->setField('campaign_tsi', $campaign->getUid());
        }

        $feUser = $process->getFeUser();
        if ($feUser instanceof FrontendUser) {
            $doc->setField('feUser_tsi', $feUser->getUid());
        }

        $feUserUids = $this->processHistoryRepository->findFeUserIdsByRecordIdentifier($process->getRecordIdentifier());
        if (is_array($feUserUids)) {
            $doc->setField('feUserHistory_tsi', $feUserUids);
        }

        foreach ($indexData as $key => $value) {
            $doc->setField($key, $value);
        }

        $update->addDocument($doc);

        $update->addCommit();

        $solr->getClient()->update($update);

    }

    /**
     * @param Process $process
     * @return void
     */
    public function deleteDocument(Process $process)
    {
        $solr   = SolrClient::getInstance();
        $update = $solr->getClient()->createUpdate();
        $update->addDeleteById($process->getRecordIdentifier());
        $update->addCommit();
        $solr->getClient()->update($update);
    }

    /**
     * Extracts the index relevant data (due to the index field configuration) from the given json data.
     *
     * @param string $xmlData
     * @return array
     * @throws \JsonPath\InvalidJsonException
     */
    public function getDocument(Process $process)
    {
        $xmlData = $process->getMetadata();
        $xml = simplexml_load_string($xmlData);
        $xml->registerXPathNamespace('kitodo', 'http://meta.kitodo.org/v1/');

        $result = [];

        // Use metadataconfiguration to define solr field config
        $queryResult = $this->metadataConfigurationRepository->findAll();
        if ($queryResult->count() !== 0) {
            /** @var MetadataConfiguration $dbConfiguration */
            $dbConfiguration = $queryResult->getFirst();
            $dbConfigArray = json_decode($dbConfiguration->getJson(), true);

            $indexConfig = [];
            foreach ($dbConfigArray[$process->getType()] as $metadataKey => $metadata) {
                if ($metadata['active'] === '1') {
                    if (is_array($metadata['children'])) {
                        $childNames = [];
                        $childFields = [];
                        foreach ($metadata['children'] as $metadataChildKey => $metdataChild) {
                            $childNames[$metadataChildKey] = true;
                        }
                        $childFields['_fields'][$metadataKey]['_fields'] = $childNames;
                        $indexConfig[$metadataKey . '_tsi'] = $childFields;


                        // prepare facet config
                        $childNames = [];
                        $childFields = [];
                        if (!empty($metadata['facet'])) {
                            $facets = explode('###', $metadata['facet']);

                            $i = 0;
                            foreach ($facets as $facet) {
                                $fieldRepresentation = explode('$', $facet);
                                foreach ($fieldRepresentation as $field) {
                                    if (!empty($field)) {
                                        if ($field === 'this') {
                                            foreach ($metadata['children'] as $metadataChildKey => $metdataChild) {
                                                $childNames[$metadataChildKey] = true;
                                            }
                                        } else {
                                            $childNames[trim($field)] = true;
                                        }
                                    }
                                }
                                $childFields['_fields'][$metadataKey]['_fields'] = $childNames;
                                $indexConfig[$metadataKey . '_' . $i .  '_faceting'] = $childFields;
                                $i++;
                            }
                        }

                    } else {
                        $indexConfig[$metadataKey . '_tsi'] = ['_fields' => [$metadataKey => true]];
                    }
                }
            }

            foreach ($indexConfig as $indexField => $indexFieldConfig) {
                $result[$indexField] = $this->xmlExtractor->extractData($indexFieldConfig, $xml);
            }

            $result['type_faceting'] = $process->getType();
            $result['state_faceting'] = $process->getState();

            if ($process->getCampaign()) {
                $result['campaign_faceting'] = $process->getCampaign()->getUid();
            }
        } else {
            throw new \Exception('Metadata configuration missing');
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function deleteAll(): bool
    {
        $solr   = SolrClient::getInstance();
        $update = $solr->getClient()->createUpdate();
        $update->addDeleteQuery('*:*');
        $update->addCommit();
        $solr->getClient()->update($update);
        return true;
    }
}
