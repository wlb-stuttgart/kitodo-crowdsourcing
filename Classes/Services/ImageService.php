<?php

namespace Wlb\Crowdsourcing\Services;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class ImageService
{
    /**
     * @param string $filePath
     * @param int|null $width
     * @return array
     */
    public function getImageInfo(string $filePath, int $width = null): array
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $file = $resourceFactory->retrieveFileOrFolderObject($filePath);

        $originalWidth = $file->getProperty('width');
        $originalHeight = $file->getProperty('height');

        if (empty($width)) {
            return [
                'path' => $filePath,
                'width' => $originalWidth,
                'height' => $originalHeight,
                'base64' => 'data:' . $file->getMimeType() . ';base64,' . base64_encode($file->getContents())
            ];
        }

        $newWidth  = $width;
        $newHeight = ($originalHeight / $originalWidth) * $width;

        $processedFile = $file->process(
            \TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            ['width' => $newWidth, 'height' => $newHeight]
        );

        $localPath = $processedFile->getForLocalProcessing(true);
        $fileContents = file_get_contents($localPath);

        return [
            'path' => $file->getPublicUrl(),
            'width' => $newWidth,
            'height' => $newHeight,
            'base64' => 'data:' . $file->getMimeType() . ';base64,' . base64_encode($fileContents)
        ];
    }
}
