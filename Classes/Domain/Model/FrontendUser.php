<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Domain\Model;

class FrontendUser extends \Evoweb\SfRegister\Domain\Model\FrontendUser
{
    /**
     * @var bool
     */
    protected bool $consentPublishUsernameEdits;

    /**
     * @var bool
     */
    protected bool $consentPublishUsernameStats;

    /**
     * @return bool
     */
    public function isConsentPublishUsernameEdits(): bool
    {
        return $this->consentPublishUsernameEdits;
    }

    /**
     * @param bool $consentPublishUsernameEdits
     */
    public function setConsentPublishUsernameEdits(bool $consentPublishUsernameEdits): void
    {
        $this->consentPublishUsernameEdits = $consentPublishUsernameEdits;
    }

    /**
     * @return bool
     */
    public function isConsentPublishUsernameStats(): bool
    {
        return $this->consentPublishUsernameStats;
    }

    /**
     * @param bool $consentPublishUsernameStats
     * @return void
     */
    public function setConsentPublishUsernameStats(bool $consentPublishUsernameStats): void
    {
        $this->consentPublishUsernameStats = $consentPublishUsernameStats;
    }



}
