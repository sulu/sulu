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

use Sulu\Bundle\WebsiteBundle\Cache\CacheClearerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\WebspaceReferenceStore;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
    public function clearAction(Request $request)
    {
        $webspaceKey = $request->query->get('webspaceKey');
        if ($webspaceKey && !$this->checkLivePermissionForWebspace($webspaceKey)) {
            return new JsonResponse(null, 403);
        }
        if (!$webspaceKey && !$this->checkLivePermissionForAllWebspaces()) {
            return new JsonResponse(null, 403);
        }

        $tags = [];
        if ($webspaceKey) {
            $tags[] = WebspaceReferenceStore::generateTagByWebspaceKey($webspaceKey);
        }

        $this->cacheClearer->clear(empty($tags) ? null : $tags);

        return new JsonResponse(null, 204);
    }

    /**
     * Check the permissions for all webspaces.
     * Returns true if the user has live permission in all webspaces.
     *
     * @return bool
     */
    private function checkLivePermissionForAllWebspaces()
    {
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $context = PageAdmin::getPageSecurityContext($webspace->getKey());
            if (!$this->securityChecker->hasPermission($context, PermissionTypes::LIVE)) {
                return false;
            }
        }

        return true;
    }

    private function checkLivePermissionForWebspace(string $webspaceKey): bool
    {
        return $this->securityChecker->hasPermission(
            PageAdmin::getPageSecurityContext($webspaceKey),
            PermissionTypes::LIVE
        );
    }
}
