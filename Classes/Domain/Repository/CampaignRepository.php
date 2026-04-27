<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Wlb\Crowdsourcing\Domain\Model\Campaign;

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

    public function getActiveCampaignUids()
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('hidden', 0),
                $query->equals('workflowState', Campaign::WORKFLOW_STATE_PUBLISHED)
            )
        );

        return array_column($query->execute(true), 'uid');
    }


    /**
     * @return \mixed[][]|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllOrderedByCreationDate()
    {
        $query = $this->createQuery();

        $query->setOrderings([
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);

        return $query->execute();
    }
}
