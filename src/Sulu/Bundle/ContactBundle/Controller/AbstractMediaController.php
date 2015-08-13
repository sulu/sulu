<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;

/**
 * Makes accounts available through a REST API.
 */
abstract class AbstractMediaController extends RestController
{
    protected static $mediaEntityName = 'SuluMediaBundle:Media';
    protected static $mediaEntityKey = 'media';

    /**
     * Adds a relation between a media and the entity.
     *
     * @param String $entityName
     * @param String $id
     * @param String $mediaId
     *
     * @return Media
     */
    protected function addMediaToEntity($entityName, $id, $mediaId)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository($entityName)->find($id);
            $media = $em->getRepository(self::$mediaEntityName)->find($mediaId);

            if (!$entity) {
                throw new EntityNotFoundException($entityName, $id);
            }

            if (!$media) {
                throw new EntityNotFoundException(self::$mediaEntityName, $mediaId);
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
     * @param String $entityName
     * @param String $id
     * @param String $mediaId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function removeMediaFromEntity($entityName, $id, $mediaId)
    {
        try {
            $delete = function () use ($entityName, $id, $mediaId) {
                $em = $this->getDoctrine()->getManager();
                $entity = $em->getRepository($entityName)->find($id);
                $media = $em->getRepository(self::$mediaEntityName)->find($mediaId);

                if (!$entity) {
                    throw new EntityNotFoundException($entityName, $id);
                }

                if (!$media) {
                    throw new EntityNotFoundException(self::$mediaEntityName, $mediaId);
                }

                if (!$entity->getMedias()->contains($media)) {
                    throw new RestException(
                        'Relation between ' . $entityName .
                        ' and ' . self::$mediaEntityName . ' with id ' . $mediaId . ' does not exists!'
                    );
                }

                $entity->removeMedia($media);
                $em->flush();
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
     * @param String $entityName
     * @param String $routeName
     * @param AbstractContactManager $entityManager
     * @param String $id
     * @param Boolean $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getMultipleView($entityName, $routeName, AbstractContactManager $entityManager, $id, $request)
    {
        $locale = $this->getUser()->getLocale();

        if ($request->get('flat') === 'true') {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create($entityName);
            $fieldDescriptors = $this->getFieldDescriptors($entityName);
            $listBuilder->where($fieldDescriptors['entity'], $id);
            $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

            $listResponse = $listBuilder->execute();
            $listResponse = $this->getMediaManager()->addThumbnails($listResponse, 'thumbnails', $locale);

            $list = new ListRepresentation(
                $listResponse,
                self::$mediaEntityKey,
                $routeName,
                array_merge(['id' => $id], $request->query->all()),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $media = $entityManager->getMediaById($id, $locale);
            $list = new CollectionRepresentation($media, self::$mediaEntityKey);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Returns the the media fields for the current entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getFieldsView()
    {
        return $this->handleView($this->view($this->getMediaManager()->getFieldDescriptors(), 200));
    }

    /**
     * Returns the field descriptors of the medias and adds a descriptor to connect
     * the media with the current entity.
     *
     * @param $entityName
     *
     * @return array
     */
    private function getFieldDescriptors($entityName)
    {
        $mediaDescriptors = $this->getMediaManager()->getFieldDescriptors();
        $additionalDescriptors = [
            'entity' => new DoctrineFieldDescriptor(
                'id',
                'entity',
                $entityName,
                null,
                [
                    self::$mediaEntityName => new DoctrineJoinDescriptor(
                        self::$mediaEntityName,
                        $entityName . '.medias',
                        null,
                        DoctrineJoinDescriptor::JOIN_METHOD_INNER
                    ),
                ]
            ),
        ];

        return array_merge($additionalDescriptors, $mediaDescriptors);
    }

    /**
     * @return MediaManagerInterface
     */
    private function getMediaManager()
    {
        return $this->get('sulu_media.media_manager');
    }
}
