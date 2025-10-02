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
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_article_load', [$this, 'loadArticle']),
        ];
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>|null
     */
    public function loadArticle(
        string $uuid,
        ?string $locale = null,
        ?array $properties = null,
    ): ?array {
        if (null === $locale) {
            $localization = $this->requestAnalyzer->getCurrentLocalization();
            if (null === $localization) { // @phpstan-ignore identical.alwaysFalse
                return null;
            }
            $locale = $localization->getLocale();
        }

        $article = $this->articleRepository->findOneBy([
            'uuid' => $uuid,
        ]);

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
}
