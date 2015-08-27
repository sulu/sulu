<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\SmartContent\Rest\ItemCollectionRepresentation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides results for smart-content filters.
 */
class SmartContentItemController extends RestController
{
    use RequestParametersTrait;

    /**
     * Resolves filter for smart-content UI.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Component\Rest\Exception\MissingParameterException
     * @throws \Sulu\Component\SmartContent\Exception\DataProviderNotExistsException
     */
    public function getItemsAction(Request $request)
    {
        // prepare filters and options
        $providerAlias = $this->getRequestParameter($request, 'provider', true);
        $filters = $request->query->all();
        $filters['excluded'] = [$this->getRequestParameter($request, 'excluded', true)];
        $filters = array_filter($filters);
        $options = [
            'webspaceKey' => $this->getRequestParameter($request, 'webspace', true),
            'locale' => $this->getRequestParameter($request, 'locale', true),
        ];

        // resolve tags if they exists in filters
        if (isset($filters['tags'])) {
            $filters['tags'] = $this->get('sulu_tag.tag_manager')->resolveTagNames($filters['tags']);
        }

        // prepare provider
        $dataProviderPool = $this->get('sulu_content.smart_content.data_provider_pool');
        $provider = $dataProviderPool->get($providerAlias);

        // resolve datasource and items
        $data = $provider->resolveDataItems(
            $filters,
            [],
            $options,
            (isset($filters['limitResult']) ? $filters['limitResult'] : null)
        );
        $items = $data->getItems();
        $datasource = $provider->resolveDatasource($request->get('dataSource'), [], $options);

        return $this->handleView($this->view(new ItemCollectionRepresentation($items, $datasource)));
    }
}
