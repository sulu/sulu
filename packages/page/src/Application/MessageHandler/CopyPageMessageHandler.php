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
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Application\Message\CopyPageMessage;
use Sulu\Page\Domain\Event\PageCopiedEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorGeneratorInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorRequest;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyPageMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentCopierInterface $contentCopier,
        private LocalizationManagerInterface $localizationManager,
        private RouteRepositoryInterface $routeRepository,
        private ResourceLocatorGeneratorInterface $resourceLocatorGenerator,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(CopyPageMessage $message): PageInterface
    {
        $allLocales = [];
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $allLocales[] = $localization->getLocale();
        }

        $sourcePage = $this->pageRepository->getOneBy(
            $message->getSourceIdentifier(),
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true],
                    'dimensionAttributes' => [
                        'locale' => $allLocales,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                    ],
                ],
            ]
        );
        $targetParentPage = $this->pageRepository->getOneBy(
            $message->getTargetParentIdentifier(),
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [], // No excerpt/tags needed - only used for webspace/parent info
                    'dimensionAttributes' => [
                        'locale' => $message->getLocale(),
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                    ],
                ],
            ]
        );

        $targetPage = $this->pageRepository->createNew($message->getTargetUuid());
        $targetPage->setWebspaceKey($targetParentPage->getWebspaceKey());
        $targetPage->setParent($targetParentPage);
        $this->pageRepository->add($targetPage);

        $dimensionContentCollection = new DimensionContentCollection(
            $sourcePage->getDimensionContents(),
            [],
            PageDimensionContent::class
        );

        $copiedLocales = [];
        foreach ($allLocales as $locale) {
            $sourceDimensionContent = $dimensionContentCollection->getDimensionContent([
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]);

            if ($sourceDimensionContent?->getLocale() !== $locale) {
                // If the page does not exist in the target locale, we cannot copy content for that locale.
                continue;
            }

            $this->contentCopier->copy(
                $sourcePage,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $locale,
                ],
                $targetPage,
                [
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                    'locale' => $locale,
                ],
                [
                    // url is re-resolved per-locale below against the new parent
                    'ignoredAttributes' => ['url'],
                ]
            );

            $copiedLocales[] = $locale;
        }

        $this->createRoutesForCopiedPage(
            $sourcePage,
            $targetPage,
            $targetParentPage,
            $copiedLocales,
        );

        /** @var PageDimensionContent $localizedDimensionContent */
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent([
            'locale' => $message->getLocale(),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ]);

        $this->domainEventCollector->collect(new PageCopiedEvent($sourcePage, $sourcePage->getId(), $sourcePage->getWebspaceKey(), $localizedDimensionContent->getTitle(), $message->getLocale()));

        return $targetPage;
    }

    /**
     * Creates the copied page's route(s) under the target parent, reusing the source slug
     * relative to its old parent.
     *
     * @param array<string> $copiedLocales
     */
    private function createRoutesForCopiedPage(
        PageInterface $sourcePage,
        PageInterface $targetPage,
        PageInterface $targetParentPage,
        array $copiedLocales,
    ): void {
        if ([] === $copiedLocales) {
            return;
        }

        $sourceParent = $sourcePage->getParent();

        $targetCollection = new DimensionContentCollection(
            $targetPage->getDimensionContents(),
            [],
            PageDimensionContent::class
        );

        foreach ($copiedLocales as $locale) {
            /** @var PageDimensionContent|null $targetDimensionContent */
            $targetDimensionContent = $targetCollection->getDimensionContent([
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]);

            if (null === $targetDimensionContent || $targetDimensionContent->getLocale() !== $locale) {
                continue;
            }

            $sourceRoute = $this->routeRepository->findOneBy([
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => $sourcePage->getUuid(),
                'locale' => $locale,
            ]);

            if (null === $sourceRoute || $sourceRoute->isHistory()) {
                continue;
            }

            $targetParentRoute = $this->routeRepository->findOneBy([
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => $targetParentPage->getUuid(),
                'locale' => $locale,
            ]);

            if (null === $targetParentRoute) {
                // target parent not routable in this locale → copy keeps no route
                continue;
            }

            $slug = $this->resourceLocatorGenerator->generate(new ResourceLocatorRequest(
                parts: [],
                locale: $locale,
                webspace: $targetPage->getWebspaceKey(),
                resourceKey: PageInterface::RESOURCE_KEY,
                resourceId: $targetPage->getUuid(),
                parentResourceId: $targetParentPage->getUuid(),
                parentResourceKey: PageInterface::RESOURCE_KEY,
                routeSchema: $this->relativeSlug($sourceRoute->getSlug(), $sourceParent, $locale),
            ));

            // parent_id stays null like normal page routes; cascade is slug-based
            $route = new Route(
                PageInterface::RESOURCE_KEY,
                $targetPage->getUuid(),
                $locale,
                $slug,
                $targetPage->getWebspaceKey(),
            );
            $this->routeRepository->add($route);

            $targetDimensionContent->setRoute($route);
        }
    }

    /**
     * Slug relative to the old parent, or the full slug when there is no old parent route.
     */
    private function relativeSlug(string $slug, ?PageInterface $parent, string $locale): string
    {
        if (null === $parent) {
            return $slug;
        }

        $parentRoute = $this->routeRepository->findOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $parent->getUuid(),
            'locale' => $locale,
        ]);

        $parentPath = \rtrim($parentRoute?->getSlug() ?? '', '/');

        if ('' !== $parentPath && \str_starts_with($slug, $parentPath)) {
            return \substr($slug, \strlen($parentPath));
        }

        return $slug;
    }
}
