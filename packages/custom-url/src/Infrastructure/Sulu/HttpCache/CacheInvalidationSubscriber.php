<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Infrastructure\Sulu\HttpCache;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRouteRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * When a content has changed or been removed we need to invalidate all custom url routes that point to the content
 * When a custom route has been removed we need to invalidate all the caches too.
 */
class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private CustomUrlRouteRepositoryInterface $customUrlRouteRepository,
        private ?CacheManager $cacheManager,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PUBLISH => ['invalidateDocument', 1024],
            Events::REMOVE => ['invalidateDocument', 1024],
        ];
    }

    public function invalidateDocument(PublishEvent|RemoveEvent $event): void
    {
        $document = $event->getDocument();
        if ($document instanceof UuidBehavior) {
            $this->invalidateDocumentImplemenation($document);
        }
    }

    private function invalidateDocumentImplemenation(UuidBehavior|RouteDocument|CustomUrlInterface|CustomUrlRoute $document): void
    {
        if (!$this->cacheManager) {
            return;
        }

        if ($document instanceof UuidBehavior) {
            foreach ($this->customUrlRepository->findByTarget($document) as $customUrlDocument) {
                $this->invalidateCustomUrlDocument($customUrlDocument);
            }
        } elseif ($document instanceof RouteDocument) {
            $this->invalidateDocumentImplemenation($document->getTargetDocument());
        } elseif ($document instanceof CustomUrlInterface) {
            $this->invalidateCustomUrlDocument($document);
        } else {
            $this->invalidateCustomUrl($document);
        }
    }

    private function invalidateCustomUrlDocument(CustomUrlInterface $customUrl): void
    {
        foreach ($this->customUrlRouteRepository->findByCustomUrl($customUrl) as $route) {
            $this->cacheManager?->invalidatePath($this->getUrlWithScheme($route->getPath()));
        }
    }

    private function invalidateCustomUrl(CustomUrlRoute $customUrlRoute): void
    {
        $this->cacheManager?->invalidatePath($this->getUrlWithScheme($customUrlRoute->getPath()));
    }

    private function getUrlWithScheme(string $url): string
    {
        $scheme = 'http';
        if ($request = $this->requestStack->getCurrentRequest()) {
            $scheme = $request->getScheme();
        }

        return \sprintf('%s://%s', $scheme, $url);
    }
}
