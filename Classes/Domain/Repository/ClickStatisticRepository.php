<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class ClickStatisticRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'crdate' => QueryInterface::ORDER_DESCENDING
    ];

    /**
     * Counts all entries for a given action type.
     *
     * @param string $actionType The type of action used to filter the entries.
     * @return int The count of entries matching the specified action type.
     */
    public function countByActionType(string $actionType): int
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('actionType', $actionType)
        );
        return $query->count();
    }

    /**
     * Counts the number of entries that match the given process UID.
     *
     * @param int $processUid The unique identifier of the process.
     * @return int The count of entries matching the process UID.
     */
    public function countByProcessUid(int $processUid): int
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('processUid', $processUid)
        );
        return $query->count();
    }

    /**
     * Counts the number of entries that match the given campaign UID.
     *
     * @param int $campaignUid The unique identifier of the campaign.
     * @return int The count of entries matching the campaign UID.
     */
    public function countByCampaignUid(int $campaignUid): int
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('campaignUid', $campaignUid)
        );
        return $query->count();
    }

    /**
     * Retrieves entries associated with the given frontend user UID.
     *
     * @param int $feUserUid The unique identifier of the frontend user.
     * @return array An array of entries matching the frontend user UID.
     */
    public function findByFeUser(int $feUserUid): array
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('feUserUid', $feUserUid)
        );
        return $query->execute()->toArray();
    }

    /**
     * Finds entries within the specified date range.
     *
     * @param int $startTimestamp The start timestamp of the date range.
     * @param int $endTimestamp The end timestamp of the date range.
     * @return array An array of entries that fall within the specified date range.
     */
    public function findByDateRange(int $startTimestamp, int $endTimestamp): array
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->greaterThanOrEqual('crdate', $startTimestamp),
                $query->lessThanOrEqual('crdate', $endTimestamp)
            )
        );
        return $query->execute()->toArray();
    }

    /**
     * Retrieves a summary of click statistics grouped by action type.
     *
     * @return array An associative array where each entry represents an action type with its corresponding click count.
     */
    public function getClickSummaryByActionType(): array
    {
        $query = $this->createQuery();
        $query->statement('
            SELECT action_type, COUNT(*) as click_count
            FROM tx_crowdsourcing_click_statistics
            WHERE deleted = 0
            GROUP BY action_type
            ORDER BY click_count DESC
        ');
        return $query->execute(true);
    }

    /**
     * Retrieves a summary of click statistics grouped by date for the specified number of past days.
     *
     * @param int $days The number of days from today to include in the summary. Defaults to 30 days.
     * @return array An array containing date-wise click statistics with each entry including the date and click count.
     */
    public function getClickSummaryByDate(int $days = 30): array
    {
        $startDate = time() - ($days * 24 * 60 * 60);
        $query = $this->createQuery();
        $query->statement('
            SELECT DATE(FROM_UNIXTIME(crdate)) as date, COUNT(*) as click_count
            FROM tx_crowdsourcing_click_statistics
            WHERE deleted = 0 AND crdate >= ' . $startDate . '
            GROUP BY DATE(FROM_UNIXTIME(crdate))
            ORDER BY date DESC
        ');
        return $query->execute(true);
    }
}