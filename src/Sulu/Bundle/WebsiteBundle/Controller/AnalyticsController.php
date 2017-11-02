<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\RouteAwareRepresentation;
use Sulu\Bundle\WebsiteBundle\Admin\WebsiteAdmin;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides webspace analytics rest-endpoint.
 */
class AnalyticsController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    const RESULT_KEY = 'analytics';

    /**
     * Returns webspace analytics by webspace key.
     *
     * @param Request $request
     * @param string $webspaceKey
     *
     * @return Response
     */
    public function cgetAction(Request $request, $webspaceKey)
    {
        $entities = $this->get('sulu_website.analytics.manager')->findAll($webspaceKey);

        $list = new RouteAwareRepresentation(
            new CollectionRepresentation($entities, self::RESULT_KEY),
            'cget_webspace_analytics',
            array_merge($request->request->all(), ['webspaceKey' => $webspaceKey])
        );

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Returns a single analytics by id.
     *
     * @param string $webspaceKey
     * @param int $id
     *
     * @return Response
     */
    public function getAction($webspaceKey, $id)
    {
        $entity = $this->get('sulu_website.analytics.manager')->find($id);

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Creates a analytics for given webspace.
     *
     * @param Request $request
     * @param string $webspaceKey
     *
     * @return Response
     */
    public function postAction(Request $request, $webspaceKey)
    {
        $entity = $this->get('sulu_website.analytics.manager')->create($webspaceKey, $request->request->all());
        $this->get('doctrine.orm.entity_manager')->flush();
        $this->get('sulu_website.http_cache.clearer')->clear();

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Updates analytics with given id.
     *
     * @param Request $request
     * @param string $webspaceKey
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $webspaceKey, $id)
    {
        $entity = $this->get('sulu_website.analytics.manager')->update($id, $request->request->all());
        $this->get('doctrine.orm.entity_manager')->flush();
        $this->get('sulu_website.http_cache.clearer')->clear();

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Removes given analytics.
     *
     * @param string $webspaceKey
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($webspaceKey, $id)
    {
        $this->get('sulu_website.analytics.manager')->remove($id);
        $this->get('doctrine.orm.entity_manager')->flush();
        $this->get('sulu_website.http_cache.clearer')->clear();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * Removes a list of analytics.
     *
     * @param Request $request
     * @param $webspaceKey
     *
     * @return Response
     */
    public function cdeleteAction(Request $request, $webspaceKey)
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        $this->get('sulu_website.analytics.manager')->removeMultiple($ids);
        $this->get('doctrine.orm.entity_manager')->flush();
        $this->get('sulu_website.http_cache.clearer')->clear();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        return WebsiteAdmin::getAnalyticsSecurityContext($request->get('webspaceKey'));
    }
}
