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

use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyException;
use Sulu\Bundle\MediaBundle\Media\ImageManager\ImageManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class WebsiteMediaController extends Controller {

    protected $imageManager = null;

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getImageAction(Request $request)
    {
        try {
            $url = $request->get('url');
            list($id, $format) = $this->getImageManager()->getMediaProperties($url);
            $this->getImageManager()->returnImage($id, $format);
        } catch (ImageProxyException $e) {
            echo $e->getMessage();
            exit;
            // error 404
            throw $this->createNotFoundException('Image create error. Code: ' . $e->getCode());
        }
    }

    /**
     * getMediaManager
     * @return ImageManagerInterface
     */
    protected function getImageManager()
    {
        if ($this->imageManager === null) {
            $this->imageManager = $this->get('sulu_media.image_manager');
        }
        return $this->imageManager;
    }
}
