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
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @phpstan-type SmartContentFilters array{
 *       categories: int[],
 *       categoryOperator: 'AND'|'OR',
 *       websiteCategories: string[],
 *       websiteCategoryOperator: 'AND'|'OR',
 *       tags: string[],
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
    ) {
    }

    public function resolve(SmartResolvable $resolvable, ?string $locale = null): ContentView
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

        $countByFilters = $filters;
        unset($countByFilters['offset']);

        $params = ['value' => $value, ...$parameters];
        $result = $smartContentProvider->findFlatBy($filters, $sortBys, $params);

        $fullTotal = $smartContentProvider->countBy($countByFilters, $params);
        $total = $limit ? \min($limit, $fullTotal) : $fullTotal;

        $view = [
            'dataSource' => $filters['dataSource'],
            'includeSubFolders' => $filters['includeSubFolders'],
            'categories' => $filters['categories'],
            'categoryOperator' => $filters['categoryOperator'],
            'tags' => $value['tags'] ?? [],
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

            // TODO duplicates
            'excluded' => [],
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
}
