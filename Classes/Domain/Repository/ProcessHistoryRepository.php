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
