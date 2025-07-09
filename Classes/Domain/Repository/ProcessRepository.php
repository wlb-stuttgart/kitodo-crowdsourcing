<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class ProcessRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @param $identifiers
     * @return array|object[]|QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByIdentifierList($identifiers): QueryResultInterface|array
    {
        $query = $this->createQuery();

        if (empty($identifiers)) {
            return [];
        } else {
            $query->matching(
                $query->in('recordIdentifier', $identifiers)
            );

            return $query->execute();
        }
    }

    /**
     * Finds processes that are considered stale and have an associated fe_user.
     *
     * @param \DateTime $cutoffTime The cutoff time to determine stale processes. Any process with a timestamp less than this time will be considered stale.
     * @return array List of stale processes with associated fe_user.
     * @throws InvalidQueryException
     */
    public function findStaleProcessesWithFeUser(\DateTime $cutoffTime): array
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->logicalNot($query->equals('fe_user', null)),
                $query->lessThan('last_accessed', $cutoffTime->getTimestamp())
            )
        );

        return $query->execute()->toArray();
    }
}