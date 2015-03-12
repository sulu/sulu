<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Search\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Api\Media;

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
     * The format of the image, which will be returned in the search
     * @var string
     */
    protected $searchImageFormat;

    /**
     * @param MediaManagerInterface $mediaManager
     * @param $searchImageFormat
     */
    public function __construct(
        MediaManagerInterface $mediaManager,
        $searchImageFormat
    ) {
        $this->mediaManager = $mediaManager;
        $this->searchImageFormat = $searchImageFormat;
    }

    /**
     * Returns the events this subscriber has subscribed
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            SearchEvents::PRE_INDEX => 'handlePreIndex',
        );
    }

    /**
     * Adds the image to the search document
     * @param PreIndexEvent $e
     */
    public function handlePreIndex(PreIndexEvent $event)
    {
        $metadata = $event->getMetadata();

        if (
            false === $metadata->getClassMetadata()->reflection->isSubclassOf(FileVersionMeta::class)
            && $metadata->getName() !== FileVersionMeta::class)
        {
            return;
        }

        $document = $event->getDocument();
        $subject = $event->getSubject();
        $locale = $subject->getLocale();

        $fileVersion = $subject->getFileVersion();
        $file = $fileVersion->getFile();
        $media = $file->getMedia();

        $document->setImageUrl($this->getImageUrl($media, $locale));
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
            throw new \InvalidArgumentException(
                sprintf('Search image format "%s" is not known', $this->searchImageFormat)
            );
        }

        return $formats[$this->searchImageFormat];
    }
}
