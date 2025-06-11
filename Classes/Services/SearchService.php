<?php

namespace Wlb\Crowdsourcing\Services;

use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class SearchService
{

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly SolrSearcher $solrSearcher,
        private readonly MetadataConfigurationRepository $metadataConfigurationRepository
    )
    {
    }

    /**
     * @param string $search
     * @return object[]|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function searchProcesses(string $search = '', array $facets = [], $activeFacets = [])
    {
        $query = empty($search)? '*' : $search;

        $results = [];

        $results = $this->solrSearcher->searchWithFacets($query, 0, 50, $facets, $activeFacets);

        $documentIdentifiers = [];

        foreach($results as $result) {
            $documentIdentifiers[] = $result->id;
        }

        return [
            'processes' => $this->processRepository->findByIdentifierList($documentIdentifiers),
            'facets' => $results->getData()['facet_counts']['facet_fields']
        ];
    }

    public function getFacetFields()
    {
        // get db saved config
        $queryResult = $this->metadataConfigurationRepository->findAll();
        if ($queryResult->count() !== 0) {
            /** @var MetadataConfiguration $dbConfiguration */
            $dbConfiguration = $queryResult->getFirst();
            $dbConfigArray = json_decode($dbConfiguration->getJson(), true);

            $facets = [];

            // add static facets like state, type, etc.
            $facets['Status']['state_faceting'] = false;
            $facets['Typ']['type_faceting'] = false;

            foreach ($dbConfigArray as $docType => $docTypConfig) {
                foreach ($docTypConfig as $metadataName => $metadataConfig) {
                    if ($metadataConfig['facet'] !== '') {
                        $facetConfig = explode('###', $metadataConfig['facet']);
                        $facetIndex = 0;
                        foreach ($facetConfig as $facetFieldConfig) {
                            $facets[$metadataConfig['label']][$metadataName . '_' . $facetIndex . '_faceting'] = false;
                            $facetIndex++;
                        }
                    }
                }
            }
            return $facets;
        }
    }
}