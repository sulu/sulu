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
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes media available through a REST API.
 */
class MediaController extends AbstractMediaController implements
    ClassResourceInterface,
    SecuredControllerInterface,
    SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    /**
     * @var string
     */
    protected static $entityKey = 'media';

    /**
     * returns all fields that can be used by list.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Get("media/fields")
     */
    public function getFieldsAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);

        return $this->handleView(
            $this->view(array_values($this->getFieldDescriptors($locale)), 200)
        );
    }

    /**
     * Shows a single media with the given id.
     *
     * @param $id
     * @param Request $request
     *
     * @return Response
     */
    public function getAction($id, Request $request)
    {
        try {
            $locale = $this->getRequestParameter($request, 'locale', true);
            $mediaManager = $this->getMediaManager();
            $view = $this->responseGetById(
                $id,
                function ($id) use ($locale, $mediaManager) {
                    $media = $mediaManager->getById($id, $locale);
                    $collection = $media->getEntity()->getCollection();

                    if ($collection->getType()->getKey() === SystemCollectionManagerInterface::COLLECTION_TYPE) {
                        $this->getSecurityChecker()->checkPermission(
                            'sulu.media.system_collections',
                            PermissionTypes::VIEW
                        );
                    }

                    return $media;
                }
            );
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Lists all media.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $fieldDescriptors = $this->getFieldDescriptors($locale, false);
        $ids = array_filter(explode(',', $request->get('ids')));
        $types = array_filter(explode(',', $request->get('types')));
        $listBuilder = $this->getListBuilder($request, $fieldDescriptors, $ids, $types);
        $listResponse = $listBuilder->execute();
        $count = $listBuilder->count();

        for ($i = 0, $length = count($listResponse); $i < $length; ++$i) {
            $format = $this->getFormatManager()->getFormats(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['storageOptions'],
                $listResponse[$i]['version'],
                $listResponse[$i]['subVersion'],
                $listResponse[$i]['mimeType']
            );

            if (0 < count($format)) {
                $listResponse[$i]['thumbnails'] = $format;
            }

            $listResponse[$i]['url'] = $this->getMediaManager()->getUrl(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['version']
            );
        }

        if (0 < count($ids)) {
            $result = [];
            foreach ($listResponse as $item) {
                $result[array_search($item['id'], $ids)] = $item;
            }
            ksort($result);
            $listResponse = array_values($result);
        }

        $list = new ListRepresentation(
            $listResponse,
            self::$entityKey,
            'cget_media',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $count
        );

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Returns a list-builder for media list.
     *
     * @param Request $request
     * @param FieldDescriptorInterface[] $fieldDescriptors
     * @param array $ids
     * @param array $types
     *
     * @return DoctrineListBuilder
     */
    private function getListBuilder(Request $request, array $fieldDescriptors, $ids, $types)
    {
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');
        $listBuilder = $factory->create($this->getParameter('sulu.model.media.class'));
        $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // default sort by created
        if (!$request->get('sortBy')) {
            $listBuilder->sort($fieldDescriptors['created'], 'desc');
        }

        $collectionId = $request->get('collection');
        if ($collectionId) {
            $collectionType = $this->getCollectionRepository()->findCollectionTypeById($collectionId);
            if ($collectionType === SystemCollectionManagerInterface::COLLECTION_TYPE) {
                $this->getSecurityChecker()->checkPermission(
                    'sulu.media.system_collections',
                    PermissionTypes::VIEW
                );
            }
            $listBuilder->addSelectField($fieldDescriptors['collection']);
            $listBuilder->where($fieldDescriptors['collection'], $collectionId);
        }

        // If no limit is set in request and limit is set by ids
        $requestLimit = $request->get('limit');
        $idsCount = count($ids);

        if ($idsCount > 0) {
            // correct request limit if more ids are requested
            if (!$requestLimit && $idsCount > $listBuilder->getLimit()) {
                $listBuilder->limit($idsCount);
            }

            $listBuilder->in($fieldDescriptors['id'], $ids);
        }

        // set the types
        if (count($types)) {
            $listBuilder->in($fieldDescriptors['type'], $types);
        }

        if (!$this->getSecurityChecker()->hasPermission('sulu.media.system_collections', PermissionTypes::VIEW)) {
            $systemCollection = $this->getCollectionRepository()
                ->findCollectionByKey(SystemCollectionManagerInterface::COLLECTION_KEY);

            $lftExpression = $listBuilder->createWhereExpression(
                $fieldDescriptors['lft'],
                $systemCollection->getLft(),
                ListBuilderInterface::WHERE_COMPARATOR_LESS
            );
            $rgtExpression = $listBuilder->createWhereExpression(
                $fieldDescriptors['rgt'],
                $systemCollection->getRgt(),
                ListBuilderInterface::WHERE_COMPARATOR_GREATER
            );

            $listBuilder->addExpression(
                $listBuilder->createOrExpression([
                    $lftExpression,
                    $rgtExpression,
                ])
            );
        }

        // field which will be needed afterwards to generate route
        $listBuilder->addSelectField($fieldDescriptors['version']);
        $listBuilder->addSelectField($fieldDescriptors['subVersion']);
        $listBuilder->addSelectField($fieldDescriptors['name']);
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['mimeType']);
        $listBuilder->addSelectField($fieldDescriptors['storageOptions']);
        $listBuilder->addSelectField($fieldDescriptors['id']);
        $listBuilder->addSelectField($fieldDescriptors['collection']);

        return $listBuilder;
    }

    /**
     * Creates a new media.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing media with the given id.
     *
     * @param int $id The id of the media to update
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
     * Delete a media with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            try {
                $this->getMediaManager()->delete($id, true);
            } catch (MediaNotFoundException $e) {
                $entityName = $this->getParameter('sulu.model.media.class');
                throw new EntityNotFoundException($entityName, $id); // will throw 404 Entity not found
            } catch (MediaException $e) {
                throw new RestException($e->getMessage(), $e->getCode()); // will throw 400 Bad Request
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Trigger an action for given media. Action is specified over get-action parameter.
     *
     * @Post("media/{id}")
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
                case 'new-version':
                    return $this->saveEntity($id, $request);
                    break;
                default:
                    throw new RestException(sprintf('Unrecognized action: "%s"', $action));
            }
        } catch (RestException $e) {
            $view = $this->view($e->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * Move an entity to another collection.
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    protected function moveEntity($id, Request $request)
    {
        try {
            $locale = $this->getRequestParameter($request, 'locale', true);
            $destination = $this->getRequestParameter($request, 'destination', true);
            $mediaManager = $this->getMediaManager();

            $media = $mediaManager->move(
                $id,
                $locale,
                $destination
            );

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        try {
            $mediaManager = $this->getMediaManager();
            $data = $this->getData($request, $id === null);
            $data['id'] = $id;
            $uploadedFile = $this->getUploadedFile($request, 'fileVersion');
            $media = $mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.media.collections';
    }

    /**
     * Returns the class name of the object to check.
     *
     * @return string
     */
    public function getSecuredClass()
    {
        // The media permissions are tied to the collection it is in
        return Collection::class;
    }

    /**
     * Returns the id of the object to check.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getSecuredObjectId(Request $request)
    {
        return $request->get('collection');
    }

    /**
     * @return CollectionManagerInterface
     */
    protected function getCollectionManager()
    {
        return $this->get('sulu_media.collection_manager');
    }

    /**
     * @return CollectionRepositoryInterface
     */
    protected function getCollectionRepository()
    {
        return $this->get('sulu_media.collection_repository');
    }

    /**
     * @return SecurityCheckerInterface
     */
    protected function getSecurityChecker()
    {
        return $this->get('sulu_security.security_checker');
    }

    /**
     * Returns field-descriptors for media.
     *
     * @param string $locale
     * @param bool $all
     *
     * @return FieldDescriptorInterface[]
     */
    protected function getFieldDescriptors($locale, $all = true)
    {
        return $this->get('sulu_core.list_builder.field_descriptor_factory')
            ->getFieldDescriptorForClass(
                $this->getParameter('sulu.model.media.class'),
                ['locale' => $locale],
                $all ? null : DoctrineFieldDescriptorInterface::class
            );
    }
}
