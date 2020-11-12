<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes accounts available through a REST API.
 */
abstract class AbstractMediaController extends AbstractRestController
{
    protected static $mediaEntityKey = 'media';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var MediaListRepresentationFactory
     */
    private $mediaListRepresentationFactory;

    /**
     * @var string
     */
    private $mediaClass;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        MediaRepositoryInterface $mediaRepository,
        MediaListRepresentationFactory $mediaListRepresentationFactory,
        string $mediaClass
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->entityManager = $entityManager;
        $this->mediaRepository = $mediaRepository;
        $this->mediaListRepresentationFactory = $mediaListRepresentationFactory;
        $this->mediaClass = $mediaClass;
    }

    /**
     * Adds a relation between a media and the entity.
     *
     * @param string $entityName
     * @param string $id
     * @param string $mediaId
     *
     * @return Media
     */
    protected function addMediaToEntity($entityName, $id, $mediaId)
    {
        try {
            $em = $this->entityManager;
            $entity = $em->getRepository($entityName)->find($id);
            $media = $this->mediaRepository->find($mediaId);

            if (!$entity) {
                throw new EntityNotFoundException($entityName, $id);
            }

            if (!$media) {
                throw new EntityNotFoundException($this->mediaClass, $mediaId);
            }

            if ($entity->getMedias()->contains($media)) {
                throw new RestException('Relation already exists');
            }

            $entity->addMedia($media);
            $em->flush();

            $view = $this->view(
                new Media(
                    $media,
                    $this->getUser()->getLocale(),
                    null
                ),
                200
            );
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Removes a media from the relation with an entity.
     *
     * @param string $entityName
     * @param string $id
     * @param string $mediaId
     *
     * @return Response
     */
    protected function removeMediaFromEntity($entityName, $id, $mediaId)
    {
        try {
            $delete = function() use ($entityName, $id, $mediaId) {
                $entity = $this->entityManager->getRepository($entityName)->find($id);
                $media = $this->mediaRepository->find($mediaId);

                if (!$entity) {
                    throw new EntityNotFoundException($entityName, $id);
                }

                if (!$media) {
                    throw new EntityNotFoundException($this->mediaClass, $mediaId);
                }

                if (!$entity->getMedias()->contains($media)) {
                    throw new RestException(
                        'Relation between ' . $entityName .
                        ' and ' . $this->mediaClass . ' with id ' . $mediaId . ' does not exists!'
                    );
                }

                $entity->removeMedia($media);
                $this->entityManager->flush();
            };

            $view = $this->responseDelete($id, $delete);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a view containing all media of an entity.
     *
     * @param string $entityName
     * @param string $routeName
     * @param Request $request
     *
     * @return Response
     */
    protected function getMultipleView($entityName, $routeName, AbstractContactManager $contactManager, $contactId, $request)
    {
        try {
            /** @var UserInterface $user */
            $user = $this->getUser();
            $locale = $this->getUser()->getLocale();

            if ('true' === $request->get('flat')) {
                $fieldDescriptors = $this->mediaListRepresentationFactory->getFieldDescriptors();

                $fieldDescriptors['contactId'] = new DoctrineFieldDescriptor(
                    'id',
                    'contactId',
                    $entityName,
                    null,
                    [
                        $entityName => new DoctrineJoinDescriptor(
                            $entityName,
                            $entityName,
                            $entityName . '.id = :contactId'
                        ),
                        static::$mediaEntityKey => new DoctrineJoinDescriptor(
                            static::$mediaEntityKey,
                            $entityName . '.medias',
                            static::$mediaEntityKey . '.id = ' . $this->mediaClass . '.id',
                            DoctrineJoinDescriptor::JOIN_METHOD_INNER
                        ),
                    ],
                    FieldDescriptorInterface::VISIBILITY_NEVER,
                    FieldDescriptorInterface::SEARCHABILITY_NEVER
                );

                $listBuilder = $this->mediaListRepresentationFactory->getListBuilder(
                    $fieldDescriptors,
                    $user,
                    [],
                    !$request->get('sortBy'),
                    null
                );

                $listBuilder->setParameter('contactId', $contactId);
                $listBuilder->where($fieldDescriptors['contactId'], $contactId);

                $listRepresentation = $this->mediaListRepresentationFactory->getListRepresentation(
                    $listBuilder,
                    $locale,
                    static::$mediaEntityKey,
                    $routeName,
                    \array_merge(['contactId' => $contactId], $request->query->all())
                );
            } else {
                $media = $contactManager->getById($contactId, $locale)->getMedias();
                $listRepresentation = new CollectionRepresentation($media, static::$mediaEntityKey);
            }

            $view = $this->view($listRepresentation, 200);
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }
}
