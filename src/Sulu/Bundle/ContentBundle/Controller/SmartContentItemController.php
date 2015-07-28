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

    public function postItemsAction(Request $request)
    {
        $providerAlias = $this->getRequestParameter($request, 'provider', true);
        $excluded = intval($this->getRequestParameter($request, 'excluded', true));
        $filters = $request->request->all();
        $options = [
            'webspaceKey' => $this->getRequestParameter($request, 'webspace', true),
            'locale' => $this->getRequestParameter($request, 'locale', true),
        ];

        $filters['excluded'] = [$excluded];

        $dataProviderPool = $this->get('sulu_content.smart_content.data_provider_pool');
        $provider = $dataProviderPool->get($providerAlias);
        $items = $provider->resolveFilters($filters, [], $options, $request->get('limitResult'));
        $datasource = $provider->resolveDatasource($request->get('dataSource'), [], $options);

        return $this->handleView($this->view(new ItemCollectionRepresentation($items, $datasource)));
    }
}
