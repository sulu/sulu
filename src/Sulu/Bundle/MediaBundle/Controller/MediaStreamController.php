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
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
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
     * @return Response
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

            if ($request->query->has('inline')) {
                $forceInline = (bool) $request->get('inline', false);
                $dispositionType = $forceInline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT;
            } else {
                $dispositionType = $this->get('sulu_media.disposition_type.resolver')
                    ->getByMimeType($fileVersion->getMimeType());
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
        $fileName = $fileVersion->getName();
        $fileSize = $fileVersion->getSize();
        $storageOptions = $fileVersion->getStorageOptions();
        $mimeType = $fileVersion->getMimeType();
        $version = $fileVersion->getVersion();
        $lastModified = $fileVersion->getCreated(); // use created as file itself is not changed when entity is changed

        $path = $this->getStorage()->load($fileName, $version, $storageOptions);

        $response = new BinaryFileResponse($path);

        // Prepare headers
        $disposition = $response->headers->makeDisposition(
            $dispositionType,
            $fileName,
            $this->cleanUpFileName($fileName, $locale, $fileVersion->getExtension())
        );

        // Set headers for
        $file = $fileVersion->getFile();
        if ($fileVersion->getVersion() !== $file->getVersion()) {
            $latestFileVersion = $file->getLatestFileVersion();

            $response->headers->set(
                'Link',
                sprintf(
                    '<%s>; rel="canonical"',
                    $this->getMediaManager()->getUrl(
                        $file->getMedia()->getId(),
                        $latestFileVersion->getName(),
                        $latestFileVersion->getVersion()
                    )
                )
            );
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        // Set headers
        $response->headers->set('Content-Type', !empty($mimeType) ? $mimeType : 'application/octet-stream');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-length', $fileSize);
        $response->headers->set('Last-Modified', $lastModified->format('D, d M Y H:i:s \G\M\T'));

        return $response;
    }

    /**
     * @param int $id
     * @param int $version
     *
     * @return FileVersion|null
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException
     */
    protected function getFileVersion($id, $version)
    {
        /** @var MediaInterface $mediaEntity */
        $mediaEntity = $this->container->get('sulu.repository.media')->findMediaById($id);

        if (!$mediaEntity) {
            return null;
        }

        $file = $mediaEntity->getFiles()[0];

        if (!$file) {
            return null;
        }

        if (!$version) {
            $version = $mediaEntity->getFiles()[0]->getVersion();
        }

        $fileVersion = $file->getFileVersion((int) $version);

        if (!$fileVersion) {
            throw new FileVersionNotFoundException($id, $version);
        }

        return $fileVersion;
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
