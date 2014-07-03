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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            $version = $request->get('v', null);

            /**
             * @var Media $mediaEntity
             */
            $mediaEntity = $this->getDoctrine()
                ->getRepository('SuluMediaBundle:Media')
                ->findMediaById($id);

            $fileName = null;
            $storageOptions = null;
            $version = $version === null ? $mediaEntity->getFiles()[0]->getVersion() : $version;

            /**
             * @var FileVersion $fileVersion
             */
            foreach ($mediaEntity->getFiles()[0]->getFileVersion() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    $fileName = $fileVersion->getName();
                    $storageOptions = $fileVersion->getStorageOptions();
                }
            }

            $path = $this->getStorage()->load($fileName, $version, $storageOptions);

            try {
                $file = file_get_contents($path);
            } catch (Exception $e) {
                throw new FileNotFoundException('File not found');
            }

            // Generate response
            $response = new Response();
            $response->setContent($file);

            // Set headers
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', mime_content_type($fileName));
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($fileName) . '";');
            $response->headers->set('Content-length', filesize($fileName));

            return new $response;
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
            $this->storage = $this->get('sulu_media.');
        }
        return $this->storage;
    }
}
