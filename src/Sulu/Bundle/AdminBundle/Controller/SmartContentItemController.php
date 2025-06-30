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
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\SmartContent\Exception\DataProviderNotExistsException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides results for smart-content filters.
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
        $locale = $request->getLocale();

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
         *     webspaceKey?: string|null,
         *     page?: int,
         *     limitResult?: int|null,
         *     params?: string|null,
         *     provider?: string|null,
         * } $filters
         */
        $filters = $request->query->all();
        $params = $filters['params'] ?? '{}';
        unset($filters['params']);
        /** @var array<string, array{type?: string|null, value: mixed}> $decodedParams */
        $decodedParams = \json_decode($params, true) ?: [];
        $params = $this->getParams($decodedParams);
        $maxPerPage = ($params['max_per_page'] ?? null) ? $params['max_per_page']->getValue() : null;

        $filters['locale'] = $locale;
        $filters['excluded'] = \array_filter(\explode(',', $filters['excluded'] ?? ''));

        $filters['categoryIds'] = isset($filters['categories']) ? \array_filter(\explode(',', $filters['categories'])) : null;
        unset($filters['categories']);
        $filters['categoryOperator'] = isset($filters['categoryOperator']) ? \strtoupper($filters['categoryOperator']) : null;

        $filters['tagNames'] = isset($filters['tags']) ? \array_filter(\explode(',', $filters['tags'])) : null;
        unset($filters['tags']);
        $filters['tagOperator'] = isset($filters['tagOperator']) ? \strtoupper($filters['tagOperator']) : null;

        $filters['types'] = isset($filters['types']) ? \explode(',', $filters['types']) : null;
        $filters['includeSubFolders'] = isset($filters['includeSubFolders']) && 'true' === $filters['includeSubFolders'];
        $filters['page'] = (int) ($filters['page'] ?? 1);
        $filters['limit'] = ($filters['limitResult'] ?? $maxPerPage) ? (int) ($filters['limitResult'] ?? $maxPerPage) : null;
        $filters = \array_filter($filters);

        $sortBys = [];
        if ($filters['sortBy'] ?? null) {
            $sortBys[$filters['sortBy']] = $filters['sortMethod'] ?? 'asc';
            unset($filters['sortBy'], $filters['sortMethod']);
        }

        $providerType = (string) ($filters['provider'] ?? null);

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
