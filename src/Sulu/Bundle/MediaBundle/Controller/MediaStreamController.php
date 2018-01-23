<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MediaStreamController extends Controller
{
    /**
     * @var FormatManagerInterface
     */
    protected $cacheManager = null;

    /**
     * @var MediaManagerInterface
     */
    protected $mediaManager = null;

    /**
     * @var StorageInterface
     */
    protected $storage = null;

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getImageAction(Request $request)
    {
        try {
            if (ob_get_length()) {
                ob_end_clean();
            }

            $url = $request->getPathInfo();

            list($id, $format) = $this->getCacheManager()->getMediaProperties($url);

            return $this->getCacheManager()->returnImage($id, $format);
        } catch (ImageProxyException $e) {
            throw $this->createNotFoundException('Image create error. Code: ' . $e->getCode());
        }
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return BinaryFileResponse
     */
    public function downloadAction(Request $request, $id)
    {
        try {
            if (ob_get_length()) {
                ob_end_clean();
            }

            $version = $request->get('v', null);
            $noCount = $request->get('no-count', false);

            $fileVersion = $this->getFileVersion($id, $version);

            if (!$fileVersion) {
                return new Response(null, 404);
            }

            $dispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
            if ($request->get('inline', false)) {
                $dispositionType = ResponseHeaderBag::DISPOSITION_INLINE;
            }

            if (!$noCount) {
                $this->getMediaManager()->increaseDownloadCounter($fileVersion->getId());
            }

            $response = $this->getFileResponse($fileVersion, $request->getLocale(), $dispositionType);

            return $response;
        } catch (MediaException $e) {
            throw $this->createNotFoundException('File not found: ' . $e->getCode() . ' ' . $e->getMessage());
        }
    }

    /**
     * @param FileVersion $fileVersion
     * @param string $locale
     * @param string $dispositionType
     *
     * @return BinaryFileResponse
     */
    protected function getFileResponse(
        $fileVersion,
        $locale,
        $dispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT
    ) {
        $cleaner = $this->get('sulu.content.path_cleaner');

        $fileName = $fileVersion->getName();
        $fileSize = $fileVersion->getSize();
        $storageOptions = $fileVersion->getStorageOptions();
        $mimeType = $fileVersion->getMimeType();
        $version = $fileVersion->getVersion();

        $path = $this->getStorage()->load($fileName, $version, $storageOptions);

        $response = new BinaryFileResponse($path);

        // Prepare headers
        $disposition = $response->headers->makeDisposition(
            $dispositionType,
            $fileName,
            $this->cleanUpFileName($fileName, $locale, $fileVersion->getExtension())
        );

        // Set headers
        $response->headers->set('Content-Type', !empty($mimeType) ? $mimeType : 'application/octet-stream');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-length', $fileSize);

        return $response;
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return null|FileVersion
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException
     */
    protected function getFileVersion($id, $version)
    {
        /*
         * @var MediaInterface
         */
        $mediaEntity = $this->container->get('sulu.repository.media')->findMediaById($id);

        if (!$mediaEntity) {
            return;
        }

        $currentFileVersion = null;
        $version = null === $version ? $mediaEntity->getFiles()[0]->getVersion() : $version;

        $file = $mediaEntity->getFiles()[0];

        /*
         * @var FileVersion
         */
        foreach ($file->getFileVersions() as $fileVersion) {
            if ($fileVersion->getVersion() == $version) {
                $currentFileVersion = $fileVersion;
            }
        }

        if (!$currentFileVersion) {
            throw new FileVersionNotFoundException($id, $version);
        }

        return $currentFileVersion;
    }

    /**
     * Cleaned up filename.
     *
     * @param string $fileName
     * @param string $locale
     * @param string $extension
     *
     * @return string
     */
    private function cleanUpFileName($fileName, $locale, $extension)
    {
        $pathInfo = pathinfo($fileName);
        $cleanedFileName = $this->get('sulu.content.path_cleaner')->cleanup($pathInfo['filename'], $locale);
        if ($extension) {
            $cleanedFileName .= '.' . $extension;
        }

        return $cleanedFileName;
    }

    /**
     * getMediaManager.
     *
     * @return FormatManagerInterface
     */
    protected function getCacheManager()
    {
        if (null === $this->cacheManager) {
            $this->cacheManager = $this->get('sulu_media.format_manager');
        }

        return $this->cacheManager;
    }

    /**
     * getMediaManager.
     *
     * @return MediaManagerInterface
     */
    protected function getMediaManager()
    {
        if (null === $this->mediaManager) {
            $this->mediaManager = $this->get('sulu_media.media_manager');
        }

        return $this->mediaManager;
    }

    /**
     * getStorage.
     *
     * @return StorageInterface
     */
    protected function getStorage()
    {
        if (null === $this->storage) {
            $this->storage = $this->get('sulu_media.storage');
        }

        return $this->storage;
    }
}
