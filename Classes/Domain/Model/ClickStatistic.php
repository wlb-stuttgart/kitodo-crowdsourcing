<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ClickStatistic extends AbstractEntity
{
    /**
     * @var string
     */
    protected string $userAgent = '';

    /**
     * @var int
     */
    protected int $feUserUid = 0;

    /**
     * @var string
     */
    protected string $actionType = '';

    /**
     * @var string
     */
    protected string $actionIdentifier = '';

    /**
     * @var string
     */
    protected string $uri = '';

    /**
     * @var string
     */
    protected string $referrer = '';

    /**
     * @var int
     */
    protected int $processUid = 0;

    /**
     * @var int
     */
    protected int $campaignUid = 0;

    /**
     * @var string
     */
    protected string $sessionId = '';

    /**
     * @var string
     */
    protected string $additionalData = '';

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getFeUserUid(): int
    {
        return $this->feUserUid;
    }

    public function setFeUserUid(int $feUserUid): void
    {
        $this->feUserUid = $feUserUid;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): void
    {
        $this->actionType = $actionType;
    }

    public function getActionIdentifier(): string
    {
        return $this->actionIdentifier;
    }

    public function setActionIdentifier(string $actionIdentifier): void
    {
        $this->actionIdentifier = $actionIdentifier;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getReferrer(): string
    {
        return $this->referrer;
    }

    public function setReferrer(string $referrer): void
    {
        $this->referrer = $referrer;
    }

    public function getProcessUid(): int
    {
        return $this->processUid;
    }

    public function setProcessUid(int $processUid): void
    {
        $this->processUid = $processUid;
    }

    public function getCampaignUid(): int
    {
        return $this->campaignUid;
    }

    public function setCampaignUid(int $campaignUid): void
    {
        $this->campaignUid = $campaignUid;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getAdditionalData(): string
    {
        return $this->additionalData;
    }

    public function setAdditionalData(string $additionalData): void
    {
        $this->additionalData = $additionalData;
    }
}