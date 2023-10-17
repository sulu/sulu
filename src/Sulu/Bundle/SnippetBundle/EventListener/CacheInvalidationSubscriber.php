<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\EventListener;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\WebspaceDefaultSnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var CacheManager|null
     */
    private $cacheManager;

    /**
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    public function __construct(
        ?CacheManager $cacheManager,
        DefaultSnippetManagerInterface $defaultSnippetManager
    ) {
        $this->cacheManager = $cacheManager;
        $this->defaultSnippetManager = $defaultSnippetManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            WebspaceDefaultSnippetModifiedEvent::class => 'invalidateSnippetAreaOnAreaModified',
            WebspaceDefaultSnippetRemovedEvent::class => 'invalidateSnippetAreaOnAreaRemoved',
            SnippetModifiedEvent::class => 'invalidateSnippetAreaOnModified',
            SnippetRemovedEvent::class => 'invalidateSnippetAreaOnRemoved',
        ];
    }

    public function invalidateSnippetAreaOnModified(SnippetModifiedEvent $event): void
    {
        $this->invalidateSnippetAreaBySnippet($event->getResourceId());
    }

    public function invalidateSnippetAreaOnRemoved(SnippetRemovedEvent $event): void
    {
        $this->invalidateSnippetAreaBySnippet($event->getResourceId());
    }

    public function invalidateSnippetAreaOnAreaRemoved(WebspaceDefaultSnippetRemovedEvent $event): void
    {
        $this->invalidateSnippetAreaByKey($event->getSnippetAreaKey());
    }

    public function invalidateSnippetAreaOnAreaModified(WebspaceDefaultSnippetModifiedEvent $event): void
    {
        $this->invalidateSnippetAreaByKey($event->getSnippetAreaKey());
    }

    private function invalidateSnippetAreaBySnippet(string $snippetUuid): void
    {
        $areaKey = $this->defaultSnippetManager->loadType($snippetUuid);

        if (!$areaKey) {
            return;
        }

        $this->invalidateSnippetAreaByKey($areaKey);
    }

    private function invalidateSnippetAreaByKey(string $areaKey): void
    {
        if (!$this->cacheManager) {
            return;
        }

        $this->cacheManager->invalidateReference('snippet_area', $areaKey);
    }
}
