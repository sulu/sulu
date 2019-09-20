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

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
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

    private static $relationName = 'custom_urls';

    /**
     * Returns a list of custom-urls.
     *
     * @param string $webspace
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction($webspace, Request $request)
    {
        // TODO pagination

        $result = $this->get('sulu_custom_urls.manager')->findList($webspace);

        $list = new CollectionRepresentation($result, self::$relationName);

        return $this->handleView($this->view($list));
    }

    /**
     * Returns a single custom-url identified by uuid.
     *
     * @param string $webspace
     * @param string $id
     * @param Request $request
     *
     * @return Response
     */
    public function getAction($webspace, $id, Request $request)
    {
        $document = $this->get('sulu_custom_urls.manager')->find($id);

        // FIXME without this target-document will not be loaded (for serialization)
        // - issue https://github.com/sulu-io/sulu-document-manager/issues/71
        if (null !== $document->getTargetDocument()) {
            $document->getTargetDocument()->getTitle();
        }

        $view = $this->view($document);

        $context = new Context();
        $context->setGroups(['defaultCustomUrl', 'fullRoute']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Create a new custom-url object.
     *
     * @param string $webspace
     * @param Request $request
     *
     * @return Response
     */
    public function postAction($webspace, Request $request)
    {
        $document = $this->get('sulu_custom_urls.manager')->create(
            $webspace,
            $request->request->all(),
            $this->getRequestParameter($request, 'targetLocale', true)
        );
        $this->get('sulu_document_manager.document_manager')->flush();

        $context = new Context();
        $context->setGroups(['defaultCustomUrl', 'fullRoute']);

        return $this->handleView($this->view($document)->setContext($context));
    }

    /**
     * Update an existing custom-url object identified by uuid.
     *
     * @param string $webspace
     * @param string $id
     * @param Request $request
     *
     * @return Response
     */
    public function putAction($webspace, $id, Request $request)
    {
        $manager = $this->get('sulu_custom_urls.manager');

        $document = $manager->save($id, $request->request->all());
        $this->get('sulu_document_manager.document_manager')->flush();

        $context = new Context();
        $context->setGroups(['defaultCustomUrl', 'fullRoute']);

        return $this->handleView($this->view($document)->setContext($context));
    }

    /**
     * Delete a single custom-url identified by uuid.
     *
     * @param string $webspace
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction($webspace, $id)
    {
        $manager = $this->get('sulu_custom_urls.manager');
        $manager->delete($id);
        $this->get('sulu_document_manager.document_manager')->flush();

        return $this->handleView($this->view());
    }

    /**
     * Deletes a list of custom-urls identified by a list of uuids.
     *
     * @param string $webspace
     * @param Request $request
     *
     * @return Response
     */
    public function cdeleteAction($webspace, Request $request)
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        $manager = $this->get('sulu_custom_urls.manager');
        foreach ($ids as $ids) {
            $manager->delete($ids);
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

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->get('webspace'));
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return;
    }
}
