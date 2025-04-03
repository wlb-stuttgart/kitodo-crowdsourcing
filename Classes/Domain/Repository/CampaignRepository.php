<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

class CampaignRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param $offset
     * @param $limit
     * @return object[]|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByPage($offset, $limit)
    {
        $query = $this->createQuery();
        $query->setLimit($limit);
        $query->setOffset($offset);
        return $query->execute();
    }

    /**
     * @return int
     */
    public function countAll()
    {
        $query = $this->createQuery();
        return $query->count();
    }
}
