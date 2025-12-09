<?php

namespace Wlb\Crowdsourcing\Services;

use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Model\Process;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\FrontendUserRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessHistoryRepository;

class ProcessHistoryService
{
    public function __construct(
        private CampaignRepository     $campaignRepository,
        private FrontendUserRepository $frontendUserRepository,
        private ProcessHistoryRepository $processHistoryRepository
    )
    {
    }

    public function restoreFromArray(Process $process, array $data): void
    {
        if (isset($data['title'])) {
            $process->setTitle($data['title']);
        }

        if (isset($data['identifier'])) {
            $process->setRecordIdentifier($data['identifier']);
        }

        if (isset($data['images'])) {
            $process->setImages($data['images']);
        }

        if (isset($data['state'])) {
            $process->setState($data['state']);
        }

        if (isset($data['type'])) {
            $process->setType($data['type']);
        }

        if (isset($data['metadata'])) {
            $process->setMetadata($data['metadata']);
        }

        if (isset($data['campaign']) && is_numeric($data['campaign'])) {
            $campaign = $this->campaignRepository->findByUid((int)$data['campaign']);
            if ($campaign) {
                $process->setCampaign($campaign);
            }
        }

        if (isset($data['feUser']) && is_numeric($data['feUser'])) {
            $feUser = $this->frontendUserRepository->findByUid((int)$data['feUser']);
            if ($feUser) {
                $process->setFeUser($feUser);
            }
        }

        if (isset($data['lastAccessed'])) {
            $process->setLastAccessed($data['lastAccessed']);
        }
    }

    public function hasUserAlreadyEdited(Process $process, FrontendUser $feUser)
    {
        $processHistories = $this->processHistoryRepository->findByRecordIdentifier($process->getRecordIdentifier());
        $hasEdited = false;

        /* @var \Wlb\Crowdsourcing\Domain\Model\ProcessHistory $processHistory */
        foreach ($processHistories as $processHistory) {
            if ($processHistory->getFeUser() === $feUser) {
                $hasEdited = true;
                break;
            }
        }

        return $hasEdited;

    }

    /**
     * @param Process $process
     * @return mixed[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function findUserIds(Process $process)
    {
        return $this->processHistoryRepository->findFeUserIdsByRecordIdentifier($process->getRecordIdentifier());
    }
}
