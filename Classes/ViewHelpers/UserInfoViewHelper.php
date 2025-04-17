<?php

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Wlb\Crowdsourcing\Domain\Model\FrontendUser;
use Wlb\Crowdsourcing\Domain\Repository\FrontendUserRepository;

class UserInfoViewHelper extends AbstractViewHelper
{
    /**
     * Needed if you want to use the output as variable
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly ConfigurationManagerInterface $configurationManager
    )
    {

    }

    public function initializeArguments(): void
    {
        $this->registerArgument('as', 'string', 'Variable name to assign user to', false, 'user');
    }

    /**
     * @return FrontendUser|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function render(): ?FrontendUser
    {
        $settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        );

        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $userId = (int)$context->getPropertyFromAspect('frontend.user', 'id');

        if ($userId <= 0) {
            return null;
        }

        /** @var FrontendUser $user */
        $user = $this->frontendUserRepository->findByUid($userId);

        if (!$user instanceof FrontendUser) {
            return null;
        }

        $userInfo = [
            'uid' => $user->getUid(),
            'username' => $user->getUsername(),
            'firstname' => $user->getFirstName(),
            'middlename' => $user->getMiddleName(),
            'lastname' => $user->getLastName()
        ];

        $as = $this->arguments['as'];

        if ($this->templateVariableContainer->exists($as)) {
            $this->templateVariableContainer->remove($as);
        }
        $this->templateVariableContainer->add($as, $userInfo);

        return null;
    }
}
