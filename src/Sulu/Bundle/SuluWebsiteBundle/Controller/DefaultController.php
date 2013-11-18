<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Component\Content\Structure;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default Controller for rendering templates
 * @package Sulu\Bundle\WebsiteBundle\Controller
 */
class DefaultController extends Controller
{
    public function indexAction()
    {
        /** @var Structure $content */
        $content = $this->getRequest()->get('content');
        $response = new Response();

        $response->setPublic();
        $response->setPrivate();
        $response->setSharedMaxAge($content->getCacheLifeTime());
        $response->setMaxAge($content->getCacheLifeTime());

        $response->setContent('<h1>' . $content->title . '</h1><p>' . $content->article . '</p>');

        return $response;
    }
}
