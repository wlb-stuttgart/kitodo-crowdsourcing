<?php

namespace Wlb\Crowdsourcing\Common\Solr;

use Wlb\Crowdsourcing\Common\XMLExtractor;
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
        private readonly XMLExtractor $xmlExtractor
    )
    {
        $this->config = $this->indexFieldConfigReader->getConfig();
    }

    /**
     * @param string $xmlData The XML metadata to be indexed
     * @return void
     * @throws \Exception
     */
    public function indexDocument(string $identifier, string $xmlData)
    {
        $this->addDocument($identifier, $this->getDocument($xmlData));
    }

    /**
     * @param string $identifier
     * @param array $indexData
     * @return void
     * @throws \Exception
     */
    public function addDocument(string $identifier, array $indexData)
    {
        $solr   = SolrClient::getInstance();
        $update = $solr->getClient()->createUpdate();
        $doc    = $update->createDocument();

        if (!isset($identifier) || empty($identifier)) {
            throw new \Exception('Error while indexing: Document with no ID.');
        }

        $doc->setField('id', $identifier);

        foreach ($indexData as $key => $value) {
            $doc->setField($key, $value);
        }

        $update->addDocument($doc);

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
    public function getDocument(string $xmlData)
    {
        $xml = simplexml_load_string($xmlData);
        $xml->registerXPathNamespace('kitodo', 'http://meta.kitodo.org/v1/');

        $result = [];

        foreach ($this->config as $indexField => $indexFieldConfig) {
            $result[$indexField] = $this->xmlExtractor->extractData($indexFieldConfig, $xml);
        }

        // Convert values of a field (key) to string if the index field is not a multi value field.
        foreach ($this->config as $indexField => $indexFieldConfig) {
            if (array_key_exists($indexField, $result)) {
                if ($indexFieldConfig['_multivalue'] === false) {
                    $result[$indexField] = implode(", ", $result[$indexField]);
                }
            }
        }

        return $result;
    }
}
