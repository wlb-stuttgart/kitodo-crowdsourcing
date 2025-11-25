<?php
namespace Wlb\Crowdsourcing\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use Wlb\Crowdsourcing\Domain\Model\SearchResult;
use Wlb\Crowdsourcing\Services\SearchService;

class SearchResultPaginator extends AbstractPaginator
{
    /**
     * @var SearchService
     */
    private $searchService;

    /**
     * @var SearchResult
     */
    private $searchResult;

    /**
     * @var array
     */
    private $paginatedItems = [];

    public function __construct(
        SearchService $searchService,
        int $currentPage = 1,
        int $itemsPerPage = 10
    ) {
        $this->searchService = $searchService;
        //$this->searchResult = $searchResult;

        if ($this->searchResult === null) {
            $this->searchResult = $searchService->searchProcesses($currentPage-1, $itemsPerPage);
        }

        $this->setCurrentPageNumber($currentPage);
        $this->setItemsPerPage($itemsPerPage);
        $this->updateInternalState();
    }


    public function getSearchResult(): SearchResult
    {
        return $this->searchResult;
    }

    /**
     * @return iterable|array
     */
    public function getPaginatedItems(): iterable
    {
        $this->paginatedItems = $this->searchResult->getProcesses();
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->searchResult = $this->searchService->searchProcesses($offset, $itemsPerPage);
        $this->paginatedItems =  $this->searchResult->getProcesses();
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->searchResult->getNumFound();
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }
}
