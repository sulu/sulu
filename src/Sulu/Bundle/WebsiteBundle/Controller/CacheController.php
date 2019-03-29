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

use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Handles http cache actions.
 */
class CacheController extends Controller
{
    /**
     * Clear the whole http_cache for website.
     *
     * @return JsonResponse
     */
    public function clearAction()
    {
        if (!$this->checkLivePermissionForAllWebspaces()) {
            return new JsonResponse(null, 403);
        }

        $this->get('sulu_website.http_cache.clearer')->clear();

        return new JsonResponse([], 200);
    }

    /**
     * Check the permissions for all webspaces.
     * Returns true if the user has live permission in all webspaces.
     *
     * TODO should be replaced with a single webspace cache clear.
     *
     * @return bool
     */
    private function checkLivePermissionForAllWebspaces()
    {
        foreach ($this->get('sulu_core.webspace.webspace_manager')->getWebspaceCollection() as $webspace) {
            $context = ContentAdmin::SECURITY_CONTEXT_PREFIX . $webspace->getKey();
            if (!$this->get('sulu_security.security_checker')->hasPermission($context, PermissionTypes::LIVE)) {
                return false;
            }
        }

        return true;
    }
}
