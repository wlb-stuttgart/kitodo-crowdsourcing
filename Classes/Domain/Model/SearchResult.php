<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

class SearchResult
{
    /**
     * @var iterable
     */
    protected $processes;

    /**
     * @var array
     */
    protected $facets;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var int
     */
    protected $numFound;


    /**
     * @return iterable
     */
    public function getProcesses(): iterable
    {
        return $this->processes;
    }

    /**
     * @param iterable $processes
     * @return void
     */
    public function setProcesses(iterable $processes): void
    {
        $this->processes = $processes;
    }

    /**
     * @return array
     */
    public function getFacets(): array
    {
        return $this->facets;
    }

    /**
     * @param array $facets
     * @return void
     */
    public function setFacets(array $facets): void
    {
        $this->facets = $facets;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return void
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    /**
     * @return int
     */
    public function getNumFound(): int
    {
        return $this->numFound;
    }

    /**
     * @param int $numFound
     * @return void
     */
    public function setNumFound(int $numFound): void
    {
        $this->numFound = $numFound;
    }

}
