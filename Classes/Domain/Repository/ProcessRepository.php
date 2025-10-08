<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
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
            'lastAccessed' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @param FrontendUser $feUser
     * @return Process|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Random\RandomException
     */
    public function findRandomForNonCurrentUsedr(FrontendUser $feUser): ?Process
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_process');

        $subQueryBuilder = $connection->createQueryBuilder();
        $subQueryBuilder
            ->select('record_identifier')
            ->from('tx_crowdsourcing_domain_model_processhistory')
            ->where(
                $subQueryBuilder->expr()->eq('fe_user', ':feUserUid')
            );

        $minPid = 1;
        $maxPidQuery = $connection->createQueryBuilder();
        $maxPid = (int)$maxPidQuery->select('uid')->from('tx_crowdsourcing_domain_model_process')->execute()->rowCount();
        $attempts = 10;
        $randomProcess = null;

            for ($i = 0; $i < $attempts; $i++) {
            $randomPid = random_int($minPid, $maxPid);

            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from('tx_crowdsourcing_domain_model_process')
                ->where(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq('fe_user', 0),
                        $queryBuilder->expr()->isNull('fe_user')
                    )
                )
                ->andWhere( $queryBuilder->expr()->eq('uid', ':uid'))
                ->andWhere(
                    $queryBuilder->expr()->notIn('record_identifier', $subQueryBuilder->getSQL())
                )
                ->setMaxResults(1)
                ->setParameter('uid', $randomPid)
                ->setParameter('feUserUid', $feUser->getUid());

            $process = $queryBuilder->executeQuery()->fetchAssociative();
            if ($process !== false) {
                $randomProcess = $process;
                break;
            }
        }


        if ($process) {
            return $this->findByUid($process['uid']);
        }

        return null;
    }


    public function findRandomForNonCurrentUser(FrontendUser $feUser): ?Process
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_process');

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

        $minId = $connection->fetchOne('SELECT MIN(uid) FROM tx_crowdsourcing_domain_model_process');
        $maxId = $connection->fetchOne('SELECT MAX(uid) FROM tx_crowdsourcing_domain_model_process');

        $attempts = 100;
        $randomProcess = null;

        for ($i = 0; $i < $attempts; $i++) {
            $randomUid = random_int($minId, $maxId);

            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from('tx_crowdsourcing_domain_model_process')
                ->where(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq('fe_user', 0),
                        $queryBuilder->expr()->isNull('fe_user')
                    )
                )
                ->andWhere( $queryBuilder->expr()->eq('uid', ':uid'))
                ->andWhere(
                    $queryBuilder->expr()->notIn('record_identifier', $subQueryBuilder->getSQL())
                )
                ->setMaxResults(1)
                ->setParameter('uid', $randomUid)
                ->setParameter('feUserUid', $feUser->getUid());

            $process = $queryBuilder->executeQuery()->fetchAssociative();


            if ($process !== false) {
                $randomProcess = $process;
                break;
            }
        }

        if ($randomProcess) {
            return $this->findByUid($randomProcess['uid']);
        }

        return null;
    }

}