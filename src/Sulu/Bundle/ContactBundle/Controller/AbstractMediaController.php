<?php

/*
 * This file is part of Sulu.
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
    protected static $collectionEntityName = 'SuluMediaBundle:Collection';
    protected static $fileVersionEntityName = 'SuluMediaBundle:FileVersion';
    protected static $fileEntityName = 'SuluMediaBundle:File';
    protected static $fileVersionMetaEntityName = 'SuluMediaBundle:FileVersionMeta';
    protected static $mediaEntityKey = 'media';
    protected $fieldDescriptors = null;

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
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository($entityName)->find($id);
            $media = $this->container->get('sulu.repository.media')->find($mediaId);

            if (!$entity) {
                throw new EntityNotFoundException($entityName, $id);
            }

            if (!$media) {
                throw new EntityNotFoundException($this->getParameter('sulu.model.media.class'), $mediaId);
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
            $delete = function () use ($entityName, $id, $mediaId) {
                $em = $this->getDoctrine()->getManager();
                $entity = $em->getRepository($entityName)->find($id);
                $media = $this->container->get('sulu.repository.media')->find($mediaId);

                if (!$entity) {
                    throw new EntityNotFoundException($entityName, $id);
                }

                $mediaEntityName = $this->getParameter('sulu.model.media.class');

                if (!$media) {
                    throw new EntityNotFoundException($mediaEntityName, $mediaId);
                }

                if (!$entity->getMedias()->contains($media)) {
                    throw new RestException(
                        'Relation between ' . $entityName .
                        ' and ' . $mediaEntityName . ' with id ' . $mediaId . ' does not exists!'
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
     * @param string $entityName
     * @param string $routeName
     * @param AbstractContactManager $contactManager
     * @param string $id
     * @param Request $request
     *
     * @return Response
     */
    protected function getMultipleView($entityName, $routeName, AbstractContactManager $contactManager, $id, $request)
    {
        try {
            $locale = $this->getUser()->getLocale();

            if ($request->get('flat') === 'true') {
                /** @var RestHelperInterface $restHelper */
                $restHelper = $this->get('sulu_core.doctrine_rest_helper');

                /** @var DoctrineListBuilderFactory $factory */
                $factory = $this->get('sulu_core.doctrine_list_builder_factory');

                $listBuilder = $factory->create($entityName);
                $fieldDescriptors = $this->getFieldDescriptors($entityName, $id);
                $listBuilder->setIdField($fieldDescriptors['id']);
                $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

                $listResponse = $listBuilder->execute();
                $listResponse = $this->addThumbnails($listResponse, $locale);
                $listResponse = $this->addUrls($listResponse, $locale);

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
                $media = $contactManager->getById($id, $locale)->getMedias();
                $list = new CollectionRepresentation($media, self::$mediaEntityKey);
            }
            $view = $this->view($list, 200);
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Returns the the media fields for the current entity.
     *
     * @param $entityName
     *
     * @return Response
     */
    protected function getFieldsView($entityName)
    {
        return $this->handleView($this->view(array_values($this->getFieldDescriptors($entityName, null)), 200));
    }

    /**
     * Returns the field-descriptors. Ensures that the descriptors get only instantiated once.
     *
     * @param $entityName
     *
     * @return DoctrineFieldDescriptor[]
     */
    private function getFieldDescriptors($entityName, $id)
    {
        if ($this->fieldDescriptors === null) {
            $this->initFieldDescriptors($entityName, $id);
        }

        return $this->fieldDescriptors;
    }

    /**
     * Creates the array of field-descriptors.
     *
     * @param $entityName
     */
    private function initFieldDescriptors($entityName, $id)
    {
        $mediaEntityName = $this->getParameter('sulu.model.media.class');

        $entityJoin = new DoctrineJoinDescriptor(
            $mediaEntityName,
            $entityName . '.medias',
            $entityName . '.id = ' . $id,
            DoctrineJoinDescriptor::JOIN_METHOD_INNER
        );

        $this->fieldDescriptors = [];

        $this->fieldDescriptors['entity'] = new DoctrineFieldDescriptor(
            'id',
            'entity',
            $entityName,
            null,
            [],
            true,
            false
        );

        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            $mediaEntityName,
            'public.id',
            [
                $mediaEntityName => $entityJoin,
            ],
            true,
            false
        );

        $this->fieldDescriptors['thumbnails'] = new DoctrineFieldDescriptor(
            'id',
            'thumbnails',
            $mediaEntityName,
            'media.media.thumbnails',
            [
                $mediaEntityName => $entityJoin,
            ],
            false,
            true,
            'thumbnails',
            '',
            '',
            false
        );

        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$fileVersionEntityName,
            'public.name',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ]
        );
        $this->fieldDescriptors['size'] = new DoctrineFieldDescriptor(
            'size',
            'size',
            self::$fileVersionEntityName,
            'media.media.size',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ],
            false,
            true,
            'bytes'
        );

        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            self::$fileVersionEntityName,
            'public.changed',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ],
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            self::$fileVersionEntityName,
            'public.created',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
            ],
            true,
            false,
            'date'
        );

        $this->fieldDescriptors['title'] = new DoctrineFieldDescriptor(
            'title',
            'title',
            self::$fileVersionMetaEntityName,
            'public.title',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
                self::$fileVersionMetaEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionMetaEntityName,
                    self::$fileVersionEntityName . '.meta'
                ),
            ],
            false,
            true,
            'title'
        );

        $this->fieldDescriptors['description'] = new DoctrineFieldDescriptor(
            'description',
            'description',
            self::$fileVersionMetaEntityName,
            'media.media.description',
            [
                $mediaEntityName => $entityJoin,
                self::$fileEntityName => new DoctrineJoinDescriptor(
                    self::$fileEntityName,
                    $mediaEntityName . '.files'
                ),
                self::$fileVersionEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionEntityName,
                    self::$fileEntityName . '.fileVersions',
                    self::$fileVersionEntityName . '.version = ' . self::$fileEntityName . '.version'
                ),
                self::$fileVersionMetaEntityName => new DoctrineJoinDescriptor(
                    self::$fileVersionMetaEntityName,
                    self::$fileVersionEntityName . '.meta'
                ),
            ]
        );
    }

    /**
     * Takes an array of entities and resets the thumbnails-property containing the media id with
     * the actual urls to the thumbnails.
     *
     * @param array $entities
     * @param string $locale
     *
     * @return array
     */
    private function addThumbnails($entities, $locale)
    {
        $ids = array_filter(array_column($entities, 'thumbnails'));
        $thumbnails = $this->getMediaManager()->getFormatUrls($ids, $locale);
        foreach ($entities as $key => $entity) {
            if (array_key_exists('thumbnails', $entity)
                && $entity['thumbnails']
                && array_key_exists($entity['thumbnails'], $thumbnails)
            ) {
                $entities[$key]['thumbnails'] = $thumbnails[$entity['thumbnails']];
            }
        }

        return $entities;
    }

    /**
     * Takes an array of entities and resets the url-property with the actual urls to the original file.
     *
     * @param array $entities
     * @param string $locale
     *
     * @return array
     */
    private function addUrls($entities, $locale)
    {
        $ids = array_filter(array_column($entities, 'id'));
        $apiEntities = $this->getMediaManager()->getByIds($ids, $locale);
        $i = 0;
        foreach ($entities as $key => $entity) {
            $entities[$key]['url'] = $apiEntities[$i]->getUrl();
            $i += 1;
        }

        return $entities;
    }

    /**
     * @return MediaManagerInterface
     */
    private function getMediaManager()
    {
        return $this->get('sulu_media.media_manager');
    }
}
