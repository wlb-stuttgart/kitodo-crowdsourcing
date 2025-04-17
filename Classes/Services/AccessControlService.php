<?php

namespace Wlb\Crowdsourcing\Services;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Repository\FrontendUserRepository;

class AccessControlService
{
    /**
     * Settings from TypoScript settings
     *
     * @var array
     */
    protected $settings;

    public function __construct(
        private readonly Context $context,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly ConfigurationManagerInterface $configurationManager
    )
    {
        $this->settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        );
    }

    /**
     * @return FrontendUser|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    private function getCurrentUser()
    {
        $userId = (int)$this->context->getPropertyFromAspect('frontend.user', 'id');

        if ($userId <= 0) {
            return null;
        }

        /** @var FrontendUser $user */
        $user = $this->frontendUserRepository->findByUid($userId);

        if ($user instanceof FrontendUser) {
            return $user;
        }

        return null;
    }


    /**
     * @param FrontendUser $user
     * @param array|int $allowedGroups
     * @return bool
     * @throws \Exception
     */
    public function hasAccessByUsergroup(FrontendUser $user, array|int $allowedGroups): bool
    {
        if (!is_array($allowedGroups)) {
            $allowedGroups = [$allowedGroups];
        }

        foreach ($allowedGroups as $allowedGroup) {
            if ((int)$allowedGroup != $allowedGroup || $allowedGroup <= 0 ) {
                throw new \Exception("Invalid user group configuration.");
            }
        }

        foreach ($user->getUsergroup() as $userGroup) {
            return in_array($userGroup->getUid(), $allowedGroups);
        }

        return false;
    }

    public function isCrowdsourcingUser()
    {
        $currentUser =$this->getCurrentUser();

        $crowdsourcingUserGroup = $this->settings['crowdsourcingUserGroup'] ?? null;

        if ($crowdsourcingUserGroup) {
            $crowdsourcingUserGroup = array_map('trim', explode(',', $crowdsourcingUserGroup));
        }

        return $currentUser
            && $this->hasAccessByUsergroup($currentUser, $crowdsourcingUserGroup)
            && $crowdsourcingUserGroup;
    }
}
