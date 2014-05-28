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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ImageProxyController extends Controller {

    /**
     * @param $url
     */
    public function getImageAction($url)
    {
        try {
            // TODO
        } catch (ImageProxyException $e) {
            // error 404
            throw $this->createNotFoundException('Image create error. Code: ' . $e->getCode());
        }
    }
}
