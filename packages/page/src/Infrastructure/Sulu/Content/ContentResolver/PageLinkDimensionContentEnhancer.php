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
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\DimensionContentEnhancerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own dimension content enhancer instead.
 */
class PageLinkDimensionContentEnhancer implements DimensionContentEnhancerInterface
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentAggregatorInterface $contentAggregator,
        private LinkProviderPoolInterface $linkProviderPool,
    ) {
    }

    public function enhance(DimensionContentInterface $dimensionContent): DimensionContentInterface
    {
        if (!$dimensionContent instanceof PageDimensionContentInterface) {
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

        $url = $targetDimensionContent->getTemplateData()['url'] ?? null;
        if (\is_string($url)) {
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
        if (!\is_string($provider) || !\is_string($url)) {
            return $pageDimensionContent;
        }

        $linkProvider = $this->linkProviderPool->getProvider($provider);
        $preloadResult = $linkProvider->preload([$url], $pageDimensionContent->getLocale() ?? 'en');
        $linkItem = [...$preloadResult][0] ?? null;

        if (null === $linkItem) {
            return $pageDimensionContent;
        }

        $url = $this->appendQueryAndAnchor($linkItem->getUrl(), $linkData);

        $pageDimensionContent->setTemplateData([
            ...$pageDimensionContent->getTemplateData(),
            ...[
                'url' => $url,
            ],
        ]);

        return $pageDimensionContent;
    }

    /**
     * Append query string and anchor to a URL based on linkData.
     *
     * @param array<string, mixed> $linkData
     */
    private function appendQueryAndAnchor(string $url, array $linkData): string
    {
        if (isset($linkData['query']) && \is_string($linkData['query'])) {
            $url = \sprintf('%s?%s', $url, \ltrim($linkData['query'], '?'));
        }
        if (isset($linkData['anchor']) && \is_string($linkData['anchor'])) {
            $url = \sprintf('%s#%s', $url, \ltrim($linkData['anchor'], '#'));
        }

        return $url;
    }
}
