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

namespace Sulu\Article\Infrastructure\Symfony\Twig;

use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ArticleTwigExtension extends AbstractExtension
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private ContentAggregatorInterface $contentAggregator,
        private RequestAnalyzerInterface $requestAnalyzer,
        private ReferenceStoreInterface $referenceStore,
        private ContentResolverInterface $contentResolver,
        private ?RequestStack $requestStack = null,
    ) {
        if (null === $requestStack) {
            @trigger_deprecation('sulu/sulu', '3.0.12', 'Instantiating ArticleTwigExtension without the $requestStack argument is deprecated.');
        }
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_article_load', [$this, 'loadArticle']),
            new TwigFunction('sulu_article_load_recent', [$this, 'loadRecentArticles']),
        ];
    }

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>|null
     */
    public function loadArticle(
        string $uuid,
        array $properties,
        ?string $locale = null,
    ): ?array {
        if (null === $locale) {
            $locale = $this->getCurrentLocale();
            if (null === $locale) {
                return null;
            }
        }

        return $this->resolveArticle($uuid, $properties, $locale);
    }

    /**
     * Loads the most recently authored articles, without the article of the current request.
     *
     * @param array<string, string> $properties
     * @param string[]|null $templateKeys
     *
     * @return array<array<string, mixed>>
     */
    public function loadRecentArticles(
        int $limit,
        array $properties,
        ?array $templateKeys = null,
        ?string $locale = null,
        bool $ignoreWebspaces = false,
    ): array {
        $locale ??= $this->getCurrentLocale();
        if (null === $locale || $limit < 1) {
            return [];
        }

        $filters = [
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
            'limit' => $limit + 1, // one more, in case the current article is among them
        ];

        if ($templateKeys) {
            $filters['templateKeys'] = $templateKeys;
        }

        $webspaceKey = $ignoreWebspaces ? null : $this->requestAnalyzer->getWebspace()?->getKey();
        if (null !== $webspaceKey) {
            $filters['webspaceKey'] = $webspaceKey;
        }

        $currentUuid = $this->getCurrentArticleUuid();

        $articles = [];
        foreach ($this->articleRepository->findIdentifiersBy($filters, ['authored' => 'desc']) as $uuid) {
            if ($uuid === $currentUuid) {
                continue;
            }

            $article = $this->resolveArticle($uuid, $properties, $locale);
            if (null !== $article) {
                $articles[] = $article;
            }

            if (\count($articles) >= $limit) {
                break;
            }
        }

        return $articles;
    }

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed>|null
     */
    private function resolveArticle(string $uuid, array $properties, string $locale): ?array
    {
        $article = $this->articleRepository->findOneBy(
            [
                'uuid' => $uuid,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ]
        );

        if (null === $article) {
            return null;
        }

        /** @var ArticleDimensionContentInterface $dimensionContent */
        $dimensionContent = $this->contentAggregator->aggregate(
            $article,
            [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ]
        );

        $resolvedContent = $this->contentResolver->resolve($dimensionContent, $properties);

        $this->referenceStore->add($article->getUuid(), ArticleInterface::RESOURCE_KEY);

        return $resolvedContent;
    }

    private function getCurrentLocale(): ?string
    {
        return $this->requestAnalyzer->getCurrentLocalization()?->getLocale(); // @phpstan-ignore nullsafe.neverNull
    }

    private function getCurrentArticleUuid(): ?string
    {
        $object = $this->requestStack?->getCurrentRequest()?->attributes->get('object');

        return $object instanceof ArticleDimensionContentInterface ? $object->getResource()->getUuid() : null;
    }
}
