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
use JMS\Serializer\SerializationContext;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides rest api for custom-urls.
 *
 * @RouteResource("custom-urls")
 */
class CustomUrlController extends RestController implements SecuredControllerInterface
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

        $result = $this->get('sulu_custom_urls.manager')->findList(
            $webspaceKey,
            $this->getRequestParameter($request, 'locale', true)
        );

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
        $document = $this->get('sulu_custom_urls.manager')->find(
            $uuid,
            $this->getRequestParameter($request, 'locale', true)
        );

        // FIXME without this target-document will not be loaded (for serialization)
        // - issue https://github.com/sulu-io/sulu-document-manager/issues/71
        if (null !== $document->getTargetDocument()) {
            $document->getTargetDocument()->getTitle();
        }

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()->setGroups(['defaultCustomUrl', 'smallPage', 'fullRoute'])
            )
        );
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
        $document = $this->get('sulu_custom_urls.manager')->create(
            $webspaceKey,
            $request->request->all(),
            $this->getRequestParameter($request, 'targetLocale', true)
        );
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()->setGroups(['defaultCustomUrl', 'smallPage', 'fullRoute'])
            )
        );
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
        $manager = $this->get('sulu_custom_urls.manager');

        $document = $manager->save(
            $uuid,
            $request->request->all(),
            $this->getRequestParameter($request, 'targetLocale', true)
        );
        $manager->invalidate($document);
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView(
            $this->view($document)->setSerializationContext(
                SerializationContext::create()->setGroups(['defaultCustomUrl', 'smallPage', 'fullRoute'])
            )
        );
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
        $manager = $this->get('sulu_custom_urls.manager');
        $document = $manager->delete($uuid);
        $manager->invalidate($document);
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
            $document = $manager->delete($uuid);
            $manager->invalidate($document);
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
            $document = $manager->deleteRoute($webspaceKey, $uuid);
            $manager->invalidateRoute($webspaceKey, $document);
        }
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView($this->view());
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->get('webspaceKey'));
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return;
    }
}
