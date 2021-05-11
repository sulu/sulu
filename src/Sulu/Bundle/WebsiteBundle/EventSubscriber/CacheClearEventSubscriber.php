<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\WebsiteBundle\Domain\Event\CacheClearedEvent;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
use Sulu\Bundle\WebsiteBundle\Events;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\WebspaceReferenceStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class CacheClearEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        DomainEventCollectorInterface $domainEventCollector,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->domainEventCollector = $domainEventCollector;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::CACHE_CLEAR => 'onCacheClear',
        ];
    }

    public function onCacheClear(CacheClearEvent $event): void
    {
        $tags = $event->getTags();
        if (null === $tags || 0 === \count($tags)) {
            return;
        }

        $webspaceKey = WebspaceReferenceStore::getWebspaceKeyFromTag(\reset($tags));
        $this->domainEventCollector->collect(new CacheClearedEvent($webspaceKey, $tags));
        $this->entityManager->flush();
    }
}
