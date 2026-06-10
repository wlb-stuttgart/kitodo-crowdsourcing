<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Controller;

use Evoweb\SfRegister\Controller\FeuserCreateController as SfRegisterFeuserCreateController;
use Evoweb\SfRegister\Domain\Model\FrontendUser;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

class FeuserCreateController extends SfRegisterFeuserCreateController
{

    /**
     * @param FrontendUser $user
     * @return ResponseInterface
     */
    public function saveAction(FrontendUser $user): ResponseInterface
    {
        $regristrationDeactivated = $this->settings['regristrationDeactivated'] ?? false;

        if ($regristrationDeactivated) {
            return new HtmlResponse($this->view->render());
        }

        return parent::saveAction($user);
    }
}
