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

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Bundle\AdminBundle\Teaser\TeaserTagPropertyExtractor;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageTeaserProvider implements TeaserProviderInterface
{
    public function __construct(
        protected PageRepositoryInterface $pageRepository,
        protected ContentAggregatorInterface $contentAggregator,
        protected ContentEnhancerInterface $contentEnhancer,
        protected TranslatorInterface $translator,
        protected TeaserTagPropertyExtractor $teaserTagPropertyExtractor,
    ) {
    }

    public function getConfiguration(): TeaserConfiguration
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_page.page', [], 'admin'),
            PageInterface::RESOURCE_KEY,
            'column_list',
            ['title'],
            $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'),
        );
    }

    /**
     * @param array<string> $ids
     *
     * @return Teaser[]
     */
    public function find(array $ids, $locale): array
    {
        if (0 === \count($ids)) {
            return [];
        }

        $pages = $this->findPagesByUuids($ids, $locale);

        $teasers = [];
        foreach ($pages as $page) {
            $teaser = $this->createTeaserFromPage($page, $locale);
            if (null !== $teaser) {
                $teasers[] = $teaser;
            }
        }

        return $teasers;
    }

    /**
     * @param array<string> $uuids
     *
     * @return array<PageInterface>
     */
    private function findPagesByUuids(array $uuids, string $locale): array
    {
        /** @var array<PageInterface> $pages */
        $pages = \iterator_to_array($this->pageRepository->findBy(
            filters: [
                'uuids' => $uuids,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            selects: [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ]
        ));

        // Sort by original order
        $uuidPositions = \array_flip($uuids);
        \usort(
            $pages,
            static fn (PageInterface $a, PageInterface $b) => ($uuidPositions[$a->getUuid()] ?? 0) - ($uuidPositions[$b->getUuid()] ?? 0)
        );

        return $pages;
    }

    private function createTeaserFromPage(PageInterface $page, string $locale): ?Teaser
    {
        $dimensionContent = $this->resolveDimensionContent($page, $locale);
        if (null === $dimensionContent) {
            return null;
        }

        /** @var PageDimensionContentInterface $dimensionContent */
        $dimensionContent = $this->contentEnhancer->enhance($dimensionContent);

        $url = $this->resolveUrl($dimensionContent);
        if (null === $url) {
            return null;
        }

        return new Teaser(
            $page->getUuid(),
            PageInterface::RESOURCE_KEY,
            $locale,
            $this->resolveTitle($dimensionContent) ?? '',
            $this->resolveDescription($dimensionContent) ?? '',
            $this->resolveMoreText($dimensionContent) ?? '',
            $url,
            $this->resolveMediaId($dimensionContent),
            $this->getAttributes($dimensionContent),
        );
    }

    protected function resolveDimensionContent(PageInterface $page, string $locale): ?PageDimensionContentInterface
    {
        try {
            /** @var PageDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($page, [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);
        } catch (ContentNotFoundException) {
            return null;
        }

        return $dimensionContent;
    }

    protected function resolveUrl(PageDimensionContentInterface $dimensionContent): ?string
    {
        $linkData = $dimensionContent->getLinkData();
        if ('external' === ($linkData['provider'] ?? null) && \is_string($linkData['href'] ?? null)) {
            return $linkData['href'];
        }

        $route = $dimensionContent->getRoute();

        return $route?->getSlug();
    }

    protected function resolveTitle(PageDimensionContentInterface $dimensionContent): ?string
    {
        $title = $dimensionContent->getExcerptTitle() ?? $dimensionContent->getTitle();

        return '' !== ($title ?? '') ? $title : null;
    }

    protected function resolveDescription(PageDimensionContentInterface $dimensionContent): ?string
    {
        $description = $dimensionContent->getExcerptDescription();
        if (null !== $description && '' !== $description) {
            return \strip_tags($description);
        }

        // Fallback to tagged property
        $templateKey = $dimensionContent->getTemplateKey();
        $locale = $dimensionContent->getLocale();
        if (null === $templateKey || null === $locale) {
            return null;
        }

        $description = $this->teaserTagPropertyExtractor->extractDescription(
            PageInterface::TEMPLATE_TYPE,
            $templateKey,
            $locale,
            $dimensionContent->getTemplateData()
        );

        return null !== $description ? \strip_tags($description) : null;
    }

    protected function resolveMoreText(PageDimensionContentInterface $dimensionContent): ?string
    {
        $moreText = $dimensionContent->getExcerptMore();

        return '' !== ($moreText ?? '') ? $moreText : null;
    }

    protected function resolveMediaId(PageDimensionContentInterface $dimensionContent): ?int
    {
        $mediaId = $dimensionContent->getExcerptImage()['id'] ?? null;
        if (null !== $mediaId) {
            return $mediaId;
        }

        // Fallback to tagged property
        $templateKey = $dimensionContent->getTemplateKey();
        $locale = $dimensionContent->getLocale();
        if (null === $templateKey || null === $locale) {
            return null;
        }

        return $this->teaserTagPropertyExtractor->extractMediaId(
            PageInterface::TEMPLATE_TYPE,
            $templateKey,
            $locale,
            $dimensionContent->getTemplateData()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(PageDimensionContentInterface $dimensionContent): array
    {
        return [];
    }
}
