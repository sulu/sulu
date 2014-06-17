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
use Sulu\Bundle\MediaBundle\Media\ImageManager\CacheManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class WebsiteMediaController extends Controller {

    protected $cacheManager = null;

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getImageAction(Request $request)
    {
        try {
            ob_clean(); // FIXME clean whitespaces and breaks

            $url = $request->get('url');
            list($id, $format) = $this->getCacheManager()->getMediaProperties($url);

            return $this->getCacheManager()->returnImage($id, $format);
        } catch (ImageProxyException $e) {
            throw $this->createNotFoundException('Image create error. Code: ' . $e->getCode());
        }
    }

    /**
     * getMediaManager
     * @return CacheManagerInterface
     */
    protected function getCacheManager()
    {
        if ($this->cacheManager === null) {
            $this->cacheManager = $this->get('sulu_media.thumbnail_manager');
        }
        return $this->cacheManager;
    }
}
