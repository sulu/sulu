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

namespace Sulu\Bundle\AdminBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Exception\DataProviderNotExistsException;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class SmartContentItemController extends AbstractRestController
{
    /**
     * @param ServiceLocator<SmartContentProviderInterface> $smartContentProviderLocator
     */
    public function __construct(
        private ServiceLocator $smartContentProviderLocator,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null,
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    /**
     * Resolves filter for smart-content UI.
     *
     * @return Response
     *
     * @throws MissingParameterException
     * @throws DataProviderNotExistsException
     */
    public function getItemsAction(Request $request)
    {
        /** @var string $locale */
        $locale = $request->query->get('locale') ?? $request->getLocale();

        /** @var array{
         *     locale: string,
         *     excluded?: string,
         *     categories?: string|null,
         *     categoryIds?: int[],
         *     categoryOperator?: 'AND'|'OR',
         *     tags?: string|null,
         *     tagOperator?: 'AND'|'OR',
         *     types?: string,
         *     sortBy?: string|null,
         *     sortMethod?: 'asc'|'desc',
         *     includeSubFolders?: bool|string,
         *     webspace?: string|null,
         *     page?: int,
         *     limitResult?: int|null,
         *     params?: string|null,
         *     provider?: string|null,
         *     dataSource?: string|null,
         * } $filters
         */
        $filters = $request->query->all();
        $params = $filters['params'] ?? '{}';
        unset($filters['params']);
        /** @var array<string, array{type?: string|null, value: mixed}> $decodedParams */
        $decodedParams = \json_decode($params, true) ?: [];
        $params = $this->getParams($decodedParams);
        $providerType = (string) ($filters['provider'] ?? null);

        $sortBys = [];
        if ($filters['sortBy'] ?? null) {
            $sortBys[$filters['sortBy']] = $filters['sortMethod'] ?? 'asc';
            unset($filters['sortBy'], $filters['sortMethod']);
        }

        /** @var SmartContentBaseFilters $filters */
        $filters = [
            // Categories
            'categories' => isset($filters['categories']) ? \array_filter(\explode(',', $filters['categories'])) : [],
            'categoryOperator' => isset($filters['categoryOperator']) ? \strtoupper($filters['categoryOperator']) : null,
            'websiteCategories' => [],
            'websiteCategoryOperator' => 'OR',

            // Tags
            'tags' => isset($filters['tags']) ? \array_filter(\explode(',', $filters['tags'])) : [],
            'tagOperator' => isset($filters['tagOperator']) ? \strtoupper($filters['tagOperator']) : null,
            'websiteTags' => [],
            'websiteTagOperator' => 'OR',

            // Types
            'types' => isset($filters['types']) ? \explode(',', $filters['types']) : [],
            'typesOperator' => 'OR',

            // Other filters
            'locale' => $locale,
            'dataSource' => $filters['dataSource'] ?? null,
            'limit' => $filters['limitResult'] ?? null,
            'page' => (int) ($filters['page'] ?? 1),
            'maxPerPage' => ($params['max_per_page'] ?? null) ? $params['max_per_page']->getValue() : null,
            'includeSubFolders' => isset($filters['includeSubFolders']) && ('true' === $filters['includeSubFolders'] || true === $filters['includeSubFolders']),
            'excludeDuplicates' => isset($params['exclude_duplicates']) && ('true' === $params['exclude_duplicates']->getValue() || true === $params['exclude_duplicates']->getValue()),
        ];

        if (!$this->smartContentProviderLocator->has($providerType)) {
            throw new \RuntimeException(
                \sprintf(
                    'Smart content provider "%s" does not exist. Existing providers: %s',
                    $providerType,
                    \implode(', ', \array_keys($this->smartContentProviderLocator->getProvidedServices())),
                ),
            );
        }
        $provider = $this->smartContentProviderLocator->get($providerType);
        $items = $provider->findFlatBy($filters, $sortBys);

        return $this->handleView(
            $this->view(
                new CollectionRepresentation(
                    $items,
                    'items',
                    [
                        'total' => \count($items),
                    ],
                ),
            ),
        );
    }

    /**
     * @param array<string, array{
     *     type?: string|null,
     *     value: mixed,
     * }> $params
     *
     * @return PropertyParameter[]
     */
    private function getParams(array $params): array
    {
        $result = [];
        foreach ($params as $name => $item) {
            $type = $item['type'] ?? null;
            $value = $item['value'];
            if ('collection' === $type && \is_array($value)) {
                /** @var array<string, array{type?: string|null, value: mixed}> $typedCollectionValue */
                $typedCollectionValue = $value;
                $value = $this->getParams($typedCollectionValue);
            }

            /** @var mixed[]|bool|string $typedValue */
            $typedValue = $value;
            $result[$name] = new PropertyParameter($name, $typedValue, $type);
        }

        return $result;
    }
}
