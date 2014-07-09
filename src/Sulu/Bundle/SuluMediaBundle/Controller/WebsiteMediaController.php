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
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteMediaController extends Controller
{

    /**
     * @var FormatManagerInterface
     */
    protected $cacheManager = null;

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
            ob_end_clean();

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
            ob_end_clean();

            $version = $request->get('v', null);

            /**
             * @var Media $mediaEntity
             */
            $mediaEntity = $this->getDoctrine()
                ->getRepository('SuluMediaBundle:Media')
                ->findMediaById($id);

            $fileName = null;
            $fileSize = null;
            $storageOptions = null;
            $version = $version === null ? $mediaEntity->getFiles()[0]->getVersion() : $version;

            $file = $mediaEntity->getFiles()[0];

            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    $fileName = $fileVersion->getName();
                    $fileSize = $fileVersion->getSize();
                    $storageOptions = $fileVersion->getStorageOptions();
                }
            }

            $path = $this->getStorage()->load($fileName, $version, $storageOptions);

            // in case you need the container
            $container = $this->container;
            $response = new StreamedResponse(function() use($container, $path) {
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
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($fileName) . '";');
            $response->headers->set('Content-length', $fileSize);

            return $response;
        } catch (MediaException $e) {
            throw $this->createNotFoundException('File not found: ' . $e->getCode());
        }
    }

    /**
     * getMediaManager
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
     * getStorage
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
