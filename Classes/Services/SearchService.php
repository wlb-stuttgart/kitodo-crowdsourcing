<?php

namespace Wlb\Crowdsourcing\Services;

use Wlb\Crowdsourcing\Common\Solr\SolrSearcher;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class SearchService
{

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly ProcessRepository $processRepository,
        private readonly SolrSearcher $solrSearcher
    )
    {
    }

    /**
     * @param string $search
     * @return object[]|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function searchProcesses(string $search = '')
    {
        $query = empty($search)? '*' : $search;

        $results = [];

        $results = $this->solrSearcher->searchWithFacets($query);

        $documentIdentifiers = [];

        foreach($results as $result) {
            $documentIdentifiers[] = $result->id;
        }

        return $this->processRepository->findByIdentifierList($documentIdentifiers);
    }
}