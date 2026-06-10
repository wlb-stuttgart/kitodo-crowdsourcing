<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

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
    public function findAllActiveOrderedByCreationDate()
    {
        $query = $this->createQuery();

        $query->setOrderings([
            'crdate' => QueryInterface::ORDER_DESCENDING,
        ]);

        $query->matching(
            $query->logicalAnd(
                $query->equals('hidden', 0),
                $query->equals('deleted', 0),
                $query->equals('workflow_state', Campaign::WORKFLOW_STATE_PUBLISHED)
            )
        );

        return $query->execute();
    }
}
