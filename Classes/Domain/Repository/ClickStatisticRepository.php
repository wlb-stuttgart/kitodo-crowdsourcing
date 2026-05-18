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
     * Calculates the average processing time per fully completed process in seconds.
     *
     * Processing time logic:
     * 1. Only processes with a 'save' in all 3 workflow states are considered:
     *    NEW, CORRECTION, FINAL_CORRECTION.
     * 2. A valid interval starts with 'edit_metadata' and ends with 'save' or 'cache'.
     * 3. Only intervals from users who later complete the same process/status with 'save' are counted.
     * 4. For 'save' as end point, the previous 'edit_metadata' must not be interrupted by 'cache', 'abort', 'admin_abort', or 'cleanup_abort'.
     * 5. For 'cache' as end point, the previous 'edit_metadata' must not be interrupted by 'save', 'abort', 'admin_abort', or 'cleanup_abort'.
     * 6. Sum durations per status and process.
     * 7. Total process time is the sum of the 3 status durations.
     * 8. Final result is the average of those fully completed total process times.
     *
     * @return float Average processing time in seconds.
     */
    public function getAverageProcessingTime(): float
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_clickstatistic');

        $sql = "
            SELECT AVG(total_process_time)
            FROM (
                SELECT process_uid, SUM(status_duration) AS total_process_time
                FROM (
                    SELECT process_uid, process_state, SUM(duration) AS status_duration
                    FROM (
                        SELECT
                            e.process_uid,
                            e.process_state,
                            e.fe_user_uid,
                            s.crdate - e.crdate AS duration
                        FROM
                            tx_crowdsourcing_domain_model_clickstatistic e
                        JOIN
                            tx_crowdsourcing_domain_model_clickstatistic s
                        ON
                            e.process_uid = s.process_uid
                            AND e.fe_user_uid = s.fe_user_uid
                            AND e.process_state = s.process_state
                            AND s.crdate > e.crdate
                        WHERE
                            e.action_type = 'workflow_action'
                            AND e.action_identifier = 'edit_metadata'
                            AND s.action_type = 'workflow_action'
                            AND s.action_identifier IN ('save', 'cache')
                            AND e.deleted = 0
                            AND s.deleted = 0
                            AND e.process_state IN ('NEW', 'CORRECTION', 'FINAL_CORRECTION')
                            -- Only consider fully completed processes:
                            -- NEW, CORRECTION and FINAL_CORRECTION must each have a save.
                            AND e.process_uid IN (
                                SELECT completed_process.process_uid
                                FROM tx_crowdsourcing_domain_model_clickstatistic completed_process
                                WHERE completed_process.action_type = 'workflow_action'
                                  AND completed_process.action_identifier = 'save'
                                  AND completed_process.process_state IN ('NEW', 'CORRECTION', 'FINAL_CORRECTION')
                                  AND completed_process.deleted = 0
                                GROUP BY completed_process.process_uid
                                HAVING COUNT(DISTINCT completed_process.process_state) = 3
                            )
                            -- Only count intervals from users who complete this exact process/status with a later save.
                            AND EXISTS (
                                SELECT 1
                                FROM tx_crowdsourcing_domain_model_clickstatistic final_save
                                WHERE final_save.process_uid = e.process_uid
                                  AND final_save.fe_user_uid = e.fe_user_uid
                                  AND final_save.process_state = e.process_state
                                  AND final_save.action_type = 'workflow_action'
                                  AND final_save.action_identifier = 'save'
                                  AND final_save.crdate > e.crdate
                                  AND final_save.deleted = 0
                            )
                            -- Ensure s is the next action after e for this user/process/state.
                            AND NOT EXISTS (
                                SELECT 1
                                FROM tx_crowdsourcing_domain_model_clickstatistic m
                                WHERE m.process_uid = e.process_uid
                                  AND m.fe_user_uid = e.fe_user_uid
                                  AND m.process_state = e.process_state
                                  AND m.crdate > e.crdate
                                  AND m.crdate < s.crdate
                                  AND m.deleted = 0
                            )
                            -- Interruption checks.
                            AND (
                                (
                                    s.action_identifier = 'save'
                                    AND NOT EXISTS (
                                        SELECT 1
                                        FROM tx_crowdsourcing_domain_model_clickstatistic i
                                        WHERE i.process_uid = e.process_uid
                                          AND i.fe_user_uid = e.fe_user_uid
                                          AND i.process_state = e.process_state
                                          AND i.crdate > e.crdate
                                          AND i.crdate < s.crdate
                                          AND i.action_identifier IN ('cache', 'abort', 'admin_abort', 'cleanup_abort')
                                          AND i.deleted = 0
                                    )
                                )
                                OR
                                (
                                    s.action_identifier = 'cache'
                                    AND NOT EXISTS (
                                        SELECT 1
                                        FROM tx_crowdsourcing_domain_model_clickstatistic i
                                        WHERE i.process_uid = e.process_uid
                                          AND i.fe_user_uid = e.fe_user_uid
                                          AND i.process_state = e.process_state
                                          AND i.crdate > e.crdate
                                          AND i.crdate < s.crdate
                                          AND i.action_identifier IN ('save', 'abort', 'admin_abort', 'cleanup_abort')
                                          AND i.deleted = 0
                                    )
                                )
                            )
                    ) AS ValidIntervals
                    GROUP BY process_uid, process_state
                ) AS StatusDurations
                GROUP BY process_uid
                HAVING COUNT(DISTINCT process_state) = 3
            ) AS ProcessDurations
        ";

        $result = $connection->executeQuery($sql)->fetchOne();

        return (float)($result ?? 0.0);
    }

    /**
     * Counts the total number of entries in the clickstatistic table.
     *
     * @return int The total count of entries.
     */
    public function countAll(): int
    {
        $query = $this->createQuery();
        return $query->count();
    }


    /**
     * Calculates monthly page views for a given year, restricted to logged-in users.
     *
     * @param int $year
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getMonthlyPageViewsForYear(int $year): array
    {
        $connection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_crowdsourcing_domain_model_clickstatistic');

        // Year range.
        $startTimestamp = (new \DateTime("$year-01-01 00:00:00"))->getTimestamp();
        $endTimestamp = (new \DateTime(($year + 1) . "-01-01 00:00:00"))->getTimestamp();

        $sql = "
            SELECT      
                YEAR(FROM_UNIXTIME(crdate)) as year, 
                MONTH(FROM_UNIXTIME(crdate)) as month,
                SUM(CASE WHEN action_type = 'page_view' AND action_identifier = 'page_hit' THEN 1 ELSE 0 END) AS page_views
            FROM 
                tx_crowdsourcing_domain_model_clickstatistic
            WHERE 
                crdate >= :start 
                AND crdate < :end
                AND deleted = 0            
                AND fe_user_uid > 0
            GROUP BY 
                YEAR(FROM_UNIXTIME(crdate)), 
                MONTH(FROM_UNIXTIME(crdate))
            ORDER BY 
                YEAR(FROM_UNIXTIME(crdate)), 
                MONTH(FROM_UNIXTIME(crdate)) ASC
        ";

        return $connection->executeQuery($sql, [
            'start' => $startTimestamp,
            'end' => $endTimestamp
        ], [
            'start' => \Doctrine\DBAL\ParameterType::INTEGER,
            'end' => \Doctrine\DBAL\ParameterType::INTEGER
        ])->fetchAllAssociative();
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
            'process_state' => $object->getProcessState(),
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
