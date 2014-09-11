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

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * handles navigation for this bundle
 */
class NavigationController extends Controller
{

    const SERVICE_NAME = 'sulu_media.admin.content_navigation';

    /**
     * Returns the navigation for a single collection
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function collectionAction()
    {
        /** @var ContentNavigation $contentNavigation */
        if ($this->has(self::SERVICE_NAME)) {
            $contentNavigation = $this->get(self::SERVICE_NAME);
        }

        return new JsonResponse($contentNavigation->toArray('collection'));
    }
}
