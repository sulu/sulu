<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Search\Subscriber;

use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber populates the image URL field
 * when a Structure containing an image field is indexed.
 */
class MediaSearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var MediaManagerInterface
     */
    protected $mediaManager;

    /**
     * The format of the image, which will be returned in the search.
     *
     * @var string
     */
    protected $searchImageFormat;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $thumbnailMimeTypes;

    /**
     * @param MediaManagerInterface $mediaManager
     * @param Factory               $factory      Massive search factory
     * @param LoggerInterface       $logger
     * @param $thumbnailMimeTypes
     * @param $searchImageFormat
     */
    public function __construct(
        MediaManagerInterface $mediaManager,
        Factory $factory,
        LoggerInterface $logger,
        $thumbnailMimeTypes,
        $searchImageFormat
    ) {
        $this->mediaManager = $mediaManager;
        $this->factory = $factory;
        $this->searchImageFormat = $searchImageFormat;
        $this->thumbnailMimeTypes = $thumbnailMimeTypes;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SearchEvents::PRE_INDEX => 'handlePreIndex',
        ];
    }

    /**
     * Adds the image to the search document.
     *
     * @param PreIndexEvent $event
     */
    public function handlePreIndex(PreIndexEvent $event)
    {
        $metadata = $event->getMetadata();
        $reflection = $metadata->getClassMetadata()->reflection;

        if (
            false === $reflection->isSubclassOf(FileVersionMeta::class)
            && $metadata->getName() !== FileVersionMeta::class) {
            return;
        }

        $document = $event->getDocument();
        $subject = $event->getSubject();
        $locale = $subject->getLocale();

        $fileVersion = $subject->getFileVersion();
        $file = $fileVersion->getFile();
        $media = $file->getMedia();

        // Do not try and get the image URL if the mime type is not in the
        // list of mime types for which thumbnails are generated.
        foreach ($this->thumbnailMimeTypes as $type) {
            if (fnmatch($type, $fileVersion->getMimeType())) {
                $document->setImageUrl($this->getImageUrl($media, $locale));
                break;
            }
        }

        $document->addField($this->factory->createField(
            'media_id',
            $media->getId()
        ));

        $document->addField($this->factory->createField(
            'media_mime',
            $fileVersion->getMimeType()
        ));

        if ($collection = $media->getCollection()) {
            $document->addField($this->factory->createField(
                'collection_id',
                $collection->getId()
            ));
        }
    }

    /**
     * Return the image URL for the given media.
     *
     * TODO: The media API needs to be improved here.
     */
    private function getImageUrl($media, $locale)
    {
        $mediaApi = new Media($media, $locale);
        $this->mediaManager->addFormatsAndUrl($mediaApi);
        $formats = $mediaApi->getThumbnails();

        if (!isset($formats[$this->searchImageFormat])) {
            $this->logger->warning(sprintf(
                'Media with ID "%s" does not have thumbnail format "%s". This thumbnail would be used by the search results.',
                $media->getId(),
                $this->searchImageFormat
            ));

            return;
        }

        return $formats[$this->searchImageFormat];
    }
}
