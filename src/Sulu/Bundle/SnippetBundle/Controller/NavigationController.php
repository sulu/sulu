<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * handles navigation for this bundle
 */
class NavigationController extends Controller
{

    const SERVICE_NAME = 'sulu_snippet.admin.snippet_navigation';

    /**
     * Returns content navigation for content form
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function snippetAction()
    {
        /** @var ContentNavigation $contentNavigation */
        if ($this->has(self::SERVICE_NAME)) {
            $contentNavigation = $this->get(self::SERVICE_NAME);
        }

        return new JsonResponse($contentNavigation->toArray('snippet'));
    }
}
