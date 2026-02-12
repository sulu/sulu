<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\ContentResolver;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkUrlTrait;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\DimensionContentEnhancerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own dimension content enhancer instead.
 */
class PageLinkDimensionContentEnhancer implements DimensionContentEnhancerInterface
{
    use LinkUrlTrait;

    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentAggregatorInterface $contentAggregator,
        private LinkProviderPoolInterface $linkProviderPool,
        private WebspaceManagerInterface $webspaceManager,
    ) {
    }

    public function enhance(DimensionContentInterface $dimensionContent): DimensionContentInterface
    {
        if (!$dimensionContent instanceof PageDimensionContentInterface) {
            return $dimensionContent;
        }

        if (null === $dimensionContent->getLocale()) {
            return $dimensionContent;
        }

        $linkData = $dimensionContent->getLinkData();

        return match ($linkData['provider'] ?? null) {
            null => $dimensionContent,
            'page' => $this->resolvePage(
                $dimensionContent,
                $dimensionContent::getEffectiveDimensionAttributes(
                    [
                        'locale' => $dimensionContent->getLocale(),
                        'stage' => $dimensionContent->getStage(),
                        'version' => $dimensionContent->getVersion(),
                    ]
                )
            ),
            default => $this->resolveLink($dimensionContent),
        };
    }

    /**
     * @template T of PageDimensionContentInterface
     *
     * @param T $pageDimensionContent
     * @param array<mixed> $dimensionAttributes
     *
     * @return T
     */
    private function resolvePage(
        PageDimensionContentInterface $pageDimensionContent,
        array $dimensionAttributes,
    ): PageDimensionContentInterface {
        $linkData = $pageDimensionContent->getLinkData();

        $href = $linkData['href'] ?? null;
        if (!\is_string($href) || $pageDimensionContent->getResourceId() === $href) {
            return $pageDimensionContent;
        }

        /** @var string|null $locale */
        $locale = $dimensionAttributes['locale'] ?? null;
        /** @var string|null $stage */
        $stage = $dimensionAttributes['stage'] ?? null;

        $page = $this->pageRepository->findOneBy(
            ['uuid' => $href],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [], // No excerpt/tags needed for link resolution
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => $stage ?? DimensionContentInterface::STAGE_DRAFT,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                ],
            ]
        );

        if (null === $page) {
            return $pageDimensionContent;
        }

        /** @var PageDimensionContentInterface $targetDimensionContent */
        $targetDimensionContent = $this->contentAggregator->aggregate($page, $dimensionAttributes);

        $route = $targetDimensionContent->getRoute();
        $targetLocale = $targetDimensionContent->getLocale();
        /** @var PageInterface $targetPage */
        $targetPage = $targetDimensionContent->getResource();
        $url = null !== $route && null !== $targetLocale
            ? $this->webspaceManager->findUrlByResourceLocator(
                $route->getSlug(),
                null,
                $targetLocale,
                $targetPage->getWebspaceKey(),
            )
            : null;

        if (\is_string($url) && null !== $linkData) {
            $url = $this->appendQueryAndAnchor($url, $linkData);
        }

        $targetDimensionContent->setTemplateData([
            ...$targetDimensionContent->getTemplateData(),
            ...[
                'title' => $pageDimensionContent->getTitle(),
                'url' => $url,
            ],
        ]);

        return $targetDimensionContent; // @phpstan-ignore-line return.type
    }

    /**
     * @template T of PageDimensionContentInterface
     *
     * @param T $pageDimensionContent
     *
     * @return T
     */
    private function resolveLink(PageDimensionContentInterface $pageDimensionContent): PageDimensionContentInterface
    {
        $linkData = $pageDimensionContent->getLinkData();

        $url = $linkData['href'] ?? null;
        $provider = $linkData['provider'] ?? null;
        $locale = $pageDimensionContent->getLocale();
        if (!\is_string($provider) || !\is_string($url) || null === $locale) {
            return $pageDimensionContent;
        }

        $linkProvider = $this->linkProviderPool->getProvider($provider);
        $preloadResult = $linkProvider->preload([$url], $locale);
        $linkItem = [...$preloadResult][0] ?? null;

        if (null === $linkItem) {
            return $pageDimensionContent;
        }

        $url = $linkItem->getUrl();
        if (null !== $linkData) {
            $url = $this->appendQueryAndAnchor($url, $linkData);
        }

        $pageDimensionContent->setTemplateData([
            ...$pageDimensionContent->getTemplateData(),
            ...[
                'url' => $url,
            ],
        ]);

        return $pageDimensionContent;
    }
}
