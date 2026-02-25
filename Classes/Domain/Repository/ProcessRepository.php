<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Model\Process;

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
        }

        $query->matching(
            $query->in('recordIdentifier', $identifiers)
        );

        $result = $query->execute();

        $items = $result->toArray();

        usort($items, function ($a, $b) use ($identifiers) {
            return array_search($a->getRecordIdentifier(), $identifiers)
                - array_search($b->getRecordIdentifier(), $identifiers);
        });

        return $items;
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
                $query->greaterThan('fe_user', 0),
                $query->logicalNot($query->equals('fe_user', null)),
                $query->lessThan('last_accessed', $cutoffTime->getTimestamp())
            )
        );

        return $query->execute()->toArray();
    }


    /**
     * Finds the current process associated with the given front-end user.
     *
     * @param FrontendUser $feUser The front-end user to find the current process for.
     * @return array The current process details associated with the given front-end user.
     */
    public function findCurrentProcessByFeUser(FrontendUser $feUser): ?Process
    {
        $query = $this->createQuery();

        $query->matching(
            $query->equals('feUser', $feUser->getUid())
        );
        $query->setOrderings([
            'lastAccessed' => QueryInterface::ORDER_DESCENDING
        ]);
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Finds a random process.
     *
     * @param FrontendUser $feUser
     * @return Process|null
     * @throws \Doctrine\DBAL\Exception
     * @throws \Random\RandomException
     */
    public function fetchRandomUnassignedProcess(FrontendUser $feUser): ?Process
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_process');

        // Build a subquery to find processes that are already edited by the user
        $subQueryBuilder = $connection->createQueryBuilder();
        $subQueryBuilder
            ->select('a.record_identifier')
            ->from('tx_crowdsourcing_domain_model_process', 'a')
            ->join('a', 'tx_crowdsourcing_domain_model_processhistory', 'b',
                $subQueryBuilder->expr()->eq(
                    'b.record_identifier',
                    $subQueryBuilder->quoteIdentifier('a.record_identifier')
                ))
            ->where(
                $subQueryBuilder->expr()->eq('b.fe_user', ':feUserUid')
            );

        // Find all processes that are not assigned to any user and belong to an active campaign
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->from('tx_crowdsourcing_domain_model_process', 'p')
            ->join('p', 'tx_crowdsourcing_domain_model_campaign', 'c',
                $queryBuilder->expr()->eq('p.campaign', 'c.uid')
            )
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('p.fe_user', 0),
                    $queryBuilder->expr()->isNull('p.fe_user')
                )
            )
            ->andWhere($queryBuilder->expr()->gt('p.campaign', 0))
            ->andWhere($queryBuilder->expr()->isNotNull('p.campaign'))
            ->andWhere($queryBuilder->expr()->eq('c.workflow_state', $queryBuilder->createNamedParameter(\Wlb\Crowdsourcing\Domain\Model\Campaign::WORKFLOW_STATE_PUBLISHED)))
            ->andWhere(
                $queryBuilder->expr()->notIn('p.record_identifier', $subQueryBuilder->getSQL())
            )
            ->setParameter('feUserUid', $feUser->getUid());

        // Count the total available processes
        $queryBuilder->count('p.uid');
        $totalAvailable = $queryBuilder->executeQuery()->fetchOne();

        if ($totalAvailable > 0) {
            // Select a random process by offset
            $randomOffset = random_int(0, $totalAvailable - 1);

            $queryBuilder
                ->select('p.*')
                ->setFirstResult($randomOffset)
                ->setMaxResults(1);

            $process = $queryBuilder->executeQuery()->fetchAssociative();

            // Handle race condition if a record was processed/deleted between count and select
            if ($process === false && $totalAvailable > 0) {
                $process = $queryBuilder
                    ->setFirstResult(0)
                    ->setMaxResults(1)
                    ->executeQuery()
                    ->fetchAssociative();
            }

            if ($process) {
                return $this->findByUid($process['uid']);
            }
        }

        return null;
    }


    /*
    public function batchByCampaign(int $campaignUid, int $limit, int $offset)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->equals('campaign', $campaignUid)
        );

        $query->setOrderings([
            'uid' => QueryInterface::ORDER_ASCENDING
        ]);
        $query->setLimit($limit);
        $query->setOffset($offset);

        return $query->execute();
    }
    */
}