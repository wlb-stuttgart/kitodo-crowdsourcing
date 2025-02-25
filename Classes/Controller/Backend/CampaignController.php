<?php

namespace Wlb\Crowdsourcing\Controller\Backend;

use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use Wlb\Crowdsourcing\Common\Indexer;
use Wlb\Crowdsourcing\Common\ProcessImporter;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;

class CampaignController extends ActionController
{
    /**
     * @var ProcessImporter
     */
    protected $processImporter;

    /**
     * @var ProcessRepository
     */
    protected $processRepository;

    public function injectProcessRepository(ProcessRepository $processRepository)
    {
        $this->processRepository = $processRepository;
    }

    public function __construct(ProcessImporter $processImporter)
    {
        $this->processImporter = $processImporter;
    }

    public function indexAction()
    {
    }
}
