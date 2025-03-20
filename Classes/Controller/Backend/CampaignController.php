<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class CampaignController extends ActionController
{
    /**
     * @var ProcessRepository
     */
    protected $processRepository;

    public function injectProcessRepository(ProcessRepository $processRepository)
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
