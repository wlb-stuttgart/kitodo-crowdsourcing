<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use Wlb\Crowdsourcing\Domain\Model\Process;

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

    /**
     * Returns the fe_user uid and edit count of the user who has edited the most processes overall.
     *
     * @return array{fe_user: int, edit_count: int}|null
     * @throws \Doctrine\DBAL\Exception
     */
    public function findMostActiveFeUserAllTime(): ?array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_processhistory');

        $queryBuilder = $connection->createQueryBuilder();
        $row = $queryBuilder
            ->select('fe_user')
            ->addSelectLiteral('COUNT(*) AS edit_count')
            ->from('tx_crowdsourcing_domain_model_processhistory')
            ->where(
                $queryBuilder->expr()->neq('fe_user', $queryBuilder->createNamedParameter(0, \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->groupBy('fe_user')
            ->orderBy('edit_count', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }

    /**
     * Returns the fe_user uid and edit count of the user who has edited the most processes in the previous calendar month.
     *
     * @return array{fe_user: int, edit_count: int}|null
     * @throws \Doctrine\DBAL\Exception
     */
    public function findMostActiveFeUserLastMonth(): ?array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $startOfLastMonth = $now->modify('first day of last month')->setTime(0, 0, 0);
        $startOfThisMonth = $now->modify('first day of this month')->setTime(0, 0, 0);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_processhistory');

        $queryBuilder = $connection->createQueryBuilder();
        $row = $queryBuilder
            ->select('fe_user')
            ->addSelectLiteral('COUNT(*) AS edit_count')
            ->from('tx_crowdsourcing_domain_model_processhistory')
            ->where(
                $queryBuilder->expr()->neq('fe_user', $queryBuilder->createNamedParameter(0, \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->andWhere(
                $queryBuilder->expr()->gte('crdate', $queryBuilder->createNamedParameter($startOfLastMonth->getTimestamp(), \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->andWhere(
                $queryBuilder->expr()->lt('crdate', $queryBuilder->createNamedParameter($startOfThisMonth->getTimestamp(), \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->groupBy('fe_user')
            ->orderBy('edit_count', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }

    /**
     * @param string $identifier
     * @return mixed[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function findFeUserIdsByRecordIdentifier(string $identifier): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_processhistory');

        $queryBuilder = $connection->createQueryBuilder();
        return $queryBuilder
            ->select('fe_user')
            ->from('tx_crowdsourcing_domain_model_processhistory')
            ->where(
                $queryBuilder->expr()->eq(
                    'record_identifier',
                    $queryBuilder->createNamedParameter($identifier
                )
            ))
            ->andWhere(
                $queryBuilder->expr()->neq(
                    'fe_user',
                    $queryBuilder->createNamedParameter(0)
                )
            )
            ->groupBy('fe_user')
            ->orderBy('fe_user', 'ASC')
            ->executeQuery()
            ->fetchFirstColumn();
    }

    /**
     * Returns the count of distinct documents (by record_identifier) a user has edited,
     * grouped by the current state of the document in the process table.
     *
     * @return array<string, int> e.g. ['NEW' => 3, 'CORRECTION' => 1, ...]
     * @throws \Doctrine\DBAL\Exception
     */
    public function countDistinctByFeUserGroupedByState(int $feUserUid): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_processhistory');

        $queryBuilder = $connection->createQueryBuilder();
        $rows = $queryBuilder
            ->select('p.state')
            ->addSelectLiteral('COUNT(DISTINCT ph.record_identifier) AS cnt')
            ->from('tx_crowdsourcing_domain_model_processhistory', 'ph')
            ->join(
                'ph',
                'tx_crowdsourcing_domain_model_process',
                'p',
                $queryBuilder->expr()->eq('ph.record_identifier', $queryBuilder->quoteIdentifier('p.record_identifier'))
            )
            ->where($queryBuilder->expr()->eq(
                'ph.fe_user',
                $queryBuilder->createNamedParameter($feUserUid, \Doctrine\DBAL\ParameterType::INTEGER)
            ))
            ->groupBy('p.state')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['state']] = (int)$row['cnt'];
        }
        return $result;
    }

    /**
     * @param $process
     * @return array|object[]|QueryResultInterface
     * @throws InvalidQueryException
     */
    public function getProcessHistory($identifier)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('record_identifier', $identifier)
        );
        $query->setOrderings(['uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING]);
        $result = $query->execute();
        return $result;
    }

    /**
     * Returns the top 10 editors for each campaign.
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function findTopTenEditorsByCampaign(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_processhistory');

        $queryBuilder = $connection->createQueryBuilder();

        $states = [
            Process::WORKFLOW_STATE_NEW,
            Process::WORKFLOW_STATE_CORRECTION,
            Process::WORKFLOW_STATE_FINAL_CORRECTION,
        ];

        $rows = $queryBuilder
            ->select('p.campaign', 'ph.fe_user')
            ->addSelectLiteral('COUNT(DISTINCT ph.record_identifier) AS edit_count')
            ->from('tx_crowdsourcing_domain_model_processhistory', 'ph')
            ->join(
                'ph',
                'tx_crowdsourcing_domain_model_process',
                'p',
                $queryBuilder->expr()->eq(
                    'ph.record_identifier',
                    $queryBuilder->quoteIdentifier('p.record_identifier')
                )
            )
            ->where($queryBuilder->expr()->gt('p.campaign', 0))
            ->andWhere($queryBuilder->expr()->neq('ph.fe_user', 0))
            ->andWhere(
                $queryBuilder->expr()->in(
                    'ph.state',
                    $queryBuilder->createNamedParameter(
                        $states,
                        \Doctrine\DBAL\ArrayParameterType::STRING
                    )
                )
            )
            ->groupBy('p.campaign', 'ph.fe_user')
            ->orderBy('p.campaign', 'ASC')
            ->addOrderBy('edit_count', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $campaignUid = (int)$row['campaign'];
            if (!isset($result[$campaignUid])) {
                $result[$campaignUid] = [];
            }

            if (count($result[$campaignUid]) < 10) {
                $result[$campaignUid][] = [
                    'fe_user' => (int)$row['fe_user'],
                    'edit_count' => (int)$row['edit_count']
                ];
            }
        }

        return $result;
    }

    /**
     * Returns the count of distinct posters edited by a frontend user,
     * grouped by campaign for the states NEW, CORRECTION and FINAL_CORRECTION.
     *
     * @return array<int, int>
     * @throws \Doctrine\DBAL\Exception
     */
    public function countEditedProcessesByFeUserGroupedByCampaign(int $feUserUid): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_processhistory');

        $queryBuilder = $connection->createQueryBuilder();

        $states = [
            Process::WORKFLOW_STATE_NEW,
            Process::WORKFLOW_STATE_CORRECTION,
            Process::WORKFLOW_STATE_FINAL_CORRECTION,
        ];

        $rows = $queryBuilder
            ->select('p.campaign')
            ->addSelectLiteral('COUNT(DISTINCT ph.record_identifier) AS cnt')
            ->from('tx_crowdsourcing_domain_model_processhistory', 'ph')
            ->join(
                'ph',
                'tx_crowdsourcing_domain_model_process',
                'p',
                $queryBuilder->expr()->eq(
                    'ph.record_identifier',
                    $queryBuilder->quoteIdentifier('p.record_identifier')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'ph.fe_user',
                    $queryBuilder->createNamedParameter($feUserUid, \Doctrine\DBAL\ParameterType::INTEGER)
                )
            )
            ->andWhere($queryBuilder->expr()->isNotNull('p.campaign'))
            ->andWhere($queryBuilder->expr()->gt('p.campaign', 0))
            ->andWhere(
                $queryBuilder->expr()->in(
                    'ph.state',
                    $queryBuilder->createNamedParameter(
                        $states,
                        \Doctrine\DBAL\ArrayParameterType::STRING
                    )
                )
            )
            ->groupBy('p.campaign')
            ->orderBy('p.campaign', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[(int)$row['campaign']] = (int)$row['cnt'];
        }

        return $result;
    }


    /**
     * Returns the count of distinct posters edited by a frontend user in the previous calendar month,
     * grouped by campaign for the states NEW, CORRECTION and FINAL_CORRECTION.
     *
     * @return array<int, int>
     * @throws \Doctrine\DBAL\Exception
     */
    public function countEditedProcessesByFeUserGroupedByCampaignLastMonth(int $feUserUid): array
    {
        // Get the start and end of the previous month
        $endOfPeriod = new \DateTimeImmutable('first day of this month 00:00:00');
        $startOfPeriod = $endOfPeriod->modify('-1 month');

        // Get the start of the last 30 days
        //$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        //$startOfPeriod = $now->modify('-30 days');
        //$endOfPeriod   = $now;

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_processhistory');

        $queryBuilder = $connection->createQueryBuilder();

        $states = [
            Process::WORKFLOW_STATE_NEW,
            Process::WORKFLOW_STATE_CORRECTION,
            Process::WORKFLOW_STATE_FINAL_CORRECTION,
        ];

        $rows = $queryBuilder
            ->select('p.campaign')
            ->addSelectLiteral('COUNT(DISTINCT ph.record_identifier) AS cnt')
            ->from('tx_crowdsourcing_domain_model_processhistory', 'ph')
            ->join(
                'ph',
                'tx_crowdsourcing_domain_model_process',
                'p',
                $queryBuilder->expr()->eq(
                    'ph.record_identifier',
                    $queryBuilder->quoteIdentifier('p.record_identifier')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'ph.fe_user',
                    $queryBuilder->createNamedParameter($feUserUid, \Doctrine\DBAL\ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->gte(
                    'ph.crdate',
                    $queryBuilder->createNamedParameter($startOfPeriod->getTimestamp(), \Doctrine\DBAL\ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->lt(
                    'ph.crdate',
                    $queryBuilder->createNamedParameter($endOfPeriod->getTimestamp(), \Doctrine\DBAL\ParameterType::INTEGER)
                )
            )
            ->andWhere($queryBuilder->expr()->isNotNull('p.campaign'))
            ->andWhere($queryBuilder->expr()->gt('p.campaign', 0))
            ->andWhere(
                $queryBuilder->expr()->in(
                    'ph.state',
                    $queryBuilder->createNamedParameter(
                        $states,
                        \Doctrine\DBAL\ArrayParameterType::STRING
                    )
                )
            )
            ->groupBy('p.campaign')
            ->orderBy('p.campaign', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];

        foreach ($rows as $row) {
            $result[(int)$row['campaign']] = (int)$row['cnt'];
        }

        return $result;
    }

}
