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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\Visitor\SmartContentFiltersVisitorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class SmartContentPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param iterable<SmartContentFiltersVisitorInterface> $smartContentFiltersVisitors
     */
    public function __construct(
        private RequestStack $requestStack,
        private iterable $smartContentFiltersVisitors,
    ) {
    }

    /**
     * @param array{
     *     categories?: int[],
     *     tags?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagOperator?: 'AND'|'OR',
     *     sortBy?: string,
     *     sortMethod?: 'ASC'|'DESC',
     *     limitResult?: int|null,
     *     dataSource?: string|null,
     *     types?: string[]|null,
     *     presentAs?: string|null,
     *     includeSubFolders?: bool|null,
     *     excludeDuplicates?: bool,
     *     audienceTargeting?: bool
     * } $data
     * @param array{
     *     resourceLoader?: string,
     *     properties?: array<string, mixed>|null,
     * } $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_array($data) || ([] !== $data && \array_is_list($data))) { // @phpstan-ignore-line
            return ContentView::create([], $params);
        }

        // Default parameters
        /**
         * @var array{
         *     locale: string|null,
         *     page_parameter: string,
         *     tags_parameter: string,
         *     types_parameter: string,
         *     categories_parameter: string,
         *     website_tags_operator: 'AND'|'OR',
         *     website_categories_operator: 'AND'|'OR',
         *     exclude_duplicates: bool|string,
         *     provider: string,
         *     max_per_page?: int,
         *     } $parameters
         */
        $parameters = \array_merge([
            'provider' => 'pages',
            'locale' => $locale,
            'page_parameter' => 'p',
            'tags_parameter' => 'tags',
            'types_parameter' => 'types',
            'categories_parameter' => 'categories',
            'website_tags_operator' => 'OR',
            'website_categories_operator' => 'OR',
            'exclude_duplicates' => false,
        ], $params['properties'] ?? []);
        $this->validateParameters($parameters);

        $request = $this->requestStack->getCurrentRequest();
        \assert(null !== $request, 'Request must not be null');

        /** @var SmartContentBaseFilters $filters */
        $filters = [
            // Categories
            'categories' => $data['categories'] ?? [],
            'categoryOperator' => \strtoupper($data['categoryOperator'] ?? 'OR'),
            'websiteCategories' => \array_filter(\explode(',', $request->query->getString($parameters['categories_parameter']))),
            'websiteCategoryOperator' => \strtoupper($parameters['website_categories_operator']),

            // Tags
            'tags' => $data['tags'] ?? [],
            'tagOperator' => \strtoupper($data['tagOperator'] ?? 'OR'),
            'websiteTags' => \array_filter(\explode(',', $request->query->getString($parameters['tags_parameter']))),
            'websiteTagOperator' => \strtoupper($parameters['website_tags_operator']),

            // Types
            'types' => $data['types'] ?? [],
            'typesOperator' => 'OR',

            // Other filters
            'locale' => $parameters['locale'],
            'dataSource' => $data['dataSource'] ?? null,
            'limit' => $data['limitResult'] ?? null,
            'page' => $request->query->getInt($parameters['page_parameter'], 1),
            'maxPerPage' => $parameters['max_per_page'] ?? null,
            'includeSubFolders' => $data['includeSubFolders'] ?? false,
            'excludeDuplicates' => 'true' === $parameters['exclude_duplicates'] || true === $parameters['exclude_duplicates'],
        ];
        $sortBys = $data['sortBy'] ?? null ? [$data['sortBy'] => $data['sortMethod'] ?? 'ASC'] : null;

        foreach ($this->smartContentFiltersVisitors as $visitor) {
            $filters = $visitor->visit($data, $filters, $parameters);
        }

        $result = [
            'value' => $data,
            'filters' => $filters,
            'sortBys' => $sortBys,
            'parameters' => $parameters,
        ];

        return ContentView::createSmartResolvable(
            data: $result,
            resourceLoaderKey: 'smart_content',
            view: [], // This will be filled in the SmartContentSmartResolver
        );
    }

    public static function getType(): string
    {
        return 'smart_content';
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function validateParameters(array $parameters): void
    {
        if (!isset($parameters['provider'])) {
            throw new \InvalidArgumentException('The "provider" parameter is required.');
        }

        if (!\is_string($parameters['provider'])) {
            throw new \InvalidArgumentException('The "provider" parameter must be a string.');
        }

        foreach (['website_tags_operator', 'website_categories_operator'] as $operator) {
            if ($parameters[$operator] ?? null) {
                /** @var string $operatorValue */
                $operatorValue = $parameters[$operator];
                $parameters[$operator] = \strtoupper($operatorValue);

                if (!\in_array($parameters[$operator], ['AND', 'OR'], true)) {
                    throw new \InvalidArgumentException(
                        \sprintf('The "%s" option must be either "AND" or "OR".', $operator),
                    );
                }
            }
        }
    }
}
