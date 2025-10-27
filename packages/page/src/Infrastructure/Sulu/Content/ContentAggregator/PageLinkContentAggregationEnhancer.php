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

namespace Sulu\Page\Infrastructure\Sulu\Content\ContentAggregator;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregationEnhancerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own content aggregation enhancer instead.
 */
class PageLinkContentAggregationEnhancer implements ContentAggregationEnhancerInterface
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentAggregatorInterface $contentAggregator,
        private LinkProviderPoolInterface $linkProviderPool,
    ) {
    }

    public function enhance(
        DimensionContentInterface $dimensionContent,
        array $dimensionAttributes
    ): DimensionContentInterface {
        if (!$dimensionContent instanceof PageDimensionContentInterface) {
            return $dimensionContent;
        }

        $linkData = $dimensionContent->getLinkData();

        return match ($linkData['provider'] ?? null) {
            null => $dimensionContent,
            'page' => $this->resolvePage($dimensionContent, $dimensionAttributes),
            default => $this->resolveLink($dimensionContent),
        };
    }

    /**
     * @template T of PageDimensionContentInterface
     *
     * @param T $pageDimensionContent
     * @param array<string, mixed> $dimensionAttributes
     *
     * @return T
     */
    private function resolvePage(
        PageDimensionContentInterface $pageDimensionContent,
        array $dimensionAttributes,
    ): PageDimensionContentInterface {
        $linkData = $pageDimensionContent->getLinkData();

        $href = $linkData['href'] ?? null;
        if (!\is_string($href)) {
            return $pageDimensionContent;
        }

        $page = $this->pageRepository->findOneBy([
            'uuid' => $href,
        ]);

        if (null === $page) {
            return $pageDimensionContent;
        }

        /** @var PageDimensionContentInterface $targetDimensionContent */
        $targetDimensionContent = $this->contentAggregator->aggregate($page, $dimensionAttributes);

        $targetDimensionContent->setTemplateData([
            ...$targetDimensionContent->getTemplateData(),
            ...[
                'title' => $pageDimensionContent->getTitle(),
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

        $pageDimensionContent->setTemplateData([
            ...$pageDimensionContent->getTemplateData(),
            ...[
                'url' => $linkItem->getUrl(),
            ],
        ]);

        return $pageDimensionContent;
    }
}
