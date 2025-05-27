<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class ProcessHistoryRepository extends ProcessRepository
{

    public function getLastHistory($identifier)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('record_identifier', $identifier)
        );
        $query->setOrderings(['uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);
        $query->setLimit(1);
        $result = $query->execute();
        return $result->getFirst();

    }
}
