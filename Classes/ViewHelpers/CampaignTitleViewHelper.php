<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;

class CampaignTitleViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = true;

    public function __construct(
        private readonly CampaignRepository $campaignRepository
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'int', 'UID of the campaign', true);
        $this->registerArgument('fallback', 'string', 'Fallback if nothing is found', false, '');
    }

    private static array $cache = [];

    public function render(): string
    {
        $uid = (int)$this->arguments['uid'];
        $fallback = (string)$this->arguments['fallback'];

        if ($uid <= 0) {
            return $fallback;
        }

        if (isset(self::$cache[$uid])) {
            return self::$cache[$uid];
        }

        $campaign = $this->campaignRepository->findByUid($uid);
        $title = $campaign ? $campaign->getTitle() : ($fallback !== '' ? $fallback : '');

        return self::$cache[$uid] = $title;
    }
}
