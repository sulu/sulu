<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
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

            if (!$noCount) {
                $this->getMediaManager()->increaseDownloadCounter($fileVersion->getId());
            }

            $response = $this->getFileResponse($fileVersion);

            return $response;
        } catch (MediaException $e) {
            throw $this->createNotFoundException('File not found: ' . $e->getCode() . ' ' . $e->getMessage());
        }
    }

    /**
     * @param FileVersion $fileVersion
     *
     * @return StreamedResponse
     */
    protected function getFileResponse($fileVersion)
    {
        $fileName = $fileVersion->getName();
        $fileSize = $fileVersion->getSize();
        $storageOptions = $fileVersion->getStorageOptions();
        $mimeType = $fileVersion->getMimeType();
        $version = $fileVersion->getVersion();

        $path = $this->getStorage()->load($fileName, $version, $storageOptions);

        $response = new StreamedResponse(function () use ($path) {
            flush(); // send headers
            $handle = fopen($path, 'r');
            while (!feof($handle)) {
                $buffer = fread($handle, 1024);
                echo $buffer;
                flush(); // buffered output
            }
            fclose($handle);
        });

        // Set headers
        $response->headers->set('Content-Type', !empty($mimeType) ? $mimeType : 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($fileName) . '";');
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
        /**
         * @var Media
         */
        $mediaEntity = $this->getDoctrine()
            ->getRepository('SuluMediaBundle:Media')
            ->findMediaById($id);

        $currentFileVersion = null;
        $version = $version === null ? $mediaEntity->getFiles()[0]->getVersion() : $version;

        $file = $mediaEntity->getFiles()[0];

        /**
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
     * getMediaManager.
     *
     * @return FormatManagerInterface
     */
    protected function getCacheManager()
    {
        if ($this->cacheManager === null) {
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
        if ($this->mediaManager === null) {
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
        if ($this->storage === null) {
            $this->storage = $this->get('sulu_media.storage');
        }

        return $this->storage;
    }
}
