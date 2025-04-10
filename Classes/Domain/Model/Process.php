<?php

namespace Wlb\Crowdsourcing\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

class Process extends AbstractEntity
{
    const WORKFLOW_STATE_NEW = 'NEW';
    const WORKFLOW_STATE_CORRECTION  = 'CORRECTION';
    const WORKFLOW_STATE_FINAL_CORRECTION= 'FINAL_CORRECTION';

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $identifier;

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
    protected $metadata;

    /**
     * @var \Wlb\Crowdsourcing\Domain\Model\Campaign
     */
    protected $campaign;

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

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getMetadata(): string
    {
        return $this->metadata;
    }

    public function setMetadata(string $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadataForDisplay()
    {
        $xml = simplexml_load_string($this->metadata);
        $xml->registerXPathNamespace('kitodo', 'http://meta.kitodo.org/v1/');
        $values = $xml->xpath("//kitodo:metadata[@name='signature']");
        return ['signature' => (string)$values[0]];
    }

    public function getImageInfos()
    {
        $importedPath = ExtensionConfigurationService::getInstance()->getConfigurationValue('importedDirectoryPath');
        if (substr($importedPath, -1) === '/') {
            $importedPath = $importedPath . '/';
        }
        $processImagesInfo = [];
        $i = 0;
        foreach ($this->getImages() as $image) {
            $path = $importedPath .'/'. $this->getIdentifier() . '/images/' . $image;
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
}
