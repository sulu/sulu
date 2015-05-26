<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;

/**
 * Makes accounts available through a REST API.
 */
abstract class AbstractMediaController extends RestController
{
    protected static $mediaEntityName = 'SuluMediaBundle:Media';

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
}
