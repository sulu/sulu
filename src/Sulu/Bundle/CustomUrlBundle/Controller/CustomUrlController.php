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
use Sulu\Component\CustomUrl\Manager\CannotDeleteCurrentRouteException;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides rest api for custom-urls.
 *
 * @RouteResource("custom-urls")
 */
class CustomUrlController extends RestController
{
    use RequestParametersTrait;

    private static $relationName = 'custom-urls';

    /**
     * Returns a list of custom-urls.
     *
     * @param string $webspaceKey
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction($webspaceKey, Request $request)
    {
        // TODO pagination

        $result = $this->get('sulu_custom_urls.manager')->readList($webspaceKey, $this->getLocale($request));

        $list = new RouteAwareRepresentation(
            new CollectionRepresentation($result, self::$relationName),
            'cget_webspace_custom-urls',
            array_merge($request->request->all(), ['webspaceKey' => $webspaceKey])
        );

        return $this->handleView($this->view($list));
    }

    /**
     * Returns a single custom-url identified by uuid.
     *
     * @param string $webspaceKey
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function getAction($webspaceKey, $uuid, Request $request)
    {
        try {
            $document = $this->get('sulu_custom_urls.manager')->read($uuid, $this->getLocale($request));
        } catch (DocumentNotFoundException $ex) {
            return $this->handleView($this->view(null, 404));
        }

        // FIXME without this target-document will not be loaded (for serialization)
        if (null !== $document->getTarget()) {
            $document->getTarget()->getTitle();
        }

        return $this->handleView($this->view($document));
    }

    /**
     * Create a new custom-url object.
     *
     * @param string $webspaceKey
     * @param Request $request
     *
     * @return Response
     */
    public function postAction($webspaceKey, Request $request)
    {
        try {
            $document = $this->get('sulu_custom_urls.manager')->create(
                $webspaceKey,
                $request->request->all(),
                $this->getLocale($request)
            );
            $this->get('sulu_document_manager.document_manager')->flush();
        } catch (RestException $ex) {
            return $this->handleView($this->view($ex->toArray(), 400));
        }

        return $this->handleView($this->view($document));
    }

    /**
     * Update an existing custom-url object identified by uuid.
     *
     * @param string $webspaceKey
     * @param string $uuid
     * @param Request $request
     *
     * @return Response
     */
    public function putAction($webspaceKey, $uuid, Request $request)
    {
        try {
            $document = $this->get('sulu_custom_urls.manager')->update(
                $uuid,
                $request->request->all(),
                $this->getLocale($request)
            );
            $this->get('sulu_document_manager.document_manager')->flush();
        } catch (RestException $ex) {
            return $this->handleView($this->view($ex->toArray(), 400));
        }

        return $this->handleView($this->view($document));
    }

    /**
     * Delete a single custom-url identified by uuid.
     *
     * @param string $webspaceKey
     * @param string $uuid
     *
     * @return Response
     */
    public function deleteAction($webspaceKey, $uuid)
    {
        $this->get('sulu_custom_urls.manager')->delete($uuid);
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView($this->view());
    }

    /**
     * Deletes a list of custom-urls identified by a list of uuids.
     *
     * @param string $webspaceKey
     * @param Request $request
     *
     * @return Response
     */
    public function cdeleteAction($webspaceKey, Request $request)
    {
        $uuids = array_filter(explode(',', $request->get('ids', '')));

        $manager = $this->get('sulu_custom_urls.manager');
        foreach ($uuids as $uuid) {
            $manager->delete($uuid);
        }
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView($this->view());
    }

    /**
     * Deletes a lst of custom-urls identified by a list of uuids.
     *
     * @param $webspaceKey
     * @param string $customUrlUuid
     * @param Request $request
     *
     * @return Response
     */
    public function cdeleteRoutesAction($webspaceKey, $customUrlUuid, Request $request)
    {
        $uuids = array_filter(explode(',', $request->get('ids', '')));

        $manager = $this->get('sulu_custom_urls.manager');
        foreach ($uuids as $uuid) {
            try {
                $manager->deleteRoute($webspaceKey, $uuid);
            } catch (CannotDeleteCurrentRouteException $ex) {
                return $this->handleView($this->view($ex->toArray(), 400));
            }
        }
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView($this->view());
    }
}
