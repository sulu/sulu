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
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaPreviewImageModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaPreviewImageRemovedEvent;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes medias preview images available through a REST API.
 */
class MediaPreviewController extends AbstractMediaController
{
    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        private MediaManagerInterface $mediaManager,
        private SystemCollectionManagerInterface $systemCollectionManager,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    /**
     * Creates a new preview image and saves it to the provided media.
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws CollectionNotFoundException
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

            /** @var MediaInterface|null $previousPreviewImage */
            $previousPreviewImage = $mediaEntity->getPreviewImage();
            $previousPreviewImageId = null;
            if (null !== $previousPreviewImage) {
                $previousPreviewImageId = $previousPreviewImage->getId();
                $data['id'] = $previousPreviewImageId;
            }

            $data['collection'] = $this->systemCollectionManager->getSystemCollection('sulu_media.preview_image');
            $data['locale'] = $locale;
            $data['title'] = $media->getTitle();

            $uploadedFile = $this->getUploadedFile($request, 'previewImage');
            $previewImage = $this->mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $mediaEntity->setPreviewImage($previewImage->getEntity());
            $this->mediaManager->addFormatsAndUrl($media);

            // Because the `MediaManager::save()` method calls `$entityManager->flush()` itself, the `created` event of
            // the preview image and the `preview_image_modified` event are not in the same batch.
            $this->domainEventCollector->collect(
                new MediaPreviewImageModifiedEvent($mediaEntity, $previewImage->getEntity(), $previousPreviewImageId)
            );

            $this->entityManager->flush();

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Removes current preview image and sets default video thumbnail.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id, Request $request)
    {
        try {
            $locale = $this->getLocale($request);

            $media = $this->mediaManager->getById($id, $locale);
            /** @var MediaInterface $mediaEntity */
            $mediaEntity = $media->getEntity();

            if (null !== $mediaEntity->getPreviewImage()) {
                $previousPreviewImageId = $mediaEntity->getPreviewImage()->getId();

                $mediaEntity->setPreviewImage(null);
                $this->mediaManager->addFormatsAndUrl($media);

                $this->domainEventCollector->collect(
                    new MediaPreviewImageRemovedEvent($mediaEntity, $previousPreviewImageId)
                );

                $this->mediaManager->delete($previousPreviewImageId);
            }

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }
}
