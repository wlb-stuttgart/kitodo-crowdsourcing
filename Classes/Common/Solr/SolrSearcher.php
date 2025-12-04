<?php

namespace Wlb\Crowdsourcing\Common\Solr;

use Solarium\Core\Query\Result\ResultInterface;
use Wlb\Crowdsourcing\Common\IndexFields;

class SolrSearcher
{
    /**
     * @var SolrClient
     */
    private $client;

    public function __construct()
    {
        $this->client = SolrClient::getInstance()->getClient();
    }

    /**
     * @param array $activeCampaigns
     * @param string $queryString
     * @param int $start
     * @param int $rows
     * @param array $facetFields
     * @param array $activeFacets
     * @return ResultInterface
     */
    public function searchWithFacets(
        array $activeCampaigns,
        string $queryString,
        int $start = 0,
        int $rows = 50,
        array $facetFields = [],
        array $activeFacets = []
    ): ResultInterface
    {
        $query = $this->client->createSelect();

        $facetQuery = '';
        if (!empty($activeFacets)) {
            foreach ($activeFacets as $facetField => $facet) {
                $facetQuery .= $facetField.':"'.key($facet).'"';
            }
            $queryString = $queryString . ' AND ' . $facetQuery;
        }

        $query->setQuery($queryString);
        $query->addSort('id', $query::SORT_ASC);

        // Deliver only campaigns that are published.
        if (!empty($activeCampaigns)) {
            $filterQuery = $query->createFilterQuery('campaignFilter');
            $filterQuery->setQuery('campaign_tsi:'.implode(' OR ', $activeCampaigns));
            $query->addFilterQuery($filterQuery);
        } else {
            $emptyFilterQuery = $query->createFilterQuery('force_empty_result');
            $emptyFilterQuery->setQuery('NOT id:[* TO *]');
            $query->addFilterQuery($emptyFilterQuery);
        }

        $query->setStart($start);
        $query->setRows($rows);

        $facetSet = $query->getFacetSet();

        if (!empty($facetFields)) {
            foreach ($facetFields as $facetLabel => $facetField) {
                foreach ($facetField as $facetName => $facetValue) {
                    $facetSet->createFacetField($facetName)->setField($facetName);
                }
            }
        }

        $resultset = $this->client->select($query);

        return $resultset;
    }

    /*
    public function getResults($resultSet)
    {
        $results = [];

        foreach ($resultSet as $document) {
            $id           = $document->id;
            $displayData  = $this->fetchDisplayData($id);
            $mergedResult = array_merge($document->getFields(), $displayData);
            $results[]    = $mergedResult;
        }

        return $results;
    }
    */

    public function getFacets($resultSet)
    {
        $facets = [];
        $facetSet = $resultSet->getFacetSet();
        foreach ($facetSet as $facetField => $facetResult) {
            $facets[$facetField] = $facetResult->getFacetCounts();
        }

        return $facets;
    }

    /*
    private function fetchDisplayData($id)
    {
        // Step 1: Fetch display data from another source, such as a database
        // Example: Fetch display data based on the 'id'
        return $this->displayDataService->getDisplayDataById($id);
    }
    */
}
