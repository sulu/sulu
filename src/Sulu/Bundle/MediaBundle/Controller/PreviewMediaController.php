<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes medias preview images available through a REST API.
 */
class PreviewMediaController extends RestController implements ClassResourceInterface
{
    /**
     * Creates a new preview image and saves it to the provided media.
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
            $mediaEntity = $media->getEntity();

            $data = $this->getData($request, false);
            unset($data['id']);

            if ($mediaEntity->getPreviewImage() !== null) {
                $data['id'] = $mediaEntity->getPreviewImage()->getId();
            }
            $data['collection'] = $systemCollectionManager->getSystemCollection('sulu_media.preview_img');
            $data['locale'] = $locale;
            $data['title'] = $media->getTitle();

            $uploadedFile = $this->getUploadedFile($request, 'previewImg');
            $previewImg = $mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $mediaEntity->setPreviewImage($previewImg->getEntity());
            $mediaManager->saveEntity($mediaEntity);

            // Add preview thumbnails
            $media = $mediaManager->addFormatsAndUrl($media);

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        } catch (MediaException $e) {
            $view = $this->view($e->toArray(), 400);
        }

        return $this->handleView($view);
    }
}
