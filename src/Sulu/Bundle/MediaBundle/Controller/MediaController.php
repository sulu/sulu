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
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ReferencingResourcesFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes media available through a REST API.
 */
class MediaController extends AbstractMediaController implements
    SecuredControllerInterface,
    SecuredObjectControllerInterface
{
    /**
     * @param class-string $mediaClass
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private MediaManagerInterface $mediaManager,
        private EntityManagerInterface $entityManager,
        private StorageInterface $storage,
        private SecurityCheckerInterface $securityChecker,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private string $mediaClass,
        private MediaListBuilderFactory $mediaListBuilderFactory,
        private MediaListRepresentationFactory $mediaListRepresentationFactory,
        private ?ReferenceRepositoryInterface $referenceRepository = null,
        private ?RequestStack $requestStack = null,
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        if (null === $this->referenceRepository) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.6.28',
                'Instantiating MediaController without the $referenceRepository argument is deprecated.'
            );
        }

        if (null === $this->requestStack) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.6.28',
                'Instantiating MediaController without the $requestStack argument is deprecated, deleting a referenced media is not checked without it.'
            );
        }
    }

    /**
     * Shows a single media with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getLocale($request);
        if (null === $locale) {
            throw new BadRequestException('Missing "locale" in the query string');
        }

        try {
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
        }

        return $this->handleView($view);
    }

    /**
     * Lists all media.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        /** @var UserInterface $user */
        $user = $this->getUser();
        $types = \array_filter(\explode(',', $request->query->getString('types', '')));

        $collectionId = $request->query->get('collection');
        $collectionId = $collectionId ? (int) $collectionId : null;

        $locale = $this->getLocale($request);
        if (null === $locale) {
            throw new BadRequestException('Missing "locale" in the query string');
        }

        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('media');
        $listBuilder = $this->mediaListBuilderFactory->getListBuilder(
            $fieldDescriptors,
            $user,
            $types,
            !$request->query->get('sortBy'),
            $collectionId
        );

        $listRepresentation = $this->mediaListRepresentationFactory->getListRepresentation(
            $listBuilder,
            $locale,
            MediaInterface::RESOURCE_KEY,
            'sulu_media.cget_media',
            $request->query->all()
        );

        $view = $this->view($listRepresentation, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new media.
     *
     * @return Response
     *
     * @throws CollectionNotFoundException
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing media with the given id.
     *
     * @param int $id The id of the media to update
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
     * Delete a media with the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $request = $this->requestStack?->getCurrentRequest();

        if (null !== $request && !$request->query->getBoolean('force', false)) {
            $referencingResources = $this->getReferencingResources($id);

            if (\count($referencingResources) > 0) {
                throw new ReferencingResourcesFoundException(
                    [
                        'id' => (int) $id,
                        'resourceKey' => MediaInterface::RESOURCE_KEY,
                        'title' => $this->getMediaTitle($id, $request),
                    ],
                    $referencingResources,
                    \count($referencingResources)
                );
            }
        }

        $delete = function($id) {
            try {
                $this->mediaManager->delete($id, true);
            } catch (MediaNotFoundException $e) {
                throw new EntityNotFoundException($this->mediaClass, $id, $e); // will throw 404 Entity not found
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param int|string $id
     */
    private function getMediaTitle($id, Request $request): ?string
    {
        $locale = $request->query->getString('locale');

        if (!$locale) {
            return null;
        }

        try {
            $media = $this->mediaManager->getById((int) $id, $locale);
        } catch (MediaNotFoundException) {
            return null;
        }

        return $media->getTitle() ?: $media->getName();
    }

    /**
     * @param int|string $id
     *
     * @return array<array{id: int|string, resourceKey: string, title: string|null}>
     */
    private function getReferencingResources($id): array
    {
        if (null === $this->referenceRepository) {
            return [];
        }

        $referencingResources = [];
        $references = $this->referenceRepository->findFlatBy(
            [
                'resourceKey' => MediaInterface::RESOURCE_KEY,
                'resourceId' => (string) $id,
            ],
            [],
            ['referenceResourceKey', 'referenceResourceId', 'referenceTitle'],
            true
        );

        foreach ($references as $reference) {
            if (!isset($reference['referenceResourceId'], $reference['referenceResourceKey'], $reference['referenceTitle'])) {
                continue;
            }

            // one reference per locale
            $key = $reference['referenceResourceKey'] . '::' . $reference['referenceResourceId'];
            if (isset($referencingResources[$key])) {
                continue;
            }

            $referencingResources[$key] = [
                'id' => $reference['referenceResourceId'],
                'resourceKey' => $reference['referenceResourceKey'],
                'title' => $reference['referenceTitle'],
            ];
        }

        return \array_values($referencingResources);
    }

    /**
     * @param int $id
     * @param string $version
     *
     * @return Response
     *
     * @throws MissingParameterException
     */
    public function deleteVersionAction($id, $version)
    {
        $this->mediaManager->removeFileVersion((int) $id, (int) $version);

        return new Response('', 204);
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
        $action = $request->query->getString('action') ?: throw new MissingParameterException(self::class, 'action');

        try {
            return match ($action) {
                'move' => $this->moveEntity($id, $request),
                'new-version' => $this->saveEntity($id, $request),
                default => throw new RestException(\sprintf('Unrecognized action: "%s"', $action)),
            };
        } catch (RestException $e) {
            $view = $this->view($e->toArray(), 400);

            return $this->handleView($view);
        }
    }

    /**
     * Move an entity to another collection.
     *
     * @param int $id
     *
     * @return Response
     */
    protected function moveEntity($id, Request $request)
    {
        $locale = $this->getLocale($request);
        if (null === $locale) {
            throw new BadRequestException('Missing "locale" in the query string');
        }

        $destination = $request->query->getInt('destination')
            ?: throw new MissingParameterException(self::class, 'destination');

        try {
            $media = $this->mediaManager->move(
                $id,
                $locale,
                $destination
            );

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * @param int|null $id
     *
     * @return Response
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
        }

        return $this->handleView($view);
    }

    /**
     * @return string
     */
    public function getSecurityContext()
    {
        return MediaAdmin::SECURITY_CONTEXT;
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
     * @return string
     */
    public function getSecuredObjectId(Request $request)
    {
        $collection = $request->query->getString('collection');
        if ('' === $collection) {
            $collection = $request->getPayload()->getString('collection');
        }

        return $collection;
    }
}
