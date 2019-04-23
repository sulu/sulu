<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\RouteAwareRepresentation;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteResource("route")
 */
class CustomUrlRouteController extends RestController implements SecuredControllerInterface
{
    private static $relationName = 'custom_url_routes';

    public function cgetAction(string $webspace, string $id, Request $request)
    {
        // TODO pagination

        $historyRoutes = $this->get('sulu_custom_urls.manager')->findHistoryRoutesById($id, $webspace);

        $result = [];
        foreach ($historyRoutes as $resourcelocator => $historyRoute) {
            $result[] = [
                'id' => $historyRoute->getUuid(),
                'resourcelocator' => $resourcelocator,
                'created' => $historyRoute->getCreated(),
            ];
        }

        $list = new RouteAwareRepresentation(
            new CollectionRepresentation($result, self::$relationName),
            'get_webspace_custom-urls_routes',
            array_merge($request->request->all(), ['id' => $id, 'webspace' => $webspace])
        );

        return $this->handleView($this->view($list));
    }

    public function cdeleteAction(string $webspace, string $id, Request $request)
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        $manager = $this->get('sulu_custom_urls.manager');
        foreach ($ids as $id) {
            $manager->deleteRoute($webspace, $id);
        }
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView($this->view());
    }

    public function getSecurityContext()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->get('webspace'));
    }

    public function getLocale(Request $request)
    {
        return;
    }
}
