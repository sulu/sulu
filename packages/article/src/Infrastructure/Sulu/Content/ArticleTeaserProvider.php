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

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Bundle\AdminBundle\Teaser\TeaserTagPropertyExtractor;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleTeaserProvider implements TeaserProviderInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
        protected ContentAggregatorInterface $contentAggregator,
        protected ContentEnhancerInterface $contentEnhancer,
        protected TranslatorInterface $translator,
        protected TeaserTagPropertyExtractor $teaserTagPropertyExtractor,
    ) {
    }

    public function getConfiguration(): TeaserConfiguration
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_article.article', [], 'admin'),
            ArticleInterface::RESOURCE_KEY,
            'table',
            ['title'],
            $this->translator->trans('sulu_article.single_selection_overlay_title', [], 'admin'),
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

        $articles = $this->findArticlesByUuids($ids, $locale);

        $teasers = [];
        foreach ($articles as $article) {
            $teaser = $this->createTeaserFromArticle($article, $locale);
            if (null !== $teaser) {
                $teasers[] = $teaser;
            }
        }

        return $teasers;
    }

    /**
     * @param array<string> $uuids
     *
     * @return array<ArticleInterface>
     */
    private function findArticlesByUuids(array $uuids, string $locale): array
    {
        /** @var array<ArticleInterface> $articles */
        $articles = \iterator_to_array($this->articleRepository->findBy(
            filters: [
                'uuids' => $uuids,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            selects: [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ]
        ));

        // Sort by original order
        $uuidPositions = \array_flip($uuids);
        \usort(
            $articles,
            static fn (ArticleInterface $a, ArticleInterface $b) => ($uuidPositions[$a->getUuid()] ?? 0) - ($uuidPositions[$b->getUuid()] ?? 0)
        );

        return $articles;
    }

    private function createTeaserFromArticle(ArticleInterface $article, string $locale): ?Teaser
    {
        $dimensionContent = $this->resolveDimensionContent($article, $locale);
        if (null === $dimensionContent) {
            return null;
        }

        /** @var ArticleDimensionContentInterface $dimensionContent */
        $dimensionContent = $this->contentEnhancer->enhance($dimensionContent);

        $url = $this->resolveUrl($dimensionContent);
        if (null === $url) {
            return null;
        }

        return new Teaser(
            $article->getUuid(),
            ArticleInterface::RESOURCE_KEY,
            $locale,
            $this->resolveTitle($dimensionContent) ?? '',
            $this->resolveDescription($dimensionContent) ?? '',
            $this->resolveMoreText($dimensionContent) ?? '',
            $url,
            $this->resolveMediaId($dimensionContent),
            $this->getAttributes($dimensionContent),
        );
    }

    protected function resolveDimensionContent(ArticleInterface $article, string $locale): ?ArticleDimensionContentInterface
    {
        try {
            /** @var ArticleDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($article, [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);
        } catch (ContentNotFoundException) {
            return null;
        }

        return $dimensionContent;
    }

    protected function resolveUrl(ArticleDimensionContentInterface $dimensionContent): ?string
    {
        $route = $dimensionContent->getRoute();

        return $route?->getSlug();
    }

    protected function resolveTitle(ArticleDimensionContentInterface $dimensionContent): ?string
    {
        $excerptTitle = $dimensionContent->getExcerptTitle();
        $title = '' !== ($excerptTitle ?? '') ? $excerptTitle : $dimensionContent->getTitle();

        return '' !== ($title ?? '') ? $title : null;
    }

    protected function resolveDescription(ArticleDimensionContentInterface $dimensionContent): ?string
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
            ArticleInterface::TEMPLATE_TYPE,
            $templateKey,
            $locale,
            $dimensionContent->getTemplateData()
        );

        return null !== $description ? \strip_tags($description) : null;
    }

    protected function resolveMoreText(ArticleDimensionContentInterface $dimensionContent): ?string
    {
        $moreText = $dimensionContent->getExcerptMore();

        return '' !== ($moreText ?? '') ? $moreText : null;
    }

    protected function resolveMediaId(ArticleDimensionContentInterface $dimensionContent): ?int
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
            ArticleInterface::TEMPLATE_TYPE,
            $templateKey,
            $locale,
            $dimensionContent->getTemplateData()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(ArticleDimensionContentInterface $dimensionContent): array
    {
        return [
            'uuid' => $dimensionContent->getResourceId(),
            'webspace' => $dimensionContent->getMainWebspace(),
            'additionalWebspaces' => $dimensionContent->getAdditionalWebspaces(),
        ];
    }
}
