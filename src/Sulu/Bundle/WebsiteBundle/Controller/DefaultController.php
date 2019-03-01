<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Component\Content\Compat\StructureInterface;
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
}
