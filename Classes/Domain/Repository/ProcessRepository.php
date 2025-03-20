<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

class ProcessRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @param $identifiers
     * @return object[]|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findByIdentifierList($identifiers)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->in('identifier', $identifiers)
        );
        return $query->execute();
    }
}
