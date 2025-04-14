<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

class ProcessRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @param $identifiers
     * @return array|object[]|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findByIdentifierList($identifiers)
    {
        $query = $this->createQuery();

        if (empty($identifiers)) {
            return [];
        } else {
            $query->matching(
                $query->in('identifier', $identifiers)
            );
            return $query->execute();
        }
    }
}
