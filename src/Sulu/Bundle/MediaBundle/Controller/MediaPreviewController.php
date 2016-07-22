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

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes medias preview images available through a REST API.
 *
 * @RouteResource("Preview")
 */
class MediaPreviewController extends AbstractMediaController implements ClassResourceInterface
{
    /**
     * Creates a new preview image and saves it to the provided media.
     *
     * @Post("media/{id}/preview")
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException
     */
    public function postAction($id, Request $request)
    {
        try {
            $mediaManager = $this->getMediaManager();
            $systemCollectionManager = $this->get('sulu_media.system_collections.manager');

            $locale = $this->getLocale($request);

            $media = $mediaManager->getById($id, $locale);
            /** @var MediaInterface $mediaEntity */
            $mediaEntity = $media->getEntity();

            $data = $this->getData($request, false);

            // Unset id to not overwrite original file
            unset($data['id']);

            if ($mediaEntity->getPreviewImage() !== null) {
                $data['id'] = $mediaEntity->getPreviewImage()->getId();
            }
            $data['collection'] = $systemCollectionManager->getSystemCollection('sulu_media.preview_image');
            $data['locale'] = $locale;
            $data['title'] = $media->getTitle();

            $uploadedFile = $this->getUploadedFile($request, 'previewImage');
            $previewImage = $mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $mediaEntity->setPreviewImage($previewImage->getEntity());

            $this->getDoctrine()->getManager()->flush();

            $view = $this->view($previewImage, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Removes current preview image and sets default video thumbnail.
     *
     * @Delete("media/{id}/preview")
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, Request $request)
    {
        try {
            $mediaManager = $this->getMediaManager();

            $locale = $this->getLocale($request);

            $media = $mediaManager->getById($id, $locale);
            /** @var MediaInterface $mediaEntity */
            $mediaEntity = $media->getEntity();

            if ($mediaEntity->getPreviewImage() !== null) {
                $oldPreviewImageId = $mediaEntity->getPreviewImage()->getId();

                $mediaEntity->setPreviewImage(null);

                $mediaManager->delete($oldPreviewImageId);
            }

            $view = $this->view(null, 204);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }
}
