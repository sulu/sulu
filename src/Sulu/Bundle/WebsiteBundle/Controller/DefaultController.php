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

use Sulu\Component\Content\StructureInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $response = $this->renderStructure(
            $structure,
            array(
                'navigation' => $this->getMainNavigation($structure)
            ),
            $preview,
            $partial
        );

        return $response;
    }

    public function redirectAction(Request $request)
    {
        $url = rtrim(str_replace(
            $request->get('url'),
            $request->get('redirect'),
            $request->getUri()
        ), '/');

        return new RedirectResponse($url, 301);
    }
}
