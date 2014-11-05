<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Search;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;

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
     * @var RequestAnalyzerInterface
     */
    protected $requestAnalyzer;

    /**
     * The format of the image, which will be returned in the search
     * @var string
     */
    protected $searchImageFormat;

    /**
     * @param MediaManagerInterface $mediaManager
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param $searchImageFormat
     */
    public function __construct(
        MediaManagerInterface $mediaManager,
        RequestAnalyzerInterface $requestAnalyzer = null,
        $searchImageFormat
    ) {
        $this->mediaManager = $mediaManager;
        $this->requestAnalyzer = $requestAnalyzer;
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
    public function handlePreIndex(PreIndexEvent $e)
    {
        $metadata = $e->getMetadata();
        $document = $e->getDocument();
        $subject = $e->getSubject();

        if (false === $metadata->reflection->isSubclassOf('Sulu\Component\Content\Structure')) {
            return;
        }

        if (!$imageUrlField = $metadata->getImageUrlField()) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $data = $accessor->getValue($subject, $imageUrlField);
        $locale = $subject->getLanguageCode();

        if (!$data) {
            return;
        }

        $imageUrl = $this->getImageUrl($data, $locale);
        $document->setImageUrl($imageUrl);
    }

    /**
     * Returns the url for the image
     * @param $data
     * @param $locale
     * @return null
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function getImageUrl($data, $locale)
    {
        // new structures will container an instance of MediaSelectionContainer
        if ($data instanceof MediaSelectionContainer) {
            $medias = $data->getData('de');
            // old ones an array ...
        } else {
            if (!isset($data['ids'])) {
                throw new \RuntimeException(
                    'Was expecting media value to contain array key "ids", got: "%s"',
                    print_r($data, true)
                );
            }

            $medias = array();
            foreach ($data['ids'] as $mediaId) {
                $medias[] = $this->mediaManager->getById($mediaId, $locale);
            }
        }

        // no media, no thumbnail URL
        if (!$medias) {
            return null;
        }

        $media = current($medias);

        if (!$media) {
            return null;
        }

        $formats = $media->getThumbnails();

        if (!isset($formats[$this->searchImageFormat])) {
            throw new \InvalidArgumentException(
                sprintf('Search image format "%s" is not known', $this->searchImageFormat)
            );
        }

        return $formats[$this->searchImageFormat];
    }
}
