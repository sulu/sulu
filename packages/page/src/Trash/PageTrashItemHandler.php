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

namespace Sulu\Page\Trash;

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
use Sulu\Page\Application\Mapper\PageMapperInterface;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class PageTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    /**
     * @param iterable<PageMapperInterface> $pageMappers
     */
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private PageRepositoryInterface $pageRepository,
        private ContentNormalizerInterface $contentNormalizer,
        private ContentMergerInterface $contentMerger,
        private iterable $pageMappers,
    ) {
    }

    public static function getResourceKey(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function store(object $resource, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($resource, PageInterface::class);

        $page = $resource;

        $data = [
            'parentUuid' => $page->getParent()?->getUuid(),
            'webspaceKey' => $page->getWebspaceKey(),
            'dimensionContents' => [],
        ];

        $restoreType = $options['locales'] ?? null ? 'translation' : null;

        $titles = [];
        /** @var array<string, PageDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = [];
        /** @var PageDimensionContentInterface|null $unlocalizedDimensionContent */
        $unlocalizedDimensionContent = null;
        foreach ($page->getDimensionContents() as $dimensionContent) {
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

        Assert::notNull($unlocalizedDimensionContent, 'Expected to find an unlocalized dimension content for the page.');
        Assert::notEmpty($localizedDimensionContents, 'Expected to find at least one localized dimension content for the page.');

        // sort dimensionContents after the availableLocales from the unlocalizedDimension
        $availableLocales = $unlocalizedDimensionContent->getAvailableLocales();
        Assert::isArray($availableLocales, 'Expected availableLocales to be an array');
        /** @var array<string, PageDimensionContentInterface> $localizedDimensionContents */
        $localizedDimensionContents = \array_merge(
            \array_flip($availableLocales),
            $localizedDimensionContents
        );

        foreach ($localizedDimensionContents as $locale => $localizedDimensionContent) {
            /** @var array<int, PageDimensionContent> $dimensionContents */
            $dimensionContents = [$unlocalizedDimensionContent, $localizedDimensionContent];

            $mergedDimensionContent = $this->contentMerger->merge(
                new DimensionContentCollection(
                    $dimensionContents,
                    [
                        'locale' => $locale,
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                        'version' => DimensionContentInterface::CURRENT_VERSION,
                    ],
                    PageDimensionContent::class
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
            PageInterface::RESOURCE_KEY,
            $page->getUuid(),
            $titles,
            $data,
            $restoreType,
            $options,
            PageAdmin::getPageSecurityContext($page->getWebspaceKey()),
            null, // TODO add Security
            $page->getUuid(),
        );
    }

    /**
     * @param array{
     *     parentId?: string,
     * } $restoreFormData
     */
    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $restoreData = $trashItem->getRestoreData();
        $pageUuid = $trashItem->getResourceId();

        // Create the page
        $page = $this->pageRepository->createNew($pageUuid);
        $webspaceKey = $restoreData['webspaceKey'];
        Assert::string($webspaceKey, 'Expected webspaceKey to be a string');
        $page->setWebspaceKey($webspaceKey);

        // Set parent if exists
        $parentUuid = $restoreFormData['parentId'] ?? $restoreData['parentUuid'];
        if ($parentUuid) {
            Assert::string($parentUuid, 'Expected parentUuid to be a string');
            $parent = $this->pageRepository->findOneBy(['uuid' => $parentUuid]);
            if ($parent) {
                $page->setParent($parent);
            }
        }

        $this->pageRepository->add($page);

        $dimensionContents = $restoreData['dimensionContents'] ?? [];
        Assert::isArray($dimensionContents, 'Expected dimensionContents to be an array');
        foreach ($dimensionContents as $dimensionContentData) {
            Assert::isArray($dimensionContentData, 'Expected dimensionContentData to be an array');
            unset($dimensionContentData['url']); // TODO old route is not removed on delete?
            foreach ($this->pageMappers as $pageMapper) {
                $pageMapper->mapPageData($page, $dimensionContentData);
            }
        }

        return $page;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            'restore_page',
            PageAdmin::EDIT_FORM_VIEW,
            ['id' => 'id', 'webspace' => 'webspace'],
            null, // TODO serialization group?
        );
    }
}
