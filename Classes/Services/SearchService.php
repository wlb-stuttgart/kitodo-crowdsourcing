<?php

namespace Wlb\Crowdsourcing\Services;

use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Model\SearchResult;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class SearchService
{
    /**
     * @var string
     */
    protected $search = '';

    /**
     * @var array
     */
    protected $facets = [];

    /**
     * @var array
     */
    protected $activeFacets = [];

    /**
     * @var FrontendUser
     */
    protected $feUser;

    /**
     * @var bool
     */
    protected $backendSearch = false;

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly SolrSearcher $solrSearcher,
        private readonly MetadataConfigurationRepository $metadataConfigurationRepository
    )
    {
    }

    public function setBackendSearch(): void
    {
        $this->backendSearch = true;
    }

    /**
     * @param string $search
     * @param array $facets
     * @param array $activeFacets
     * @return void
     */
    public function setQuery(string $search = '', array $facets = [], array $activeFacets = []): void
    {
        $this->search = $search;
        $this->facets = $facets;
        $this->activeFacets = $activeFacets;
    }

    public function setFeUser(FrontendUser $feUser): void
    {
        $this->feUser = $feUser;
    }

    /**
     * @return FrontendUser|null
     */
    public function getFeUser(): FrontendUser|null
    {
        return $this->feUser;
    }

    /**
     * @param $offset
     * @param $itemsPerPage
     * @param $backend
     * @return SearchResult
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function searchProcesses($offset = 0, $itemsPerPage = 50)
    {
        $query = empty($this->search)? '*' : $this->search;

        $results = $this->solrSearcher->searchWithFacets(
            $this->campaignRepository->getActiveCampaignUids(),
            $query, $offset, $itemsPerPage, $this->facets, $this->activeFacets, $this->getFeUser(), $this->backendSearch
        );

        $documentIdentifiers = [];
        foreach($results as $result) {
            $documentIdentifiers[] = $result->id;
        }

        $numFound = $results->getData()['response']['numFound'] ?? 0;

        $searchResult = new SearchResult();
        $searchResult->setProcesses($this->processRepository->findByIdentifierList($documentIdentifiers));
        $searchResult->setFacets($results->getData()['facet_counts']['facet_fields'] ?? []);
        $searchResult->setQuery($query);
        $searchResult->setNumFound($numFound);

        return $searchResult;

    }

    /**
     * @return int
     */
    public function getTotalCount(): int
    {
        $query = empty($this->search)? '*' : $this->search;
        $results = $this->solrSearcher->searchWithFacets(
            $this->campaignRepository->getActiveCampaignUids(),
            $query, 0, 0, $this->facets, $this->activeFacets, null, $this->backendSearch
        );

        return (int)$results->getData()['response']['numFound'] ?? 0;
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
            $facets['state']['state_faceting'] = false;
            $facets['type']['type_faceting'] = false;
            $facets['campaign']['campaign_faceting'] = false;

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