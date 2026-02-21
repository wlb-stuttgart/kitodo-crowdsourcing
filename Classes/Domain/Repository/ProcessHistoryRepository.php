<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
}
