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
}
