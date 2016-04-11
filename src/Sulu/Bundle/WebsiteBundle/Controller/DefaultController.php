<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default Controller for rendering templates, uses the themes from the ClientWebsiteBundle.
 */
class DefaultController extends WebsiteController
{
    /**
     * Loads the content from the request (filled by the route provider) and creates a response with this content and
     * the appropriate cache headers.
     *
     * @param \Sulu\Component\Content\Compat\StructureInterface $structure
     * @param bool $preview
     * @param bool $partial
     *
     * @return Response
     */
    public function indexAction(StructureInterface $structure, $preview = false, $partial = false)
    {
        $response = $this->renderStructure(
            $structure,
            [],
            $preview,
            $partial
        );

        return $response;
    }

    /**
     * Creates a redirect for configured webspaces.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @deprecated since 1.2 use SuluWebsiteBundle:Redirect:redirectWebspace instead
     */
    public function redirectWebspaceAction(Request $request)
    {
        @trigger_error('SuluWebsiteBundle:Default:redirectWebspace is deprecated since version 1.2. Use the "SuluWebsiteBundle:Redirect:redirectWebspace" action instead.', E_USER_DEPRECATED);

        return $this->forward(
            'SuluWebsiteBundle:Redirect:redirectWebspace',
            $request->attributes->all(),
            $request->query->all()
        );
    }

    /**
     * Creates a redirect for *.html to * (without html).
     *
     * @param Request $request
     *
     * @return Response
     *
     * @deprecated since 1.2 use SuluWebsiteBundle:Redirect:redirect instead
     */
    public function redirectAction(Request $request)
    {
        @trigger_error('SuluWebsiteBundle:Default:redirect is deprecated since version 1.2. Use the "SuluWebsiteBundle:Redirect:redirect" action instead.', E_USER_DEPRECATED);

        return $this->forward(
            'SuluWebsiteBundle:Redirect:redirect',
            $request->attributes->all(),
            $request->query->all()
        );
    }
}
