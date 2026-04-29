<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\RootCollection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Makes collections available through a REST API.
 */
class CollectionController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface, SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var string
     */
    protected static $entityName = \Sulu\Bundle\MediaBundle\Entity\Collection::class;

    /**
     * @var string
     *
     * @deprecated Use CollectionInterface::RESOURCE_KEY instead
     */
    protected static $entityKey = CollectionInterface::RESOURCE_KEY;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private ListRestHelperInterface $listRestHelper,
        private SecurityCheckerInterface $securityChecker,
        private TranslatorInterface $translator,
        private SystemCollectionManagerInterface $systemCollectionManager,
        private CollectionManagerInterface $collectionManager,
        private array $defaultCollectionType,
        private array $permissions,
        private ?string $collectionClass = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        if (!$this->collectionClass) {
            $this->collectionClass = CollectionEntity::class;

            @trigger_deprecation(
                'sulu/sulu',
                '2.1',
                \sprintf(
                    'Omitting the "collectionClass" argument is deprecated and will not longer work in Sulu 3.0.'
                )
            );
        }
    }

    /**
     * Shows a single collection with the given id.
     *
     * > Note: If you provide a non existing id you will get
     * >
     * > tree version: an empty collection and status code 200
     * > flat version: an error message and status code 400
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getLocale($request) ?? $request->getLocale();

        if ($request->query->getBoolean('tree')) {
            $collections = $this->collectionManager->getTreeById($id, $locale);

            return $this->handleView(
                $this->view(
                    new CollectionRepresentation($collections, CollectionInterface::RESOURCE_KEY)
                )
            );
        }

        $depth = $request->query->getInt('depth');
        $breadcrumb = $request->query->getBoolean('breadcrumb');
        $children = $request->query->getBoolean('children');

        // filter children
        $limit = $this->listRestHelper->getLimit();
        /** @var int $offset */
        $offset = $this->listRestHelper->getOffset();
        $search = $this->listRestHelper->getSearchPattern();
        $sortBy = $this->listRestHelper->getSortColumn();
        $sortOrder = $this->listRestHelper->getSortOrder();

        $filter = [
            'limit' => $limit,
            'offset' => $offset,
            'search' => $search,
        ];

        try {
            $locale = $this->getLocale($request);
            $depth = \intval($this->getRequestParameter($request, 'depth', false, 0));
            $breadcrumb = $this->getBooleanRequestParameter($request, 'breadcrumb', false, false);
            $children = $this->getBooleanRequestParameter($request, 'children', false, false);

            // filter children
            $limit = $this->listRestHelper->getLimit();
            $offset = $this->listRestHelper->getOffset();
            $search = $this->listRestHelper->getSearchPattern();
            /** @var string|null $sortBy */
            $sortBy = $request->get('sortBy', 'title');
            /** @var string $sortOrder */
            $sortOrder = $request->get('sortOrder', 'ASC');

            $filter = [
                'limit' => $limit,
                'offset' => $offset,
                'search' => $search,
            ];

            $view = $this->responseGetById(
                $id,
                function($id) use ($locale, $depth, $breadcrumb, $filter, $sortBy, $sortOrder, $children) {
                    $collection = $this->collectionManager->getById(
                        $id,
                        $locale,
                        $depth,
                        $breadcrumb,
                        $filter,
                        null !== $sortBy ? [$sortBy => $sortOrder] : [],
                        $children,
                        $this->permissions[PermissionTypes::VIEW]
                    );

                    if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collection->getType()->getKey()) {
                        $this->securityChecker->checkPermission(
                            'sulu.media.system_collections',
                            PermissionTypes::VIEW
                        );
                    }

                    return $collection;
                }
            );
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * lists all collections.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $depth = $request->query->getInt('depth');
        $parentId = $request->get('parentId', null);
        $limit = $request->query->getInt('limit', 1000);

        /** @var int $offset */
        $offset = $this->listRestHelper->getOffset();
        $search = $this->listRestHelper->getSearchPattern();
        $sortBy = $this->listRestHelper->getSortColumn();
        $sortOrder = $this->listRestHelper->getSortOrder();
        $includeRoot = $request->query->getBoolean('includeRoot');
        $locale = $request->query->getString('locale');

        if ('root' === $parentId) {
            $includeRoot = false;
            $parentId = null;
        }

        try {
            $flat = $this->getBooleanRequestParameter($request, 'flat', false);
            $depth = $request->get('depth', 0);
            $parentId = $request->get('parentId', null);
            /** @var int|null $limit */
            $limit = $request->get('limit', null);
            $offset = $this->getOffset($request, $limit);
            $search = $this->listRestHelper->getSearchPattern();
            /** @var string|null $sortBy */
            $sortBy = $request->get('sortBy', 'title');
            /** @var string $sortOrder */
            $sortOrder = $request->get('sortOrder', 'ASC');
            $includeRoot = $this->getBooleanRequestParameter($request, 'includeRoot', false, false);

            if ('root' === $parentId) {
                $includeRoot = false;
                $parentId = null;
            }

            if ($flat) {
                $collections = $this->collectionManager->get(
                    $locale,
                    [
                        'depth' => $depth,
                        'parent' => $parentId,
                    ],
                    $limit,
                    $offset,
                    null !== $sortBy ? [$sortBy => $sortOrder] : []
                );
            } else {
                $collections = $this->collectionManager->getTree(
                    $locale,
                    $offset,
                    $limit,
                    $search,
                    $depth,
                    null !== $sortBy ? [$sortBy => $sortOrder] : [],
                    $this->securityChecker->hasPermission('sulu.media.system_collections', 'view'),
                    $this->permissions[PermissionTypes::VIEW]
                );
            }

            if ($includeRoot && !$parentId) {
                $collections = [
                    new RootCollection(
                        $this->translator->trans('sulu_media.all_collections', [], 'admin'),
                        $collections
                    ),
                ];
            }

            $list = new ListRepresentation(
                $collections,
                CollectionInterface::RESOURCE_KEY,
                'sulu_media.get_collections',
                $request->query->all(),
                $this->listRestHelper->getPage(),
                $this->listRestHelper->getLimit(),
                $this->collectionManager->getCount(),
            );

            $view = $this->view($list, 200);
        } catch (CollectionNotFoundException $cnf) {
            $view = $this->view($cnf->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new collection.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing collection with the given id.
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Delete a collection with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id, Request $request)
    {
        /** @var string|null $parent */
        $parent = $request->get('parent');

        $this->checkSystemCollection($id, $parent);

        $delete = function($id) {
            try {
                $this->collectionManager->delete($id);
            } catch (CollectionNotFoundException $cnf) {
                throw new EntityNotFoundException(self::$entityName, $id, $cnf); // will throw 404 Entity not found
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Trigger an action for given media. Action is specified over get-action parameter.
     *
     * @param int $id
     *
     * @return Response
     */
    public function postTriggerAction($id, Request $request)
    {
        $action = $request->query->get('action') ?: throw new MissingParameterException(self::class, 'action');

        try {
            return match ($action) {
                'move' => $this->moveEntity($id, $request),
                default => throw new RestException(\sprintf('Unrecognized action: "%s"', $action)),
            };
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * Moves an entity into another one.
     *
     * @param int $id
     *
     * @return Response
     */
    protected function moveEntity($id, Request $request)
    {
        /** @var int|null $destinationId */
        $destinationId = $request->query->get('destination');
        $locale = $request->query->getString('locale');

        $collection = $this->collectionManager->move($id, $locale, $destinationId);
        $view = $this->view($collection);

        return $this->handleView($view);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(Request $request)
    {
        return [
            'style' => $request->request->all('style'),
            'type' => $request->request->all('type', $this->defaultCollectionType),
            'parent' => $request->request->get('parent'),
            'locale' => $request->request->get('locale'),
            'title' => $request->request->get('title'),
            'description' => $request->request->get('description'),

            // These will be overriden in the CollectionManager::save function anyways
            'changer' => $request->request->get('changer'),
            'creator' => $request->request->get('creator'),
            'changed' => $request->request->get('changed'),
            'created' => $request->request->get('created'),
        ];
    }

    /**
     * @param int|null $id
     *
     * @return Response
     */
    protected function saveEntity($id, Request $request)
    {
        /** @var string|null $parent */
        $parent = $request->get('parent');
        $breadcrumb = $request->query->getBoolean('breadcrumb');

        $this->checkSystemCollection($id, $parent);

        if (!$request->request->has('locale')) {
            throw new MissingParameterException(self::class, 'locale');
        }

        try {
            $data = [
                ...$this->getData($request),
                'id' => $id,
                'locale' => $request->request->get('locale'),
            ];

            $collection = $this->collectionManager->save($data, $this->getUser()->getId(), $breadcrumb);

            $view = $this->view($collection, 200);
        } catch (CollectionNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * @param string|int|null $id
     * @param string|int|null $parent
     *
     * @return void
     */
    private function checkSystemCollection($id, $parent)
    {
        if ((null !== $id && $this->systemCollectionManager->isSystemCollection(\intval($id)))
            || (null !== $parent && $this->systemCollectionManager->isSystemCollection(\intval($parent)))
        ) {
            throw new AccessDeniedException('Permission "update" or "create" is not granted for system collections');
        }
    }

    /**
     * @return string
     */
    public function getSecurityContext()
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }

    /**
     * @return string
     */
    public function getSecuredClass()
    {
        return $this->collectionClass;
    }

    public function getSecuredObjectId(Request $request)
    {
        return $request->get('id') ?: $request->get('parent');
    }
}
