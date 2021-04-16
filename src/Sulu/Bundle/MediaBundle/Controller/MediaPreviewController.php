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
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\EventLogBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaPreviewImageCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaPreviewImageModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaPreviewImageRemovedEvent;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
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

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        MediaManagerInterface $mediaManager,
        SystemCollectionManagerInterface $systemCollectionManager,
        EntityManagerInterface $entityManager,
        DomainEventCollectorInterface $domainEventCollector
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        $this->mediaManager = $mediaManager;
        $this->systemCollectionManager = $systemCollectionManager;
        $this->entityManager = $entityManager;
        $this->domainEventCollector = $domainEventCollector;
    }

    /**
     * Creates a new preview image and saves it to the provided media.
     *
     * @param int $id
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

            $oldPreviewImageId = null;
            if (null !== $mediaEntity->getPreviewImage()) {
                $oldPreviewImageId = $mediaEntity->getPreviewImage()->getId();
                $data['id'] = $oldPreviewImageId;
            }

            $data['collection'] = $this->systemCollectionManager->getSystemCollection('sulu_media.preview_image');
            $data['locale'] = $locale;
            $data['title'] = $media->getTitle();

            $uploadedFile = $this->getUploadedFile($request, 'previewImage');
            $previewImage = $this->mediaManager->save($uploadedFile, $data, $this->getUser()->getId());

            $mediaEntity->setPreviewImage($previewImage->getEntity());
            $this->mediaManager->addFormatsAndUrl($media);

            // Because the `MediaManager::save()` method calls `$entityManager->flush()` itself, the `created` event of
            // the preview image and the `preview_image_created`/`preview_image_modified` event are not in the same badge.
            if (null !== $oldPreviewImageId) {
                $this->domainEventCollector->collect(
                    new MediaPreviewImageModifiedEvent($mediaEntity, $previewImage->getEntity(), $oldPreviewImageId)
                );
            } else {
                $this->domainEventCollector->collect(
                    new MediaPreviewImageCreatedEvent($mediaEntity, $previewImage->getEntity())
                );
            }

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
                $this->mediaManager->addFormatsAndUrl($media);

                $this->domainEventCollector->collect(
                    new MediaPreviewImageRemovedEvent($mediaEntity, $oldPreviewImageId)
                );

                $this->mediaManager->delete($oldPreviewImageId);
            }

            $view = $this->view($media, 200);
        } catch (MediaNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }
}
