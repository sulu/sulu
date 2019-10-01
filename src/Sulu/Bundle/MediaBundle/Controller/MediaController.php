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

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $doctrineListBuilderFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var CollectionRepositoryInterface
     */
    private $collectionRepository;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var string
     */
    private $mediaClass;

    /**
     * @var string
     */
    private $collectionClass;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        MediaManagerInterface $mediaManager,
        FormatManagerInterface $formatManager,
        RestHelperInterface $restHelper,
        DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        EntityManagerInterface $entityManager,
        StorageInterface $storage,
        CollectionRepositoryInterface $collectionRepository,
        SecurityCheckerInterface $securityChecker,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        string $mediaClass,
        string $collectionClass
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->mediaManager = $mediaManager;
        $this->formatManager = $formatManager;
        $this->restHelper = $restHelper;
        $this->doctrineListBuilderFactory = $doctrineListBuilderFactory;
        $this->entityManager = $entityManager;
        $this->storage = $storage;
        $this->collectionRepository = $collectionRepository;
        $this->securityChecker = $securityChecker;
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->mediaClass = $mediaClass;
        $this->collectionClass = $collectionClass;
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
            $view = $this->responseGetById(
                $id,
                function($id) use ($locale) {
                    $media = $this->mediaManager->getById($id, $locale);
                    $collection = $media->getEntity()->getCollection();

                    if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collection->getType()->getKey()) {
                        $this->securityChecker->checkPermission(
                            'sulu.media.system_collections',
                            PermissionTypes::VIEW
                        );
                    }

                    $this->securityChecker->checkPermission(
                        new SecurityCondition(
                            $this->getSecurityContext(),
                            $locale,
                            $this->getSecuredClass(),
                            $collection->getId()
                        ),
                        PermissionTypes::VIEW
                    );

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
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('media');
        $types = array_filter(explode(',', $request->get('types')));
        $listBuilder = $this->getListBuilder($request, $fieldDescriptors, $types);
        $listBuilder->setParameter('locale', $locale);
        $listResponse = $listBuilder->execute();
        $count = $listBuilder->count();

        for ($i = 0, $length = count($listResponse); $i < $length; ++$i) {
            $format = $this->formatManager->getFormats(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['version'],
                $listResponse[$i]['subVersion'],
                $listResponse[$i]['mimeType']
            );

            if (0 < count($format)) {
                $listResponse[$i]['thumbnails'] = $format;
            }

            $listResponse[$i]['url'] = $this->mediaManager->getUrl(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['version']
            );
        }

        $ids = $listBuilder->getIds();
        if (null != $ids) {
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
            'sulu_media.cget_media',
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
     * @param array $types
     *
     * @return DoctrineListBuilder
     */
    private function getListBuilder(Request $request, array $fieldDescriptors, $types)
    {
        $listBuilder = $this->doctrineListBuilderFactory->create($this->mediaClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // default sort by created
        if (!$request->get('sortBy')) {
            $listBuilder->sort($fieldDescriptors['created'], 'desc');
        }

        $collectionId = $request->get('collection');
        if ($collectionId) {
            $collectionType = $this->collectionRepository->findCollectionTypeById($collectionId);
            if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collectionType) {
                $this->securityChecker->checkPermission(
                    'sulu.media.system_collections',
                    PermissionTypes::VIEW
                );
            }
            $listBuilder->addSelectField($fieldDescriptors['collection']);
            $listBuilder->where($fieldDescriptors['collection'], $collectionId);
        } else {
            $listBuilder->addPermissionCheckField($fieldDescriptors['collection']);
            $listBuilder->setPermissionCheck(
                $this->getUser(),
                PermissionTypes::VIEW,
                $this->collectionClass
            );
        }

        // set the types
        if (count($types)) {
            $listBuilder->in($fieldDescriptors['type'], $types);
        }

        if (!$this->securityChecker->hasPermission('sulu.media.system_collections', PermissionTypes::VIEW)) {
            $systemCollection = $this->collectionRepository
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
        $delete = function($id) {
            try {
                $this->mediaManager->delete($id, true);
            } catch (MediaNotFoundException $e) {
                throw new EntityNotFoundException($this->mediaClass, $id); // will throw 404 Entity not found
            } catch (MediaException $e) {
                throw new RestException($e->getMessage(), $e->getCode()); // will throw 400 Bad Request
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @param $id
     * @param $version
     *
     * @throws \Sulu\Component\Rest\Exception\MissingParameterException
     */
    public function deleteVersionAction(Request $request, $id, $version)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $media = $this->mediaManager->getById($id, $locale);

        if ($media->getVersion() === (int) $version) {
            throw new BadRequestHttpException('Can\'t delete active version of a media.');
        }

        $currentFileVersion = null;

        /** @var Media $mediaEntity */
        foreach ($media->getFile()->getFileVersions() as $fileVersion) {
            if ($fileVersion->getVersion() === (int) $version) {
                $currentFileVersion = $fileVersion;
                break;
            }
        }

        if (!$currentFileVersion) {
            throw new NotFoundHttpException(sprintf(
                'Version "%s" for Media "%s"',
                $version,
                $id
            ));
        }

        $this->entityManager->remove($currentFileVersion);
        $this->entityManager->flush();
        // After successfully delete in the database remove file from storage
        $this->storage->remove($currentFileVersion->getStorageOptions());

        return new Response('', 204);
    }

    /**
     * Trigger an action for given media. Action is specified over get-action parameter.
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

            $media = $this->mediaManager->move(
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
            $data = $this->getData($request, null === $id);
            $data['id'] = $id;
            $uploadedFile = $this->getUploadedFile($request, 'fileVersion');
            $media = $this->mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

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
}
