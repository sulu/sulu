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

namespace Sulu\Page\Application\ContentAggregator;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregationEnhancerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\ContentBehaviorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own content aggregation enhancer instead.
 */
class PageContentBehaviorEnhancer implements ContentAggregationEnhancerInterface
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentAggregatorInterface $contentAggregator,
        private LinkProviderPoolInterface $linkProviderPool,
    ) {
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     * @param array<string, mixed> $dimensionAttributes
     *
     * @return T
     */
    public function enhance(
        DimensionContentInterface $dimensionContent,
        array $dimensionAttributes
    ): DimensionContentInterface {
        if (!$dimensionContent instanceof PageDimensionContentInterface) {
            return $dimensionContent;
        }

        $behavior = $dimensionContent->getBehavior();

        // @phpstan-ignore-next-line
        return match ($behavior) {
            ContentBehaviorInterface::BEHAVIOR_INTERNAL => $this->resolveInternalBehavior($dimensionContent, $dimensionAttributes),
            ContentBehaviorInterface::BEHAVIOR_EXTERNAL => $this->resolveExternalBehavior($dimensionContent),
            default => $dimensionContent,
        };
    }

    /**
     * @param array<string, mixed> $dimensionAttributes
     */
    private function resolveInternalBehavior(PageDimensionContentInterface $pageDimensionContent, array $dimensionAttributes): PageDimensionContentInterface
    {
        $internalData = $pageDimensionContent->getBehaviorData($pageDimensionContent->getBehavior()) ?? [];

        $href = $internalData['href'] ?? null;
        if (null === $href || !\is_string($href)) {
            return $pageDimensionContent;
        }

        $page = $this->pageRepository->findOneBy(['uuid' => $href]);

        if (null === $page) {
            return $pageDimensionContent;
        }

        /** @var PageDimensionContentInterface $targetDimensionContent */
        $targetDimensionContent = $this->contentAggregator->aggregate($page, $dimensionAttributes);

        if ($internalData['title'] ?? null) {
            $targetDimensionContent->setTemplateData([
                ...$targetDimensionContent->getTemplateData(),
                ...[
                    'title' => $internalData['title'],
                ],
            ]);
        }

        return $targetDimensionContent;
    }

    private function resolveExternalBehavior(PageDimensionContentInterface $pageDimensionContent): PageDimensionContentInterface
    {
        $externalData = $pageDimensionContent->getBehaviorData($pageDimensionContent->getBehavior()) ?? [];

        $url = $externalData['href'] ?? null;
        $provider = $externalData['provider'] ?? null;
        if (null === $url || null === $provider || !\is_string($provider) || !\is_string($url)) {
            return $pageDimensionContent;
        }

        $linkProvider = $this->linkProviderPool->getProvider($provider);
        $preloadResult = $linkProvider->preload([$url], $pageDimensionContent->getLocale() ?? 'en');
        /** @var \Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem|null $linkItem */
        $linkItem = \is_array($preloadResult) ? ($preloadResult[0] ?? null) : null;

        if (null === $linkItem) {
            return $pageDimensionContent;
        }

        $pageDimensionContent->setTemplateData([
            ...$pageDimensionContent->getTemplateData(),
            ...[
                'title' => $linkItem->getTitle() ?: $pageDimensionContent->getTitle(),
                'url' => $linkItem->getUrl(),
            ],
        ]);

        return $pageDimensionContent;
    }
}
