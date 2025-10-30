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

namespace Sulu\Snippet\Infrastructure\Sulu\Trash;

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
use Sulu\Snippet\Application\Mapper\SnippetMapperInterface;
use Sulu\Snippet\Domain\Event\SnippetRestoredEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationRestoredEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class SnippetTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    /**
     * @param iterable<SnippetMapperInterface> $snippetMappers
     */
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private SnippetRepositoryInterface $snippetRepository,
        private ContentNormalizerInterface $contentNormalizer,
        private ContentMergerInterface $contentMerger,
        private iterable $snippetMappers,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public static function getResourceKey(): string
    {
        return SnippetInterface::RESOURCE_KEY;
    }

    public function store(object $resource, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($resource, SnippetInterface::class);

        $snippet = $resource;

        $data = [
            'dimensionContents' => [],
        ];

        $restoreType = $options['locales'] ?? null ? 'translation' : null;

        $titles = [];
        /** @var array<string, SnippetDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = [];
        /** @var SnippetDimensionContentInterface|null $unlocalizedDimensionContent */
        $unlocalizedDimensionContent = null;
        foreach ($snippet->getDimensionContents() as $dimensionContent) {
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

        Assert::notNull($unlocalizedDimensionContent, 'Expected to find an unlocalized dimension content for the snippet.');
        Assert::notEmpty($localizedDimensionContents, 'Expected to find at least one localized dimension content for the snippet.');

        // sort dimensionContents after the availableLocales from the unlocalizedDimension
        $availableLocales = $unlocalizedDimensionContent->getAvailableLocales();
        Assert::isArray($availableLocales, 'Expected availableLocales to be an array');
        /** @var array<string, SnippetDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = \array_merge(
            \array_flip($availableLocales),
            $localizedDimensionContents
        );

        foreach ($localizedDimensionContents as $locale => $localizedDimensionContent) {
            /** @var array<int, SnippetDimensionContent> $dimensionContents */
            $dimensionContents = [$unlocalizedDimensionContent, $localizedDimensionContent];

            $mergedDimensionContent = $this->contentMerger->merge(
                new DimensionContentCollection(
                    $dimensionContents,
                    [
                        'locale' => $locale,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    SnippetDimensionContent::class
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
            SnippetInterface::RESOURCE_KEY,
            $snippet->getUuid(),
            $titles,
            $data,
            $restoreType,
            $options,
            SnippetAdmin::SECURITY_CONTEXT,
            null, // TODO add Security
            $snippet->getUuid(),
        );
    }

    /**
     * @param array{} $restoreFormData
     */
    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $restoreData = $trashItem->getRestoreData();
        $snippetUuid = $trashItem->getResourceId();
        $restoreTranslation = true;

        $snippet = $this->snippetRepository->findOneBy(['uuid' => $snippetUuid]);

        if (!$snippet) {
            $snippet = $this->snippetRepository->createNew($snippetUuid);
            $restoreTranslation = false;
        }

        $this->snippetRepository->add($snippet);

        $dimensionContents = $restoreData['dimensionContents'] ?? [];
        $allLocales = [];
        $snippetTitle = null;
        Assert::isArray($dimensionContents, 'Expected dimensionContents to be an array');
        /** @var array<string, mixed> $dimensionContentData */
        foreach ($dimensionContents as $dimensionContentData) {
            unset($dimensionContentData['url']); // TODO old route is not removed on delete?

            if (!$snippetTitle && \array_key_exists('title', $dimensionContentData) && $dimensionContentData['title']) {
                /** @var string $snippetTitle */
                $snippetTitle = $dimensionContentData['title'];
            }

            if (\array_key_exists('locale', $dimensionContentData) && $dimensionContentData['locale']) {
                $allLocales[] = $dimensionContentData['locale'];
            }

            foreach ($this->snippetMappers as $snippetMapper) {
                $snippetMapper->mapSnippetData($snippet, $dimensionContentData);
            }
        }

        /** @var array{locales?: string[]} $context */
        $context = $allLocales ? ['locales' => $allLocales] : [];

        if ($restoreTranslation) {
            /** @var string $locale */
            foreach ($allLocales as $locale) {
                $this->domainEventCollector->collect(new SnippetTranslationRestoredEvent(
                    $snippet,
                    $locale,
                    $restoreData,
                ));
            }
        } else {
            $this->domainEventCollector->collect(new SnippetRestoredEvent(
                $snippet,
                $snippetTitle,
                $context,
                $restoreData,
            ));
        }

        return $snippet;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            SnippetAdmin::EDIT_TABS_VIEW,
            ['id' => 'id'],
            null, // TODO serialization group?
        );
    }
}
