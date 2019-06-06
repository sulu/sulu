<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides api to handle routes.
 */
class RouteController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * Returns list of routes for given entity.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        // required parameter
        $locale = $this->getRequestParameter($request, 'locale', true);
        $resourceKey = $this->getRequestParameter($request, 'resourceKey', true);
        $id = $this->getRequestParameter($request, 'id', true);

        // optional parameter
        $history = $this->getBooleanRequestParameter($request, 'history', false, false);
        $entityClass = $this->getParameter('sulu_route.resource_key_mappings')[$resourceKey] ?? null;

        if (!$entityClass) {
            throw new NotFoundHttpException(sprintf('No route mapping configured for resourceKey "%s"', $resourceKey));
        }

        $routes = $this->findRoutes($history, $entityClass, $id, $locale);

        return $this->handleView($this->view(new CollectionRepresentation($routes, 'routes')));
    }

    /**
     * Find routes with given parameters.
     *
     * @param bool $history
     * @param string $entityClass
     * @param string $entityId
     * @param string $locale
     *
     * @return RouteInterface|RouteInterface[]
     */
    private function findRoutes($history, $entityClass, $entityId, $locale)
    {
        /** @var RouteRepositoryInterface $routeRespository */
        $routeRespository = $this->get('sulu.repository.route');

        if ($history) {
            return $routeRespository->findHistoryByEntity($entityClass, $entityId, $locale);
        }

        return [$routeRespository->findByEntity($entityClass, $entityId, $locale)];
    }

    /**
     * Delete given history-route.
     *
     * @param int[] $ids
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function cdeleteAction(Request $request)
    {
        /** @var RouteRepositoryInterface $routeRespository */
        $routeRespository = $this->get('sulu.repository.route');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $ids = explode(',', $request->get('ids'));

        foreach ($ids as $id) {
            $route = $routeRespository->find($id);
            if (!$route) {
                throw new EntityNotFoundException(RouteInterface::class, $id);
            }

            $entityManager->remove($route);
        }

        $entityManager->flush();

        return $this->handleView($this->view());
    }
}
