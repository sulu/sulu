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
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class WebsiteMediaController extends Controller
{

    protected $cacheManager = null;

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
}
