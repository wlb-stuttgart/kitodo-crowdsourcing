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
            ->andWhere($queryBuilder->expr()->eq('p.hidden', 0))
            ->andWhere($queryBuilder->expr()->eq('p.deleted', 0))
            ->andWhere($queryBuilder->expr()->eq('c.hidden', 0))
            ->andWhere($queryBuilder->expr()->eq('c.deleted', 0))
            ->andWhere($queryBuilder->expr()->eq(
                'c.workflow_state',
                $queryBuilder->createNamedParameter(\Wlb\Crowdsourcing\Domain\Model\Campaign::WORKFLOW_STATE_PUBLISHED)
            ))
            ->andWhere(
                $queryBuilder->expr()->in(
                    'p.state',
                    $queryBuilder->createNamedParameter(
                        [
                            Process::WORKFLOW_STATE_NEW,
                            Process::WORKFLOW_STATE_CORRECTION,
                            Process::WORKFLOW_STATE_FINAL_CORRECTION,
                        ],
                        \Doctrine\DBAL\ArrayParameterType::STRING
                    )
                )
            )
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


    /**
     * Counts all processes that are part of a campaign.
     *
     * @return int
     */
    public function countAllActive() {
        $query = $this->createQuery();

        $constraints = [
            $query->logicalNot($query->equals('campaign', null)),
            $query->logicalNot($query->equals('campaign', 0)),
            $query->equals('hidden', 0),
            $query->equals('deleted', 0)
        ];

        return $query->execute()->count();
    }

    /**
     * Counts all processes that are part of a campaign with a specific state.
     *
     * @param string $state
     * @return int
     */
    public function countAllActiveByState(string $state) {
        $query = $this->createQuery();

        $constraints = [
            $query->logicalNot($query->equals('campaign', null)),
            $query->logicalNot($query->equals('campaign', 0)),
            $query->equals('hidden', 0),
            $query->equals('deleted', 0),
            $query->equals('state', $state),
        ];

        $query->matching(
            $query->logicalAnd(...$constraints)
        );

        return $query->execute()->count();
    }


    /**
     * Counts all active processes grouped by campaign.
     *
     * @return array<int, int>
     * @throws \Doctrine\DBAL\Exception
     */
    public function countAllActiveGroupedByCampaign(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_process');

        $queryBuilder = $connection->createQueryBuilder();

        $rows = $queryBuilder
            ->select('campaign')
            ->addSelectLiteral('COUNT(uid) AS count')
            ->from('tx_crowdsourcing_domain_model_process')
            ->where($queryBuilder->expr()->isNotNull('campaign'))
            ->andWhere($queryBuilder->expr()->gt('campaign', 0))
            ->andWhere($queryBuilder->expr()->eq('hidden', 0))
            ->andWhere($queryBuilder->expr()->eq('deleted', 0))
            ->groupBy('campaign')
            ->executeQuery()
            ->fetchAllAssociative();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int)$row['campaign']] = (int)$row['count'];
        }

        return $counts;
    }


    /**
     * Counts all active processes with a specific state grouped by campaign.
     *
     * @param string $state
     * @return array<int, int>
     * @throws \Doctrine\DBAL\Exception
     */
    public function countAllActiveByStateGroupedByCampaign(string $state): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_process');

        $queryBuilder = $connection->createQueryBuilder();

        $rows = $queryBuilder
            ->select('campaign')
            ->addSelectLiteral('COUNT(uid) AS count')
            ->from('tx_crowdsourcing_domain_model_process')
            ->where($queryBuilder->expr()->isNotNull('campaign'))
            ->andWhere($queryBuilder->expr()->gt('campaign', 0))
            ->andWhere($queryBuilder->expr()->eq('hidden', 0))
            ->andWhere($queryBuilder->expr()->eq('deleted', 0))
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'state',
                    $queryBuilder->createNamedParameter($state)
                )
            )
            ->groupBy('campaign')
            ->executeQuery()
            ->fetchAllAssociative();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int)$row['campaign']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Counts active processes grouped by campaign for statistic overview.
     *
     * The result contains:
     * - countAll: all active processes assigned to a campaign
     * - countUnedited: NEW processes without assigned fe_user
     * - countInProgress: NEW processes with assigned fe_user, CORRECTION and FINAL_CORRECTION processes
     * - countCompleted: COMPLETED processes
     *
     * @return array<int, array{countAll: int, countUnedited: int, countInProgress: int, countCompleted: int}>
     * @throws \Doctrine\DBAL\Exception
     */
    public function countStatisticsGroupedByCampaign(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_process');

        $queryBuilder = $connection->createQueryBuilder();

        $rows = $queryBuilder
            ->select('campaign')
            ->addSelectLiteral('COUNT(uid) AS count_all')
            ->addSelectLiteral(
                'SUM(CASE WHEN state = ' . $queryBuilder->quote(Process::WORKFLOW_STATE_NEW)
                . ' AND (fe_user IS NULL OR fe_user = 0) THEN 1 ELSE 0 END) AS count_unedited'
            )
            ->addSelectLiteral(
                'SUM(CASE WHEN (state = ' . $queryBuilder->quote(Process::WORKFLOW_STATE_NEW)
                . ' AND fe_user IS NOT NULL AND fe_user > 0) OR state IN ('
                . $queryBuilder->quote(Process::WORKFLOW_STATE_CORRECTION)
                . ', '
                . $queryBuilder->quote(Process::WORKFLOW_STATE_FINAL_CORRECTION)
                . ') THEN 1 ELSE 0 END) AS count_in_progress'
            )
            ->addSelectLiteral(
                'SUM(CASE WHEN state = ' . $queryBuilder->quote(Process::WORKFLOW_STATE_COMPLETED)
                . ' THEN 1 ELSE 0 END) AS count_completed'
            )
            ->from('tx_crowdsourcing_domain_model_process')
            ->where($queryBuilder->expr()->isNotNull('campaign'))
            ->andWhere($queryBuilder->expr()->gt('campaign', 0))
            ->andWhere($queryBuilder->expr()->eq('hidden', 0))
            ->andWhere($queryBuilder->expr()->eq('deleted', 0))
            ->groupBy('campaign')
            ->executeQuery()
            ->fetchAllAssociative();

        $statistics = [
            'countAll' => [],
            'countUnedited' => [],
            'countInProgress' => [],
            'countCompleted' => [],
        ];

        foreach ($rows as $row) {
            $campaignUid = (int)$row['campaign'];

            $statistics['countAll'][$campaignUid] = (int)$row['count_all'];
            $statistics['countUnedited'][$campaignUid] = (int)$row['count_unedited'];
            $statistics['countInProgress'][$campaignUid] = (int)$row['count_in_progress'];
            $statistics['countCompleted'][$campaignUid] = (int)$row['count_completed'];
        }

        return $statistics;
    }
}

