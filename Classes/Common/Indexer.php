<?php

namespace Wlb\Crowdsourcing\Common;

use JsonPath\JsonObject;
use Wlb\Crowdsourcing\Common\Solr\Solr;

class Indexer
{
    /**
     * @access protected
     * @static
     * @var Solr Instance of Solr class
     */
    protected static Solr $solr;


    public function addDocument()
    {
        $solr = Solr::getInstance();

        // Create an update query
        $update = $solr->getClient()->createUpdate();


        $doc = $update->createDocument();
        $doc->setField('id', md5(time()));
        $doc->setField('uid', "100");
        $doc->setField('pid',2);
        $doc->setField('author_tsi',
            [
            json_encode(['firstname' => 'Max', 'lastname' => 'Mustermann', 'role' => 'editor']),
            json_encode(['firstname' => 'Test', 'lastname' => 'Schmidt', 'role' => 'publisher'])
            ]
        );


        // Add the document to the update query
        $update->addDocument($doc);
        $update->addCommit();

        // Execute the update query
        $result = $solr->getClient()->update($update);

        //if ($result->getStatus() == 0) {
        //} else {
        // "Error adding document.";
        //}
    }


    /**
     * @param $metadata string
     * @return void
     */
    public function getDocument(string $metadata)
    {


        if (empty($metadata)) {
            $metadata = '{
  "signature": "PLSH-000177",
  "type": "Plakat",
  "originCountry": "XA-DE",
  "publicationPlace": "Berlin",
  "year1": "2012",
  "year2": "2012",
  "person": [{
    "firstname": "Bernd",
    "lastname": "Langer",
    "role": "GeistigeR SchöpferIn",
    "misc": "cre"
  },
    {
    "firstname": "Bernd2",
    "lastname": "Langer2",
    "role": "GeistigeR SchöpferIn2",
    "misc": "cre2"
  }],
 "corporation": {
    "name": "Kunsthaus Tacheles",
    "role": "Herausgebendes Organ",
    "misc": "isb"
  },
  "keywords": "Kultur,Kunst",
  "scope": "1 Plakat",
  "imageSizes": "ca. 42 x 59 cm",
  "illustrationIndication": "Siebdruck",
  "language": "ger"
}';
        }

        $jsonDoc = new JsonDocument($metadata);

        $indexMapping = new IndexMapping();

        $indexDocument = [];

        // Extract the data from the first level
        foreach ($indexMapping as $fieldMapping) {
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
                        $indexDocument[$fieldMapping->getName()][] = implode(",", $subfieldValues);
                    } else {
                        $indexDocument[$fieldMapping->getName()][] = $fieldJson->toString();
                    }
                }
            }
        }

        foreach ($indexMapping as $fieldMapping) {
            $key = $fieldMapping->getName();
            if (array_key_exists($key, $indexDocument)) {
                if (!$fieldMapping->isMultivalue()) {
                    $indexDocument[$key] = implode(",", $indexDocument[$key]);
                }
            }
        }

        //echo "<pre>";
        //print_r($indexDocument);
        //echo "</pre>";

    }
}
