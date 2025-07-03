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
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
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
         *     filters: SmartContentBaseFilters,
         *     sortBys: array<string, string>,
         *     parameters: array<string, mixed>,
         * } $data
         */
        $data = $resolvable->getData();

        $value = $data['value'];
        $filters = $data['filters'];
        $sortBys = $data['sortBys'];
        $parameters = $data['parameters'];

        /** @var int|null $limit */
        $limit = $filters['limit'] ?? null;
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
        $smartContentProvider = $this->smartContentProviders->get($provider);

        $params = ['value' => $value, ...$parameters];
        $result = $smartContentProvider->findFlatBy($filters, $sortBys, $params);
        $total = ($limit && \count($result) <= $limit) ? \count($result) : $smartContentProvider->countBy($filters, $params);

        $view = [
            'dataSource' => $filters['dataSource'],
            'includeSubFolders' => $filters['includeSubFolders'],
            'categories' => $filters['categories'],
            'categoryOperator' => $filters['categoryOperator'],
            'tags' => $value['tags'] ?? [],
            'tagOperator' => $filters['tagOperator'],
            'types' => $value['types'] ?? [],
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
            'hasNextPage' => null !== $limit && ($total > ($limit * $page)),
            'paginated' => null !== $limit,
            'total' => $total,
            'maxPage' => (null !== $limit) ? (int) \ceil($total / $limit) : null,
            'limit' => $limit,

            // TODO duplicates
            'excluded' => [],
        ];

        return ContentView::createResolvables(
            ids: \array_map(static fn (array $item) => $item['id'], $result),
            resourceLoaderKey: $smartContentProvider->getResourceLoaderKey(),
            view: $view,
        );
    }

    public static function getType(): string
    {
        return 'smart_content';
    }
}
