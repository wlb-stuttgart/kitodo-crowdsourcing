<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Domain\Repository;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FrontendUserRepository extends \Evoweb\SfRegister\Domain\Repository\FrontendUserRepository
{
    /**
     * Get the cumulative number of frontend users per month for a specific year.
     *
     * @param int $year
     * @param int $activeUserGroupUid
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function numberActiveUsersPerMonthForYear(int $year, $activeUserGroupUid = 0): array
    {
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n'); // 1 bis 12

        $startTimestamp = mktime(0, 0, 0, 1, 1, $year);
        $endTimestamp   = mktime(23, 59, 59, 12, 31, $year);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        // Baseline value (all users before year)
        $sqlBase = "
            SELECT COUNT(uid) 
            FROM fe_users 
            WHERE FIND_IN_SET(:groupUid, usergroup) > 0
                AND crdate < :startTimestamp
                AND deleted = 0 AND disable = 0
        ";

        $existingUsersBeforeYear = (int)$connection->executeQuery($sqlBase, [
            'groupUid' => $activeUserGroupUid,
            'startTimestamp' => $startTimestamp
        ], [
            'groupUid' => ParameterType::INTEGER,
            'startTimestamp' => ParameterType::INTEGER
        ])->fetchOne();


        // Monthly new registrations in year
        $sqlMonths = "
        SELECT 
            MONTH(FROM_UNIXTIME(crdate)) AS month, 
            COUNT(uid) AS new_users
        FROM fe_users
        WHERE FIND_IN_SET(:groupUid, usergroup) > 0
            AND crdate >= :startTimestamp
            AND crdate <= :endTimestamp
            AND deleted = 0 AND disable = 0
        GROUP BY MONTH(FROM_UNIXTIME(crdate))
        ORDER BY month ASC
        ";

        $dbRows = $connection->executeQuery($sqlMonths, [
            'groupUid' => $activeUserGroupUid,
            'startTimestamp' => $startTimestamp,
            'endTimestamp' => $endTimestamp
        ], [
            'groupUid' => ParameterType::INTEGER,
            'startTimestamp' => ParameterType::INTEGER,
            'endTimestamp' => ParameterType::INTEGER
        ])->fetchAllAssociative();

        // Accumulate & fill all 12 months
        $monthlyData = [];
        foreach ($dbRows as $row) {
            $monthlyData[(int)$row['month']] = (int)$row['new_users'];
        }

        $finalResult = [];
        $currentRunningTotal = $existingUsersBeforeYear;

        // Create exactly 12 months. Months without registrations are calculated as 0,
        // but retain the cumulative value of the previous month.
        for ($m = 1; $m <= 12; $m++) {

            // Check if the month is in the future (only relevant if the queried year is the current year)
            if ($year === $currentYear && $m > $currentMonth) {
                $finalResult[$m] = [
                    'month' => $m,
                    'new_users' => null,
                    'total_users' => null
                ];
                continue;
            }

            $newUsersInMonth = $monthlyData[$m] ?? 0;
            $currentRunningTotal += $newUsersInMonth;

            $finalResult[$m] = [
                'month' => $m,
                'new_users' => $newUsersInMonth,
                'total_users' => $currentRunningTotal
            ];
        }

        return $finalResult;
    }


    /**
     * Determines the year of the very first user registration.
     * Falls back to the current year if no users exist.
     *
     * @param $activeUserGroupUid
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function getFirstRegistrationYear($activeUserGroupUid = 0): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('fe_users');

        // Fetches the oldest timestamp (crdate) from the fe_users table
        $row = $queryBuilder
            ->select('crdate')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->gt('crdate', 0),
                // Checks if the group ID exists in the comma-separated list
                $queryBuilder->expr()->and(
                    'FIND_IN_SET(' . $queryBuilder->createNamedParameter($activeUserGroupUid, ParameterType::INTEGER) . ', usergroup) > 0'
                )
            )
            ->orderBy('crdate', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        // If users exist, convert the timestamp into the year
        if (!empty($row['crdate'])) {
            return (int)date('Y', (int)$row['crdate']);
        }

        return (int)date('Y');
    }

    /**
     * Determines the number of active FE users (the active user group is set) without a 'processhistory' entry.
     * Such users are considered metadata-only viewers.
     *
     * @param int $groupId Die ID der Frontend-Usergroup
     * @return int Anzahl der gefundenen User
     */
    public function countUsersWithoutProcessHistory(int $groupId): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('fe_users');

        $count = $queryBuilder
            ->count('fe_users.uid')
            ->from('fe_users')
            ->leftJoin(
                'fe_users',
                'tx_crowdsourcing_domain_model_processhistory',
                'ph',
                'fe_users.uid = ph.fe_user'
            )
            ->where(
                $queryBuilder->expr()->isNull('ph.uid'),
                $queryBuilder->expr()->inSet('fe_users.usergroup', (string)$groupId)
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$count;
    }
}
