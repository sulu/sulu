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
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes medias preview images available through a REST API.
 *
 * @RouteResource("Preview")
 */
class MediaPreviewController extends AbstractMediaController implements ClassResourceInterface
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var SystemCollectionManagerInterface
     */
    private $systemCollectionManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        MediaManagerInterface $mediaManager,
        SystemCollectionManagerInterface $systemCollectionManager,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->mediaManager = $mediaManager;
        $this->systemCollectionManager = $systemCollectionManager;
        $this->entityManager = $entityManager;
    }

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
            $locale = $this->getLocale($request);

            $media = $this->mediaManager->getById($id, $locale);
            /** @var MediaInterface $mediaEntity */
            $mediaEntity = $media->getEntity();

            $data = $this->getData($request, false);

            // Unset id to not overwrite original file
            unset($data['id']);

            if (null !== $mediaEntity->getPreviewImage()) {
                $data['id'] = $mediaEntity->getPreviewImage()->getId();
            }
            $data['collection'] = $this->systemCollectionManager->getSystemCollection('sulu_media.preview_image');
            $data['locale'] = $locale;
            $data['title'] = $media->getTitle();

            $uploadedFile = $this->getUploadedFile($request, 'previewImage');
            $previewImage = $this->mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $mediaEntity->setPreviewImage($previewImage->getEntity());

            $this->entityManager->flush();

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
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, Request $request)
    {
        try {
            $locale = $this->getLocale($request);

            $media = $this->mediaManager->getById($id, $locale);
            /** @var MediaInterface $mediaEntity */
            $mediaEntity = $media->getEntity();

            if (null !== $mediaEntity->getPreviewImage()) {
                $oldPreviewImageId = $mediaEntity->getPreviewImage()->getId();

                $mediaEntity->setPreviewImage(null);

                $this->mediaManager->delete($oldPreviewImageId);
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
