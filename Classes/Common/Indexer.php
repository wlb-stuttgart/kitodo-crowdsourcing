<?php

namespace Wlb\Crowdsourcing\Common;

use Wlb\Crowdsourcing\Common\Solr\Solr;
use Wlb\Crowdsourcing\Services\IndexFieldsService;

class Indexer
{
    /**
     * @access protected
     * @static
     * @var Solr Instance of Solr class
     */
    protected static Solr $solr;


    /**
     * @var IndexFieldsService
     */
    protected $indexFieldsService;


    /**
     * @param IndexFieldsService $indexFieldsService
     * @return void
     */
    public function __construct(IndexFieldsService $indexFieldsService)
    {
        $this->indexFieldsService = $indexFieldsService;
    }


    /**
     * @param string $jsonData The JSON metadata to be indexed
     * @return void
     * @throws \Exception
     */
    public function indexDocument(string $jsonData)
    {
        $this->addDocument($this->getDocument($jsonData));
    }

    /**
     * @param array $indexDocument
     * @return void
     * @throws \Exception
     */
    public function addDocument(array $indexDocument)
    {
        $solr   = Solr::getInstance();
        $update = $solr->getClient()->createUpdate();
        $doc    = $update->createDocument();

        if (!isset($indexDocument['id']) || empty($indexDocument['id'])) {
            throw new \Exception('Error while indexing: Document with no ID.');
        }

        foreach ($indexDocument as $key => $value) {
            $doc->setField($key, $value);
        }

        $update->addDocument($doc);
        $update->addCommit();


        $solr->getClient()->update($update);
    }


    /**
     * Extracts the index relevant data (due to the index field configuration) from the given json data.
     *
     * @param string $jsonData
     * @return array
     * @throws \JsonPath\InvalidJsonException
     */
    public function getDocument(string $jsonData)
    {
        $jsonDoc = new JsonDocument($jsonData);

        $indexFields = $this->indexFieldsService->load();

        $indexDocument = [];

        // Extract the data from the first level
        foreach ($indexFields as $fieldMapping) {
            $fieldJsonList = $jsonDoc->findByJsonPath($fieldMapping->getPath());

            if ($fieldJsonList) {
                foreach ($fieldJsonList as $fieldJson) {
                    $subPaths = $fieldMapping->getSubpaths();
                    if ($subPaths && is_array($subPaths)) {
                        $subfieldValues = [];
                        foreach ($subPaths as $subPath) {
                            $subFieldList = $fieldJson->findByJsonPath($subPath);
                            foreach ($subFieldList as $subFieldJson) {
                                $subfieldValues[] = $subFieldJson->toString();
                            }
                        }
                        $indexDocument[$fieldMapping->getName()][] = implode(" ", $subfieldValues);
                    } else {
                        $indexDocument[$fieldMapping->getName()][] = $fieldJson->toString();
                    }
                }
            }
        }

        // Convert values of a field (key) to string if the index field is not a multi value field.
        foreach ($indexFields as $fieldMapping) {
            $key = $fieldMapping->getName();
            if (array_key_exists($key, $indexDocument)) {
                if (!$fieldMapping->isMultivalue()) {
                    $indexDocument[$key] = implode(", ", $indexDocument[$key]);
                }
            }
        }


        return $indexDocument;
    }
}
