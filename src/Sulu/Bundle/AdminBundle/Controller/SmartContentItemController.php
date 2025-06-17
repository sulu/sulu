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
use Sulu\Component\Rest\RequestParametersTrait;
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
    use RequestParametersTrait;

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
     * @throws MissingParameterException
     * @throws DataProviderNotExistsException
     *
     * @return Response
     */
    public function getItemsAction(Request $request)
    {
        $locale = $this->getLocale($request);

        /** @var array{
         *     locale: string,
         *     excluded?: string[],
         *     categories?: string|null,
         *     categoryIds?: int[],
         *     categoryOperator?: 'AND'|'OR',
         *     tags?: string|null,
         *     tagIds?: int[],
         *     tagOperator?: 'AND'|'OR',
         *     types?: string[],
         *     sortBy?: string|null,
         *     sortMethod?: 'asc'|'desc',
         *     includeSubFolders?: bool,
         *     webspaceKey?: string|null,
         *     page?: int,
         *     limitResult?: int|null,
         * } $filters
         */
        $filters = $request->query->all();
        $params = $filters['params'] ?? '{}';
        unset($filters['params']);
        // TODO do we need default parameters here?
        $params = $this->getParams(\json_decode($params, true));
        $maxPerPage = ($params['max_per_page'] ?? null) ? $params['max_per_page']->getValue() : null;

        $filters['locale'] = $locale;
        $filters['excluded'] = \array_filter(\explode(',', $this->getRequestParameter($request, 'excluded')));

        $filters['categoryIds'] = isset($filters['categories']) ? \array_filter(\explode(',', $this->getRequestParameter($request, 'categories'))) : null;
        unset($filters['categories']);
        $filters['categoryOperator'] = isset($filters['categoryOperator']) ? \strtoupper($this->getRequestParameter($request, 'categoryOperator')) : null;

        $filters['tagNames'] = isset($filters['tags']) ? \array_filter(\explode(',', $this->getRequestParameter($request, 'tags'))) : null;
        unset($filters['tags']);
        $filters['tagOperator'] = isset($filters['tagOperator']) ? \strtoupper($this->getRequestParameter($request, 'tagOperator')) : null;

        $filters['types'] = isset($filters['types']) ? \explode(',', $this->getRequestParameter($request, 'types')) : null;
        $filters['sortBy'] = isset($filters['sortBy']) ? $this->getRequestParameter($request, 'sortBy') : null;
        $filters['includeSubFolders'] = isset($filters['includeSubFolders']) && 'true' === $filters['includeSubFolders'];
        $filters['webspaceKey'] = $this->getRequestParameter($request, 'webspace');
        $filters['datasource'] = $this->getRequestParameter($request, 'datasource');
        $filters['page'] = (int) $this->getRequestParameter($request, 'page', false, 1);
        $filters['limit'] = ($filters['limitResult'] ?? $maxPerPage) ? (int)($filters['limitResult'] ?? $maxPerPage) : null;
        $filters = \array_filter($filters);

        $sortBys = [];
        if ($filters['sortBy'] ?? null) {
            $sortBys[$filters['sortBy']] = $filters['sortMethod'] ?? 'asc';
            unset($filters['sortBy'], $filters['sortMethod']);
        }

        $providerType = $this->getRequestParameter($request, 'provider', true);

        if (!$this->smartContentProviderLocator->has($providerType)) {
            throw new DataProviderNotExistsException(
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
     * Returns property-parameter.
     *
     * @return PropertyParameter[]
     */
    private function getParams(array $params): array
    {
        $result = [];
        foreach ($params as $name => $item) {
            $type = $item['type'] ?? null;
            $value = $item['value'];
            if ('collection' === $type) {
                $value = $this->getParams($value);
            }

            $result[$name] = new PropertyParameter($name, $value, $type);
        }

        return $result;
    }

    public function getLocale(Request $request): string
    {
        return $this->getRequestParameter($request, 'locale', true);
    }
}
