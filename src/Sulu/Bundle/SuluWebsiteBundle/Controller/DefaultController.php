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
use Sulu\Component\Content\StructureInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;

/**
 * Default Controller for rendering templates, uses the themes from the ClientWebsiteBundle
 * @package Sulu\Bundle\WebsiteBundle\Controller
 */
class DefaultController extends WebsiteController
{
    /**
     * Loads the content from the request (filled by the route provider) and creates a response with this content and
     * the appropriate cache headers
     * @param \Sulu\Component\Content\StructureInterface $structure
     * @param bool $preview
     * @param bool $partial
     * @return Response
     */
    public function indexAction(StructureInterface $structure, $preview = false, $partial = false)
    {
        $response = $this->renderStructure($structure, array(), $preview, $partial);
        return $response;
    }

    public function error404Action()
    {
        $content = $this->renderView(
            'ClientWebsiteBundle:Website:error404.html.twig',
            array('path' => $this->getRequest()->get('path'))
        );

        $response = new Response();
        $response->setStatusCode(404);

        $response->setContent($content);

        return $response;
    }

    public function redirectAction()
    {
        $url = str_replace(
            $this->getRequest()->get('url'),
            $this->getRequest()->get('redirect'),
            $this->getRequest()->getUri()
        );

        return new RedirectResponse($url, 301);
    }
}
