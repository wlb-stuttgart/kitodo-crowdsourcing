<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Validation\Validator;

use Evoweb\SfRegister\Validation\Validator\EqualCurrentUserValidator;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

/*
 * This class is a bug fix for the non-functioning UniqueValidator in sf_register, specifically for the username field.
 */
class UniqueUsernameValidator extends AbstractValidator
{
    public function isValid($value): void
    {
        $user = $GLOBALS['TSFE']->fe_user->user;
        $username = $user['username'] ?? '';

        if (trim($username) === $value) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');

        // Check for a record with the same username
        $result = $queryBuilder
            ->select('uid')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($value)))
            ->executeQuery()
            ->fetchOne();

        if ($result !== false) {
            $this->addError(
                $this->translateErrorMessage(
                    'error_notunique_global',
                    'SfRegister',
                    [$this->translateErrorMessage("username", 'SfRegister')]
                ),
                1301599619
            );

        }
    }
}
