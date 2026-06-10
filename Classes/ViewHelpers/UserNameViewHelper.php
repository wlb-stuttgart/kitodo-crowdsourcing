<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Repository\FrontendUserRepository;

/**
 * ViewHelper to get the display name of a frontend user.
 * Respects the consent property (stats or edits).
 */
class UserNameViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly FrontendUserRepository $frontendUserRepository
    ) {
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('feUserUid', 'int', 'The UID of the frontend user', true);
        $this->registerArgument('type', 'string', 'The consent type: "stats" or "edits"', true);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $feUserUid = (int)$this->arguments['feUserUid'];
        $type = $this->arguments['type'];

        if ($feUserUid <= 0) {
            return LocalizationUtility::translate('process.editor.deleted', 'Crowdsourcing') ?? 'Anonymous';
        }

        /** @var FrontendUser $feUser */
        $feUser = $this->frontendUserRepository->findByUid($feUserUid);

        if ($feUser instanceof FrontendUser) {
            $consent = match ($type) {
                'stats' => $feUser->isConsentPublishUsernameStats(),
                'edits' => $feUser->isConsentPublishUsernameEdits(),
                default => false,
            };

            if ($consent) {
                return $feUser->getUsername();
            }
            return LocalizationUtility::translate('process.editor.anonymous', 'Crowdsourcing') ?? 'Anonymous';
        }

        return LocalizationUtility::translate('process.editor.deleted', 'Crowdsourcing') ?? 'Anonymous';
    }
}
