<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Api\RootCollection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Makes collections available through a REST API.
 */
class CollectionController extends RestController implements ClassResourceInterface, SecuredControllerInterface, SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var string
     */
    protected static $entityName = 'SuluMediaBundle:Collection';

    /**
     * @var string
     */
    protected static $entityKey = 'collections';

    /**
     * returns all fields that can be used by list.
     *
     * @Get("collection/fields")
     *
     * @return mixed
     */
    public function getFieldsAction()
    {
        $fieldDescriptors = array_values($this->getCollectionManager()->getFieldDescriptors());

        return $this->handleView($this->view($fieldDescriptors, 200));
    }

    /**
     * persists a setting.
     *
     * @Put("collection/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single collection with the given id.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        if ($this->getBooleanRequestParameter($request, 'tree', false, false)) {
            $collections = $this->getCollectionManager()->getTreeById(
                $id,
                $this->getRequestParameter($request, 'locale', true)
            );

            if ($this->getBooleanRequestParameter($request, 'include-root', false, false)) {
                $collections = [
                    new RootCollection($collections),
                ];
            }

            return $this->handleView(
                $this->view(
                    new CollectionRepresentation($collections, 'collections')
                )
            );
        }

        try {
            $locale = $this->getRequestParameter($request, 'locale', true);
            $depth = intval($request->get('depth', 0));
            $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);
            $collectionManager = $this->getCollectionManager();

            // filter children
            $listRestHelper = $this->get('sulu_core.list_rest_helper');
            $limit = $request->get('limit', null);
            $offset = $this->getOffset($request, $limit);
            $search = $listRestHelper->getSearchPattern();
            $sortBy = $request->get('sortBy');
            $sortOrder = $request->get('sortOrder', 'ASC');

            $filter = [
                'limit' => $limit,
                'offset' => $offset,
                'search' => $search,
            ];

            $view = $this->responseGetById(
                $id,
                function ($id) use ($locale, $collectionManager, $depth, $breadcrumb, $filter, $sortBy, $sortOrder) {
                    $collection = $collectionManager->getById(
                        $id,
                        $locale,
                        $depth,
                        $breadcrumb,
                        $filter,
                        $sortBy !== null ? [$sortBy => $sortOrder] : []
                    );

                    if ($collection->getType()->getKey() === SystemCollectionManagerInterface::COLLECTION_TYPE) {
                        $this->get('sulu_security.security_checker')->checkPermission(
                            'sulu.media.system_collections',
                            PermissionTypes::VIEW
                        );
                    }

                    return $collection;
                }
            );
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * lists all collections.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        try {
            /** @var ListRestHelperInterface $listRestHelper */
            $listRestHelper = $this->get('sulu_core.list_rest_helper');
            $securityChecker = $this->get('sulu_security.security_checker');

            $flat = $this->getBooleanRequestParameter($request, 'flat', false);
            $depth = $request->get('depth', 0);
            $limit = $request->get('limit', null);
            $offset = $this->getOffset($request, $limit);
            $search = $listRestHelper->getSearchPattern();
            $sortBy = $request->get('sortBy');
            $sortOrder = $request->get('sortOrder', 'ASC');
            $collectionManager = $this->getCollectionManager();

            if ($flat) {
                $collections = $collectionManager->get(
                    $this->getRequestParameter($request, 'locale', true),
                    [
                        'depth' => $depth,
                    ],
                    $limit,
                    $offset,
                    $sortBy !== null ? [$sortBy => $sortOrder] : []
                );
            } else {
                $collections = $collectionManager->getTree(
                    $this->getRequestParameter($request, 'locale', true),
                    $offset,
                    $limit,
                    $search,
                    $depth,
                    $sortBy !== null ? [$sortBy => $sortOrder] : [],
                    $securityChecker->hasPermission('sulu.media.system_collections', 'view')
                );
            }

            if ($this->getBooleanRequestParameter($request, 'include-root', false, false)) {
                $collections = [
                    new RootCollection($collections),
                ];
            }

            $all = $collectionManager->getCount();

            $list = new ListRepresentation(
                $collections,
                self::$entityKey,
                'get_collections',
                $request->query->all(),
                $listRestHelper->getPage(),
                $listRestHelper->getLimit(),
                $all
            );

            $view = $this->view($list, 200);
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        } catch (MediaException $me) {
            $view = $this->view($me->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new collection.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing collection with the given id.
     *
     * @param int $id The id of the collection to update
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Delete a collection with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            try {
                $collectionManager = $this->getCollectionManager();
                $collectionManager->delete($id);
            } catch (CollectionNotFoundException $cnf) {
                throw new EntityNotFoundException(self::$entityName, $id); // will throw 404 Entity not found
            } catch (MediaException $me) {
                throw new RestException($me->getMessage(), $me->getCode()); // will throw 400 Bad Request
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Trigger an action for given media. Action is specified over get-action parameter.
     *
     * @Post("collections/{id}")
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function postTriggerAction($id, Request $request)
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'move':
                    return $this->moveEntity($id, $request);
                    break;
                default:
                    throw new RestException(sprintf('Unrecognized action: "%s"', $action));
            }
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * Moves an entity into another one.
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    protected function moveEntity($id, Request $request)
    {
        $destinationId = $this->getRequestParameter($request, 'destination');
        $locale = $this->getRequestParameter($request, 'locale', true);
        $collection = $this->getCollectionManager()->move($id, $locale, $destinationId);
        $view = $this->view($collection);

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     *
     * @return Collection
     */
    protected function getData(Request $request)
    {
        return [
            'style' => $request->get('style'),
            'type' => $request->get('type', $this->container->getParameter('sulu_media.collection.type.default')),
            'parent' => $request->get('parent'),
            'locale' => $this->getRequestParameter($request, 'locale', true),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'changer' => $request->get('changer'),
            'creator' => $request->get('creator'),
            'changed' => $request->get('changed'),
            'created' => $request->get('created'),
        ];
    }

    /**
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        $systemCollectionManager = $this->get('sulu_media.system_collections.manager');
        $parent = $request->get('parent');

        if (($id !== null && $systemCollectionManager->isSystemCollection(intval($id))) ||
            ($parent !== null && $systemCollectionManager->isSystemCollection(intval($parent)))
        ) {
            throw new AccessDeniedException('Permission "update" or "create" is not granted for system collections');
        }

        try {
            $collectionManager = $this->getCollectionManager();
            $data = $this->getData($request);
            $data['id'] = $id;
            $data['locale'] = $this->getRequestParameter($request, 'locale', true);
            $collection = $collectionManager->save($data, $this->getUser()->getId());

            $view = $this->view($collection, 200);
        } catch (CollectionNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @return CollectionManagerInterface
     */
    protected function getCollectionManager()
    {
        return $this->get('sulu_media.collection_manager');
    }

    /**
     * @param Request $request
     * @param $limit
     *
     * @return int
     */
    private function getOffset(Request $request, $limit)
    {
        $page = $request->get('page', 1);

        return ($limit !== null) ? $limit * ($page - 1) : 0;
    }

    /**
     * @return string
     */
    public function getSecurityContext()
    {
        return 'sulu.media.collections';
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredClass()
    {
        return CollectionEntity::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredObjectId(Request $request)
    {
        return $request->get('id') ?: $request->get('parent');
    }
}
