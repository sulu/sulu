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

namespace Sulu\Content\Application\SmartResolver\Resolver;

use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ContentResolver\ContentDeduplicationTracker;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\ResourceLoader\Loader\RawResourceLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @phpstan-type SmartContentFilters array{
 *       categories: int[],
 *       categoryOperator: 'AND'|'OR',
 *       websiteCategories: string[],
 *       websiteCategoryOperator: 'AND'|'OR',
 *       tags: int[],
 *       tagOperator: 'AND'|'OR',
 *       websiteTags: string[],
 *       websiteTagOperator: 'AND'|'OR',
 *       types: string[],
 *       typesOperator: 'OR',
 *       templateKeys?: string[],
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
 *       page: int,
 *       maxPerPage: int|null,
 *       includeSubFolders: bool,
 *       excludeDuplicates: bool,
 *       excluded?: string[],
 *       audienceTargeting?: bool,
 *       targetGroupId?: string,
 *       offset?: int,
 *   }
 */
class SmartContentSmartResolver implements SmartResolverInterface
{
    /**
     * @param ServiceLocator<SmartContentProviderInterface> $smartContentProviders
     */
    public function __construct(
        private ServiceLocator $smartContentProviders,
        private ContentDeduplicationTracker $deduplicationTracker,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function resolve(SmartResolvable $resolvable, ?string $locale = null, array $context = []): ContentView
    {
        /** @var array{
         *     value: array<string, mixed>,
         *     filters: SmartContentFilters,
         *     sortBys: array<string, string>|null,
         *     parameters: array<string, mixed>,
         * } $data
         */
        $data = $resolvable->getData();

        $value = $data['value'];
        $filters = $data['filters'];
        $sortBys = $data['sortBys'] ?? [];
        $parameters = $data['parameters'];

        /** @var int|null $limit Total max items across all pages (null = no limit) */
        $limit = $filters['limit'] ?? null;
        /** @var int|null $maxPerPage Items per page for pagination (null = no pagination) */
        $maxPerPage = $filters['maxPerPage'] ?? null;
        /** @var int $page */
        $page = $filters['page'];

        $provider = $parameters['provider'] ?? null;

        if (!\is_string($provider)) {
            throw new \InvalidArgumentException(\sprintf('The "provider" must be a string, %s given.', \gettype($provider)));
        }

        if (!$this->smartContentProviders->has($provider)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'No smart content provider found for key "%s". Existing keys: %s',
                    $provider,
                    \implode(', ', \array_keys($this->smartContentProviders->getProvidedServices())),
                ),
            );
        }

        if (null !== $maxPerPage && $maxPerPage <= 0) {
            throw new \InvalidArgumentException('The "maxPerPage" parameter must be a positive integer.');
        }

        $smartContentProvider = $this->smartContentProviders->get($provider);

        // Pagination filters
        unset($filters['maxPerPage']);
        unset($filters['page']);

        $filters['offset'] = $maxPerPage ? (($page - 1) * $maxPerPage) : 0;
        $filters['limit'] = $maxPerPage ?? $limit;

        // Smart content deduplication is keyed by the provider's resource key, which is shared with the
        // regular selections and with the currently rendered resource.
        $resourceKey = $smartContentProvider->getType();

        // Preserve any explicit exclusions already present in the filters.
        /** @var string[] $excluded */
        $excluded = $filters['excluded'] ?? [];

        // Exclude the currently rendered resource (the "own" page) from its own results.
        $selfReference = $context['selfReference'] ?? null;
        if (\is_array($selfReference) && ($selfReference['resourceKey'] ?? null) === $resourceKey) {
            $excluded[] = (string) $selfReference['id'];
        }

        // When "exclude_duplicates" is enabled, also exclude the resources already referenced by earlier
        // selections or smart content blocks of the same resource key.
        if ($filters['excludeDuplicates']) {
            $excluded = [...$excluded, ...$this->deduplicationTracker->getAll($resourceKey)];
        }

        $excluded = \array_values(\array_unique($excluded));
        $filters['excluded'] = $excluded;

        $countByFilters = $filters;
        unset($countByFilters['offset']);

        $params = ['value' => $value, ...$parameters];
        $result = $smartContentProvider->findFlatBy($filters, $sortBys, $params);

        // Always register the resolved ids so that following blocks can deduplicate against them, even
        // when this block has "exclude_duplicates" disabled.
        foreach ($result as $item) {
            $this->deduplicationTracker->add($resourceKey, $item['id']);
        }

        $fullTotal = $smartContentProvider->countBy($countByFilters, $params);
        $total = $limit ? \min($limit, $fullTotal) : $fullTotal;

        /** @var array<int|string> $valueTags */
        $valueTags = $value['tags'] ?? [];

        $view = [
            'dataSource' => $filters['dataSource'],
            'includeSubFolders' => $filters['includeSubFolders'],
            'categories' => $this->buildCategoryResolvables($filters['categories'], $parameters['categoryResourceLoader'] ?? null),
            'categoryOperator' => $filters['categoryOperator'],
            'tags' => $this->buildTagResolvables($valueTags, $parameters['tagResourceLoader'] ?? null),
            'tagOperator' => $filters['tagOperator'],
            'types' => $filters['types'],
            'templateKeys' => $filters['templateKeys'] ?? [],
            'typesOperator' => $filters['typesOperator'],
            'websiteCategories' => $filters['websiteCategories'],
            'websiteCategoryOperator' => $filters['websiteCategoryOperator'],
            'categoryRoot' => $parameters['categoryRoot'] ?? null,
            'websiteTags' => $filters['websiteTags'],
            'websiteTagOperator' => $filters['websiteTagOperator'],
            'sortBys' => $sortBys,
            'presentAs' => $value['presentAs'] ?? null,
            'limitResult' => $filters['limit'],

            'page' => $page,
            'hasNextPage' => null !== $maxPerPage && ($total > ($maxPerPage * $page)),
            'paginated' => null !== $maxPerPage,
            'total' => $total,
            'maxPage' => null !== $maxPerPage ? (int) \ceil($total / $maxPerPage) : null,
            'limit' => $limit,
            'maxPerPage' => $maxPerPage,

            'excluded' => $excluded,
        ];

        $configuration = $smartContentProvider->getConfiguration();
        $defaultProperties = $configuration->getDefaultProperties();
        /** @var array<string, string> $passedProperties */
        $passedProperties = $params['properties'] ?? [];
        $properties = null !== $defaultProperties
            ? \array_replace($defaultProperties, $passedProperties)
            : ($passedProperties ?: null);

        return ContentView::createResolvables(
            ids: \array_map(static fn (array $item) => $item['id'], $result),
            resourceLoaderKey: $smartContentProvider->getResourceLoaderKey(),
            view: $view,
            metadata: [
                'properties' => $properties,
            ]
        );
    }

    public static function getType(): string
    {
        return 'smart_content';
    }

    /**
     * @param array<int|string> $tagIds
     *
     * @return ResolvableResource[]
     */
    private function buildTagResolvables(array $tagIds, mixed $resourceLoaderKey): array
    {
        $resourceLoaderKey = \is_string($resourceLoaderKey) ? $resourceLoaderKey : RawResourceLoader::RESOURCE_LOADER_KEY;

        return \array_map(
            static fn (int|string $id): ResolvableResource => new ResolvableResource(
                id: $id,
                resourceLoaderKey: $resourceLoaderKey,
                priority: 0,
                resourceKey: TagInterface::RESOURCE_KEY,
            ),
            $tagIds,
        );
    }

    /**
     * @param int[] $categoryIds
     *
     * @return ResolvableResource[]
     */
    private function buildCategoryResolvables(array $categoryIds, mixed $resourceLoaderKey): array
    {
        $resourceLoaderKey = \is_string($resourceLoaderKey) ? $resourceLoaderKey : RawResourceLoader::RESOURCE_LOADER_KEY;

        return \array_map(
            static fn (int $id): ResolvableResource => new ResolvableResource(
                id: $id,
                resourceLoaderKey: $resourceLoaderKey,
                priority: 0,
                resourceKey: CategoryInterface::RESOURCE_KEY,
            ),
            $categoryIds,
        );
    }
}
