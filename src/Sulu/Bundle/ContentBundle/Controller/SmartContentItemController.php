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

use Sulu\Component\Rest\ListBuilder\ListRepresentation;
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
        $providerAlias = $this->getRequestParameter($request, 'provider', true);
        $limit = intval($request->query->get('limit', 3));
        $excluded = intval($this->getRequestParameter($request, 'excluded', true));
        $filters = $request->request->all();
        $options = [
            'webspaceKey' => $this->getRequestParameter($request, 'webspace', true),
            'locale' => $this->getRequestParameter($request, 'locale', true),
        ];

        $filters['excluded'] = [$excluded];

        $dataProviderPool = $this->get('sulu_content.smart_content.data_provider_pool');
        $provider = $dataProviderPool->get($providerAlias);
        $result = $provider->resolveFilters($filters, [], $options);
        $total = count($result);

        return $this->handleView(
            $this->view(
                new ListRepresentation($result, 'items', 'post_items', [], 1, $limit, $total)
            )
        );
    }
}
