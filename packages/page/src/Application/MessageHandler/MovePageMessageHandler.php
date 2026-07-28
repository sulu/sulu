<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\MessageHandler;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Page\Application\Message\MovePageMessage;
use Sulu\Page\Domain\Event\PageMovedEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorGeneratorInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorRequest;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create a PageMapper to extend this Handler.
 */
class MovePageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private RouteRepositoryInterface $routeRepository,
        private ResourceLocatorGeneratorInterface $resourceLocatorGenerator,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(MovePageMessage $message): PageInterface
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());
        $previousParent = $page->getParent();

        $this->pageRepository->moveOneBy($message->getIdentifier(), $message->getTargetParentIdentifier());

        $newParent = $page->getParent();
        if (null !== $newParent && $newParent !== $previousParent) {
            $this->moveRoutes($page, $previousParent, $newParent);
        }

        if (null === $previousParent) {
            $this->domainEventCollector->collect(new PageMovedEvent(
                $page,
                $message->getLocale(),
                null,
                null,
                null,
            ));

            return $page;
        }

        $previousParentDimensionContentCollection = new DimensionContentCollection($previousParent->getDimensionContents(), [], PageDimensionContent::class);
        /** @var PageDimensionContent $previousParentLocalizedDimensionContent */
        $previousParentLocalizedDimensionContent = $previousParentDimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);

        $this->domainEventCollector->collect(new PageMovedEvent(
            $page,
            $message->getLocale(),
            $previousParent->getUuid(),
            $previousParent->getWebspaceKey(),
            $previousParentLocalizedDimensionContent->getTitle(),
        ));

        return $page;
    }

    /**
     * Re-anchors the page's route(s) under the new parent; the RouteChangedUpdater cascades
     * descendants and history on flush.
     */
    private function moveRoutes(PageInterface $page, ?PageInterface $previousParent, PageInterface $newParent): void
    {
        $routes = $this->routeRepository->findBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $page->getUuid(),
        ]);

        foreach ($routes as $route) {
            if (null !== $route->getParentRoute()) {
                // route is anchored to an external page (e.g. via a page_tree_route field),
                // not to the page tree, so moving the page must not re-anchor it
                continue;
            }

            $locale = $route->getLocale();
            $oldSlug = $route->getSlug();
            $newSlug = $this->resourceLocatorGenerator->generate(new ResourceLocatorRequest(
                parts: [],
                locale: $locale,
                webspace: $route->getWebspace(),
                resourceKey: PageInterface::RESOURCE_KEY,
                resourceId: $page->getUuid(),
                parentResourceId: $newParent->getUuid(),
                parentResourceKey: PageInterface::RESOURCE_KEY,
                routeSchema: $this->relativeSlug($oldSlug, $previousParent, $locale),
            ));

            if ($oldSlug !== $newSlug) {
                $route->setSlug($newSlug);
            }
        }
    }

    /**
     * The page's own slug segment relative to its previous parent, or the trailing segment when the
     * previous parent has no route in this locale. The parent path must be followed by a "/", so a
     * sibling that merely shares a string prefix is not stripped.
     */
    private function relativeSlug(string $slug, ?PageInterface $previousParent, string $locale): string
    {
        if (null === $previousParent) {
            return $slug;
        }

        $previousParentRoute = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $previousParent->getUuid(),
            'locale' => $locale,
        ]);

        if (null === $previousParentRoute) {
            return \substr($slug, (int) \strrpos($slug, '/'));
        }

        $previousParentPath = \rtrim($previousParentRoute->getSlug(), '/');

        if ('' !== $previousParentPath && \str_starts_with($slug, $previousParentPath . '/')) {
            return \substr($slug, \strlen($previousParentPath));
        }

        return $slug;
    }
}
