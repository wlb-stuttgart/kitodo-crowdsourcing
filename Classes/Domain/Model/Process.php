<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Frontend\Exception;
use Wlb\Crowdsourcing\Domain\Repository\CampaignRepository;
use Wlb\Crowdsourcing\Domain\Repository\FrontendUserRepository;
use Wlb\Crowdsourcing\Domain\Repository\ProcessRepository;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

class Process extends AbstractEntity
{
    const WORKFLOW_STATE_NEW = 'NEW';
    const WORKFLOW_STATE_CORRECTION = 'CORRECTION';
    const WORKFLOW_STATE_FINAL_CORRECTION = 'FINAL_CORRECTION';
    const WORKFLOW_STATE_COMPLETED = 'COMPLETED';

    const PROCESS_IMAGE_BASE_DIRECTORY = 'jpg';
    const PROCESS_IMAGE_DEFAULT_DIRECTORY = 'max';
    const PROCESS_IMAGE_THUMB_DIRECTORY = 'thumbs';

    const WORKFLOW_STATES = [
        self::WORKFLOW_STATE_NEW,
        self::WORKFLOW_STATE_CORRECTION,
        self::WORKFLOW_STATE_FINAL_CORRECTION,
        self::WORKFLOW_STATE_COMPLETED,
    ];

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $recordIdentifier = '';

    /**
     * @var string List of images
     */
    protected $images;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string Metadata in JSON-Format
     */
    protected $metadata = '';

    /**
     * @var \Wlb\Crowdsourcing\Domain\Model\Campaign
     */
    protected $campaign;

    /**
     * @var \Wlb\Crowdsourcing\Domain\Model\FrontendUser|null
     */
    protected $feUser;

    /**
     * @var int|null
     */
    protected $lastAccessed = null;

    /**
     * @return int|null
     */
    public function getLastAccessed(): ?int
    {
        return $this->lastAccessed;
    }

    /**
     * @param int $lastAccessed
     * @return void
     */
    public function setLastAccessed(int $lastAccessed): void
    {
        $this->lastAccessed = $lastAccessed;
    }


    /**
     * Get the campaign associated with this process.
     *
     * @return \Wlb\Crowdsourcing\Domain\Model\Campaign
     */
    public function getCampaign(): ?\Wlb\Crowdsourcing\Domain\Model\Campaign
    {
        return $this->campaign;
    }

    /**
     * Set the campaign for this process.
     *
     * @param \Wlb\Crowdsourcing\Domain\Model\Campaign $campaign
     */
    public function setCampaign(\Wlb\Crowdsourcing\Domain\Model\Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getRecordIdentifier(): string
    {
        return $this->recordIdentifier;
    }

    public function setRecordIdentifier(string $recordIdentifier): void
    {
        $this->recordIdentifier = $recordIdentifier;
    }

    public function getImages(): array
    {
        return unserialize($this->images);
    }

    public function setImages(array $images): void
    {
        $this->images = serialize($images);
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function setNextState(): void
    {
        if ($this->state === self::WORKFLOW_STATE_NEW) {
            $this->state = self::WORKFLOW_STATE_CORRECTION;
        } else if ($this->state === self::WORKFLOW_STATE_CORRECTION) {
            $this->state = self::WORKFLOW_STATE_FINAL_CORRECTION;
        } else if ($this->state === self::WORKFLOW_STATE_FINAL_CORRECTION) {
            $this->state = self::WORKFLOW_STATE_COMPLETED;
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return \Wlb\Crowdsourcing\Domain\Model\FrontendUser|null
     */
    public function getFeUser(): \Wlb\Crowdsourcing\Domain\Model\FrontendUser|null
    {
        if ($this->feUser instanceof \Wlb\Crowdsourcing\Domain\Model\FrontendUser) {
            return $this->feUser;
        }
        return null;
    }

    /**
     * @param \Wlb\Crowdsourcing\Domain\Model\FrontendUser $feUser
     */
    public function setFeUser(\Wlb\Crowdsourcing\Domain\Model\FrontendUser $feUser): void
    {
        $this->feUser = $feUser;
        $this->lastAccessed = time();
    }

    public function hasFeUser(): bool
    {
        return (bool)$this->feUser;
    }

    public function resetFeUser(): void
    {
        $this->feUser = 0;
    }

    public function getMetadata(): string
    {
        return $this->metadata;
    }

    public function setMetadata(string $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Sets the title based on metadata extracted from an XML structure.
     *
     * This method parses the metadata field, interprets the XML to extract the
     * title or signature, and sets the title accordingly. If the title is not
     * found, the signature is used as the title.
     *
     * @return void
     */
    public function setTitleFromMetadata()
    {
        $xml = simplexml_load_string($this->metadata);
        $xml->registerXPathNamespace('kitodo', 'http://meta.kitodo.org/v1/');
        // Get title and signature
        $signature = (string) $xml->xpath('*[@name="Signatur"]')[0];
        $title = (string) $xml->xpath('*[@name="4000"]/*[@name="4000_1"]')[0];
        
        if (empty($title)) {
            $this->setTitle($signature);;
        } else {
            $this->setTitle($title);
        }

    }

    /**
     * @return string[]
     */
    public function getMetadataForDisplay()
    {
        $xml = simplexml_load_string($this->metadata);
        $xml->registerXPathNamespace('kitodo', 'http://meta.kitodo.org/v1/');
        $values = $xml->xpath("//kitodo:metadata[@name='Signatur']");
        return ['signatur' => (string)$values[0]];
    }

    public function getThumbsImageInfos()
    {
        $thumbImageType = ExtensionConfigurationService::getInstance()->getConfigurationValue('processImageThumbDirectory') ?? self::PROCESS_IMAGE_THUMB_DIRECTORY;
        return $this->getImageInfos($thumbImageType);
    }

    public function getImageInfos(string $fileType = null)
    {
        if ($this->state === self::WORKFLOW_STATE_COMPLETED) {
            $processImagePath = ExtensionConfigurationService::getInstance()->getConfigurationValue('archiveDirectoryPath');
        } else {
            $processImagePath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        }

        $imageDirectory   = ExtensionConfigurationService::getInstance()->getConfigurationValue('processImageBaseDirectory') ?? self::PROCESS_IMAGE_BASE_DIRECTORY;
        $defaultImageType = ExtensionConfigurationService::getInstance()->getConfigurationValue('processImageDefaultDirectory') ?? self::PROCESS_IMAGE_DEFAULT_DIRECTORY;
        $imageType = empty($fileType) ? $defaultImageType : $fileType;

        if (substr($processImagePath, -1) === '/') {
            $processImagePath = $processImagePath . '/';
        }

        $processImagesInfo = [];
        $i = 0;
        foreach ($this->getImages() as $image) {
            $path = $processImagePath .'/'. $this->getRecordIdentifier() . '/' . $imageDirectory . '/' . $imageType . '/' . $image;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $processImagesInfo[$i]['image'] = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $imageSize = getimagesize($path);
            $processImagesInfo[$i]['width'] = $imageSize[0];
            $processImagesInfo[$i]['height'] = $imageSize[1];
            $i++;
        }

        return $processImagesInfo;
    }

    public function updateMetadata(Array $metadataArray)
    {
        $sxe = simplexml_load_string($this->metadata);

        // TODO: Consider whether you could clean the array first, i.e. remove the empty values

        foreach ($metadataArray as $metadataKey => $metadata) {
            if (is_array($metadata[0])) {
                $availableGroups = $sxe->xpath('*[@name="' . $metadataKey . '"]');

                foreach ($metadata as $metadataGroupCount => $metadataGroupFields) {

                    if (array_key_exists($metadataGroupCount, $availableGroups)) {
                        // metadata group exist // override values or create metadata node if it doesnt exist
                        foreach ($metadataGroupFields as $subMetadataKey => $subMetadata) {
                            $availableElements = $availableGroups[$metadataGroupCount]->xpath('*[@name="' . $subMetadataKey . '"]');

                            if (is_array($availableElements)) {
                                // check each value
                                $j = 0;
                                foreach ($subMetadata as $subMetadataField) {
//                                    if (!empty($subMetadata[$j])) {
                                        if (isset($availableElements[$j])) {
                                            $availableElements[$j][0] = $subMetadata[$j];
                                        } else {
                                            // missing field for sub metadata
                                            $availableGroups[$metadataGroupCount]->addChild('kitodo:metadata', $subMetadata[$j])->addAttribute('name', $subMetadataKey);
                                        }
//                                    }
                                    $j++;
                                }

                            } else {
                                // No avaiable element ?? create it inside group
                                $j = 0;
                                foreach ($subMetadata as $subMetadataField) {
                                    if (!empty($subMetadata[$j])) {
                                        $availableGroups[$metadataGroupCount]->addChild('kitodo:metadata', $subMetadata[$j])->addAttribute('name', $subMetadataKey);
                                    }
                                    $j++;
                                }

                            }
                        }
                    } else {
                        // metadata group doesnt exist / create metadata group
                        $createNodeArray = [];
                        $createGroup = false;
                        $i = 0;
                        foreach ($metadataGroupFields as $subMetadataKey => $subMetadata) {
                            if (!empty($subMetadata)) {
                                $j = 0;
                                foreach ($subMetadata as $subMetadataField) {
                                    if (!empty($subMetadata[$j])) {
                                        $createGroup = true;
                                        $createNodeArray[$i.$j]['name'] = $subMetadataKey;
                                        $createNodeArray[$i.$j]['value'] = $subMetadata[$j];
                                    }
                                    $j++;
                                }
                            }
                            $i++;
                        }
                        if ($createGroup) {
                            $group = $sxe->addChild('kitodo:metadataGroup', '');
                            $group->addAttribute('name', $metadataKey);

                            foreach ($createNodeArray as $node) {
                                $group->addChild('kitodo:metadata', $node['value'])->addAttribute('name', $node['name']);
                            }
                        }

                    }
                }

            } else {
                $availableElements = $sxe->xpath('*[@name="' . $metadataKey . '"]');
                $i = 0;
                foreach ($metadata as $subMetadata) {
                    if (!empty($subMetadata)) {
                        if (array_key_exists($i, $availableElements)) {
                            $availableElements[$i][0] = $subMetadata;
                        } else {
                            $sxe->addChild('kitodo:metadata', $subMetadata)->addAttribute('name', $metadataKey);
                        }
                    } else {
                        if (array_key_exists($i, $availableElements)) {
                            $availableElements[$i][0] = '';
                        }
                    }
                    $i++;
                }
            }
        }

        $this->setMetadata($sxe->saveXML());

        $this->setTitleFromMetadata();
    }

    public function getSignature()
    {
        $xml = simplexml_load_string($this->metadata);
        $xml->registerXPathNamespace('kitodo', 'http://meta.kitodo.org/v1/');
        $signature = $xml->xpath('*[@name="Signatur"]');
        if (isset($signature[0])) {
            return (string) $signature[0];
        }
        return '';
    }

    public function toArray()
    {
        return [
            'uid' => $this->getUid(),
            'title' => $this->getTitle(),
            'identifier' => $this->getRecordIdentifier(),
            'images' => $this->getImages(),
            'state' => $this->getState(),
            'type' => $this->getType(),
            'metadata' => $this->getMetadata(),
            'campaign' => $this->getCampaign()?->getUid(),
            'feUser' => $this->getFeUser()?->getUid(),
            'lastAccessed' => $this->getLastAccessed(),
        ];
    }
}
