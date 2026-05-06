<?php

namespace Wlb\Crowdsourcing\Domain\Repository;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use Wlb\Crowdsourcing\Domain\Model\ClickStatistic;

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
            FROM tx_crowdsourcing_domain_model_clickstatistic
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
            FROM tx_crowdsourcing_domain_model_clickstatistic
            WHERE deleted = 0 AND crdate >= ' . $startDate . '
            GROUP BY DATE(FROM_UNIXTIME(crdate))
            ORDER BY date DESC
        ');
        return $query->execute(true);
    }

    /**
     * Calculates the average dwell time per user in seconds using a direct SQL query.
     *
     * Dwell time logic:
     * 1. Identify session boundaries (gap > inactivityLimit).
     * 2. Calculate duration for each session.
     * 3. Average session durations per user.
     * 4. Average of those user averages.
     *
     * @param int $inactivityLimit Inactivity limit in seconds (default: 15 minutes).
     * @return float Average dwell time in seconds.
     */
    public function getAverageDwellTime(int $inactivityLimit = 900): float
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_clickstatistic');

        // SQL explanation:
        // ClicksWithLag: Get the previous click's timestamp for each user.
        // SessionFlags: Flag the start of a new session if gap > limit or it's the first click.
        // SessionGroups: Use cumulative sum of flags to create unique session IDs per user.
        // SessionDurations: Calculate duration (max - min crdate) for each session.
        // UserAverages: Average those durations per user.
        // Final: Average the user averages.

        $sql = "
            SELECT AVG(user_avg_duration) as overall_avg
            FROM (
                SELECT fe_user_uid, AVG(session_duration) as user_avg_duration
                FROM (
                    SELECT fe_user_uid, session_id, (MAX(crdate) - MIN(crdate)) as session_duration
                    FROM (
                        SELECT fe_user_uid, crdate,
                               SUM(is_new_session) OVER (PARTITION BY fe_user_uid ORDER BY crdate) as session_id
                        FROM (
                            SELECT fe_user_uid, crdate,
                                   CASE
                                       WHEN crdate - LAG(crdate) OVER (PARTITION BY fe_user_uid ORDER BY crdate) > :limit
                                       OR LAG(crdate) OVER (PARTITION BY fe_user_uid ORDER BY crdate) IS NULL
                                       THEN 1
                                       ELSE 0
                                   END as is_new_session
                            FROM tx_crowdsourcing_domain_model_clickstatistic
                            WHERE fe_user_uid > 0 AND deleted = 0
                        ) AS ClicksWithLag
                    ) AS SessionGroups
                    GROUP BY fe_user_uid, session_id
                    HAVING MAX(crdate) - MIN(crdate) > 0
                ) AS SessionDurations
                GROUP BY fe_user_uid
            ) AS UserAverages
        ";

        $result = $connection->executeQuery($sql, [
            'limit' => $inactivityLimit
        ])->fetchOne();

        return (float)($result ?? 0.0);
    }

    /**
     * This function will insert the clickStatistic data into the database using the connection pool and the query builder.
     *
     * Since the add function is used by the LogPageHitMiddleware, we need to insert the data directly into the database
     * to avoid the RuntimeException #1700841298:
     * "Setup array has not been initialized. This happens in cached Frontend scope where full TypoScript is not needed by the system."
     *
     * @param $object
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function add($object)
    {
        if (!$object instanceof ClickStatistic) {
            throw new \InvalidArgumentException();
        }

        $data = [
            'user_agent' => $object->getUserAgent(),
            'fe_user_uid' => $object->getFeUserUid(),
            'action_type' => $object->getActionType(),
            'action_identifier' => $object->getActionIdentifier(),
            'uri' => $object->getUri(),
            'referrer' => $object->getReferrer(),
            'process_uid' => $object->getProcessUid(),
            'campaign_uid' => $object->getCampaignUid(),
            'session_id' => $object->getSessionId(),
            'additional_data' => $object->getAdditionalData(),
            'tstamp' => time(),
            'crdate' => time(),
        ];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_crowdsourcing_domain_model_clickstatistic');

        $queryBuilder
            ->insert('tx_crowdsourcing_domain_model_clickstatistic')
            ->values($data)
            ->executeStatement();
    }
}
