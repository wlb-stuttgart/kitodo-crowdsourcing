<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use Wlb\Crowdsourcing\Domain\Repository\CampaignTaskRepository;

class CampaignController extends ActionController
{
    /**
     * @var CampaignTaskRepository
     */
    protected $processRepository;

    public function injectProcessRepository(CampaignTaskRepository $processRepository)
    {
        $this->processRepository = $processRepository;
    }

    public function __construct()
    {
    }

    public function indexAction()
    {
    }
}
