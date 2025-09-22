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

namespace Sulu\Article\Trash;

use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class ArticleTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    /**
     * @param iterable<ArticleMapperInterface> $articleMappers
     */
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private ArticleRepositoryInterface $articleRepository,
        private ContentNormalizerInterface $contentNormalizer,
        private ContentMergerInterface $contentMerger,
        private iterable $articleMappers,
    ) {
    }

    public static function getResourceKey(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }

    public function store(object $resource, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($resource, ArticleInterface::class);

        $article = $resource;

        $data = [
            'dimensionContents' => [],
        ];

        $restoreType = $options['locales'] ?? null ? 'translation' : null;

        $titles = [];
        /** @var array<string, ArticleDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = [];
        /** @var ArticleDimensionContentInterface|null $unlocalizedDimensionContent */
        $unlocalizedDimensionContent = null;
        foreach ($article->getDimensionContents() as $dimensionContent) {
            if (
                DimensionContentInterface::CURRENT_VERSION !== $dimensionContent->getVersion()
                && DimensionContentInterface::STAGE_LIVE !== $dimensionContent->getStage()
            ) {
                continue;
            }

            if (null === $dimensionContent->getLocale()) {
                $unlocalizedDimensionContent = $dimensionContent;
                continue;
            }

            $localizedDimensionContents[$dimensionContent->getLocale()] = $dimensionContent;
        }

        Assert::notNull($unlocalizedDimensionContent, 'Expected to find an unlocalized dimension content for the article.');
        Assert::notEmpty($localizedDimensionContents, 'Expected to find at least one localized dimension content for the article.');

        // sort dimensionContents after the availableLocales from the unlocalizedDimension
        $availableLocales = $unlocalizedDimensionContent->getAvailableLocales();
        Assert::isArray($availableLocales, 'Expected availableLocales to be an array');
        /** @var array<string, ArticleDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = \array_merge(
            \array_flip($availableLocales),
            $localizedDimensionContents
        );

        foreach ($localizedDimensionContents as $locale => $localizedDimensionContent) {
            /** @var array<int, ArticleDimensionContent> $dimensionContents */
            $dimensionContents = [$unlocalizedDimensionContent, $localizedDimensionContent];

            $mergedDimensionContent = $this->contentMerger->merge(
                new DimensionContentCollection(
                    $dimensionContents,
                    [
                        'locale' => $locale,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    ArticleDimensionContent::class
                )
            );

            $normalizedContent = $this->contentNormalizer->normalize($mergedDimensionContent);
            $data['dimensionContents'][] = $normalizedContent;

            $title = $localizedDimensionContent->getTitle();

            if ($title) {
                $titles[$locale] = $title;
            }
        }

        return $this->trashItemRepository->create(
            ArticleInterface::RESOURCE_KEY,
            $article->getUuid(),
            $titles,
            $data,
            $restoreType,
            $options,
            ArticleAdmin::SECURITY_CONTEXT,
            null, // TODO add Security
            $article->getUuid(),
        );
    }

    /**
     * @param array{} $restoreFormData
     */
    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $restoreData = $trashItem->getRestoreData();
        $articleUuid = $trashItem->getResourceId();

        $article = $this->articleRepository->createNew($articleUuid);
        $this->articleRepository->add($article);

        $dimensionContents = $restoreData['dimensionContents'] ?? [];
        Assert::isArray($dimensionContents, 'Expected dimensionContents to be an array');
        /** @var array<string, mixed> $dimensionContentData */
        foreach ($dimensionContents as $dimensionContentData) {
            unset($dimensionContentData['url']); // TODO old route is not removed on delete?
            foreach ($this->articleMappers as $articleMapper) {
                $articleMapper->mapArticleData($article, $dimensionContentData);
            }
        }

        return $article;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            'restore_article',
            ArticleAdmin::EDIT_TABS_VIEW,
            ['id' => 'id'],
            null, // TODO serialization group?
        );
    }
}
