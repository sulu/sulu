<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $webspace
     *
     * @return Response
     */
    public function cgetAction(Request $request, $webspace)
    {
        $entities = $this->get('sulu_website.analytics.manager')->findAll($webspace);

        $list = new RouteAwareRepresentation(
            new CollectionRepresentation($entities, self::RESULT_KEY),
            'sulu_website.cget_webspace_analytics',
            array_merge($request->request->all(), ['webspace' => $webspace])
        );

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Returns a single analytics by id.
     *
     * @param string $webspace
     * @param int $id
     *
     * @return Response
     */
    public function getAction($webspace, $id)
    {
        $entity = $this->get('sulu_website.analytics.manager')->find($id);

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Creates a analytics for given webspace.
     *
     * @param Request $request
     * @param string $webspace
     *
     * @return Response
     */
    public function postAction(Request $request, $webspace)
    {
        $data = $request->request->all();
        $data['content'] = $this->buildContent($data);

        $entity = $this->get('sulu_website.analytics.manager')->create($webspace, $data);
        $this->get('doctrine.orm.entity_manager')->flush();
        $this->get('sulu_website.http_cache.clearer')->clear();

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Updates analytics with given id.
     *
     * @param Request $request
     * @param string $webspace
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $webspace, $id)
    {
        $data = $request->request->all();
        $data['content'] = $this->buildContent($data);

        $entity = $this->get('sulu_website.analytics.manager')->update($id, $data);
        $this->get('doctrine.orm.entity_manager')->flush();
        $this->get('sulu_website.http_cache.clearer')->clear();

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Removes given analytics.
     *
     * @param string $webspace
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($webspace, $id)
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
     * @param $webspace
     *
     * @return Response
     */
    public function cdeleteAction(Request $request, $webspace)
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

        return WebsiteAdmin::getAnalyticsSecurityContext($request->get('webspace'));
    }

    private function buildContent(array $data)
    {
        if (!array_key_exists('type', $data)) {
            return null;
        }

        switch ($data['type']) {
            case 'google':
                return $data['google_key'] ?? null;
            case 'google_tag_manager':
                return $data['google_tag_manager_key'] ?? null;
            case 'matomo':
                return [
                    'siteId' => $data['matomo_id'] ?? null,
                    'url' => $data['matomo_url'] ?? null,
                ];
            case 'custom':
                return [
                    'position' => $data['custom_position'] ?? null,
                    'value' => $data['custom_script'] ?? null,
                ];
            default:
                return null;
        }
    }
}
