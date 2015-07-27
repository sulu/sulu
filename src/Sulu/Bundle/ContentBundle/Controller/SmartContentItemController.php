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
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides results for smart-content filters.
 */
class SmartContentItemController extends RestController
{
    use RequestParametersTrait;

    public function postItemsAction(Request $request)
    {
        $providerAlias = $this->getRequestParameter($request, 'alias');
        $limit = intval($this->getRequestParameter($request, 'limit'));
        $filters = $request->request->all();
        $options = [
            'webspaceKey' => $this->getRequestParameter($request, 'alias'),
            'locale' => $this->getRequestParameter($request, 'locale')
        ];

        $dataProviderPool = $this->get('sulu_content.smart_content.data_provider_pool');
        $provider = $dataProviderPool->get($providerAlias);

        return $this->handleView($this->view($provider->resolveFilters($filters, [], $options, $limit)));
    }
}
