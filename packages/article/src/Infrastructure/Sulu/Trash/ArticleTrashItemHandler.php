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

namespace Sulu\Article\Infrastructure\Sulu\Trash;

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Domain\Event\ArticleRestoredEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRestoredEvent;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
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
        private DomainEventCollectorInterface $domainEventCollector,
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

        $restoreType = $options['locale'] ?? null ? 'translation' : null;

        $titles = [];
        /** @var array<string, ArticleDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = [];
        /** @var ArticleDimensionContentInterface|null $unlocalizedDimensionContent */
        $unlocalizedDimensionContent = null;
        foreach ($article->getDimensionContents() as $dimensionContent) {
            if (
                DimensionContentInterface::CURRENT_VERSION !== $dimensionContent->getVersion()
                || DimensionContentInterface::STAGE_DRAFT !== $dimensionContent->getStage()
            ) {
                continue;
            }

            if (null === $dimensionContent->getLocale()) {
                $unlocalizedDimensionContent = $dimensionContent;
                continue;
            }

            if ('translation' === $restoreType && $dimensionContent->getLocale() !== $options['locale']) {
                continue;
            }

            $localizedDimensionContents[$dimensionContent->getLocale()] = $dimensionContent;
        }

        Assert::notNull($unlocalizedDimensionContent, 'Expected to find an unlocalized dimension content for the article.');
        Assert::notEmpty($localizedDimensionContents, 'Expected to find at least one localized dimension content for the article.');

        // Reorder localized dimension contents to match the order defined in availableLocales.
        $availableLocales = $unlocalizedDimensionContent->getAvailableLocales();
        Assert::isArray($availableLocales, 'Expected availableLocales to be an array');
        /** @var array<string, ArticleDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = \array_merge(
            \array_flip(
                \array_filter(
                    $availableLocales, static fn ($locale) => \array_key_exists($locale, $localizedDimensionContents)
                )
            ),
            $localizedDimensionContents,
        );

        foreach ($localizedDimensionContents as $locale => $localizedDimensionContent) {
            $mergedDimensionContent = $this->contentMerger->merge(
                new DimensionContentCollection(
                    new ArrayCollection([$unlocalizedDimensionContent, $localizedDimensionContent]),
                    [
                        'locale' => $locale,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    ArticleDimensionContent::class,
                ),
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

        $article = $this->articleRepository->findOneBy(['uuid' => $articleUuid]);
        if (!$article) {
            $article = $this->articleRepository->createNew($articleUuid);
            $this->articleRepository->add($article);
        }

        $dimensionContents = $restoreData['dimensionContents'] ?? [];
        /** @var list<string> $allLocales */
        $allLocales = [];
        /** @var string|null $articleTitle */
        $articleTitle = null;
        Assert::isArray($dimensionContents, 'Expected dimensionContents to be an array');
        foreach ($dimensionContents as $dimensionContentData) {
            Assert::isArray($dimensionContentData, 'Expected dimensionContentData to be an array');
            /** @var array<string, mixed> $dimensionContentData */
            if (null === $articleTitle && \array_key_exists('title', $dimensionContentData) && $dimensionContentData['title']) {
                Assert::string($dimensionContentData['title']);
                $articleTitle = $dimensionContentData['title'];
            }

            if (\array_key_exists('locale', $dimensionContentData) && $dimensionContentData['locale']) {
                Assert::string($dimensionContentData['locale']);
                $allLocales[] = $dimensionContentData['locale'];
            }

            foreach ($this->articleMappers as $articleMapper) {
                $articleMapper->mapArticleData($article, $dimensionContentData);
            }
        }

        $context = $allLocales ? ['locales' => $allLocales] : [];

        if ('translation' === $trashItem->getRestoreType()) {
            foreach ($allLocales as $locale) {
                $this->domainEventCollector->collect(new ArticleTranslationRestoredEvent(
                    $article,
                    $locale,
                    $restoreData,
                ));
            }

            return $article;
        }

        $this->domainEventCollector->collect(new ArticleRestoredEvent(
            $article,
            $articleTitle,
            $context,
            $restoreData,
        ));

        return $article;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            ArticleAdmin::EDIT_TABS_VIEW,
            ['id' => 'id'],
            null, // TODO serialization group?
        );
    }
}
