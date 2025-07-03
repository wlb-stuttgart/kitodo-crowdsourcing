<?php

namespace Wlb\Crowdsourcing\Controller;

use Evoweb\SfRegister\Controller\FeuserCreateController as SfRegisterFeuserCreateController;
use Evoweb\SfRegister\Domain\Model\FrontendUser;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

class FeuserCreateController extends SfRegisterFeuserCreateController
{

    public function saveAction(FrontendUser $user): ResponseInterface
    {
        return new HtmlResponse($this->view->render());
        //return parent::saveAction($user);
    }
}
