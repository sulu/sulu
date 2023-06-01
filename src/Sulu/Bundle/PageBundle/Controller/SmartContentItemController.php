<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\SmartContent\DataProviderPoolInterface;
use Sulu\Component\SmartContent\Exception\DataProviderNotExistsException;
use Sulu\Component\SmartContent\Rest\ItemCollectionRepresentation;
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
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var DataProviderPoolInterface
     */
    private $dataProviderPool;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TagManagerInterface $tagManager,
        DataProviderPoolInterface $dataProviderPool,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);
        $this->tagManager = $tagManager;
        $this->dataProviderPool = $dataProviderPool;
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
        // prepare filters and options
        $providerAlias = $this->getRequestParameter($request, 'provider', true);
        $filters = $request->query->all();
        $filters['excluded'] = \array_filter(\explode(',', $this->getRequestParameter($request, 'excluded')));
        if (isset($filters['categories'])) {
            $filters['categories'] = \explode(',', $this->getRequestParameter($request, 'categories'));
        }
        if (isset($filters['tags'])) {
            $filters['tags'] = \explode(',', $this->getRequestParameter($request, 'tags'));
        }
        if (isset($filters['types'])) {
            $filters['types'] = \explode(',', $this->getRequestParameter($request, 'types'));
        }
        if (isset($filters['sortBy'])) {
            $filters['sortBy'] = $this->getRequestParameter($request, 'sortBy');
        }
        if (isset($filters['includeSubFolders'])) {
            $filters['includeSubFolders'] = 'true' === $filters['includeSubFolders'];
        }
        $filters = \array_filter($filters);
        $options = [
            'webspaceKey' => $this->getRequestParameter($request, 'webspace'),
            'locale' => $this->getLocale($request),
        ];

        // resolve tags if they exists in filters
        if (isset($filters['tags'])) {
            $filters['tags'] = $this->tagManager->resolveTagNames($filters['tags']);
        }

        // prepare provider
        $provider = $this->dataProviderPool->get($providerAlias);

        $params = \array_merge(
            $provider->getDefaultPropertyParameter(),
            $this->getParams(\json_decode($request->get('params', '{}'), true))
        );

        $user = $this->getUser();

        // resolve datasource and items
        $data = $provider->resolveDataItems(
            $filters,
            $params,
            $options,
            $filters['limitResult'] ?? null,
            1,
            null,
            $user
        );
        $items = $data->getItems();
        $datasource = $provider->resolveDatasource($request->get('dataSource'), [], $options, $user);

        return $this->handleView($this->view(new ItemCollectionRepresentation($items, $datasource)));
    }

    /**
     * Returns property-parameter.
     *
     * @return PropertyParameter[]
     */
    private function getParams(array $params)
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

    public function getLocale(Request $request)
    {
        return $this->getRequestParameter($request, 'locale', true);
    }
}
