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

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\WebsiteBundle\Cache\CacheClearerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Handles http cache actions.
 */
class CacheController
{
    /**
     * @var CacheClearerInterface
     */
    private $cacheClearer;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        CacheClearerInterface $cacheClearer,
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->cacheClearer = $cacheClearer;
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
    }

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

        $this->cacheClearer->clear();

        return new JsonResponse(null, 204);
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
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $context = PageAdmin::SECURITY_CONTEXT_PREFIX . $webspace->getKey();
            if (!$this->securityChecker->hasPermission($context, PermissionTypes::LIVE)) {
                return false;
            }
        }

        return true;
    }
}
