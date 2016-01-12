<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\RouteAwareRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides rest api for custom-urls.
 *
 * @RouteResource("custom-urls")
 */
class CustomUrlController extends RestController
{
    use RequestParametersTrait;

    private static $relationName = 'custom-urls';

    public function cgetAction($webspaceKey, Request $request)
    {
        $result = $this->get('sulu_custom_urls.manager')->readList($webspaceKey);

        $list = new RouteAwareRepresentation(
            new CollectionRepresentation($result, self::$relationName),
            'cget_webspace_custom-urls',
            array_merge($request->request->all(), ['webspaceKey' => $webspaceKey])
        );

        return $this->handleView($this->view($list));
    }

    public function postAction($webspaceKey, Request $request)
    {
        $document = $this->get('sulu_custom_urls.manager')->create($webspaceKey, $request->request->all());
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView($this->view($document));
    }
}
