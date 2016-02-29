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
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber populates the image URL field
 * when a Structure containing an image field is indexed.
 */
class StructureMediaSearchSubscriber implements EventSubscriberInterface
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
     * The format of the image, which will be returned in the search.
     *
     * @var string
     */
    protected $searchImageFormat;

    /**
     * @param MediaManagerInterface    $mediaManager
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
     * Returns the events this subscriber has subscribed.
     *
     * @return array
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
     * @param PreIndexEvent $e
     */
    public function handlePreIndex(PreIndexEvent $e)
    {
        $metadata = $e->getMetadata();
        $document = $e->getDocument();
        $subject = $e->getSubject();
        $evaluator = $e->getFieldEvaluator();

        if (false === $metadata->getClassMetadata()->reflection->isSubclassOf(StructureBehavior::class)) {
            return;
        }

        if (!$imageUrlField = $metadata->getImageUrlField()) {
            return;
        }

        $data = $evaluator->getValue($subject, $imageUrlField);
        $locale = $subject->getLocale();

        if (!$data) {
            $document->setImageUrl(null);

            return;
        }

        $imageUrl = $this->getImageUrl($data, $locale);
        $document->setImageUrl($imageUrl);
    }

    /**
     * Returns the url for the image.
     *
     * @param $data
     * @param $locale
     *
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
                    sprintf('Was expecting media value to contain array key "ids", got: "%s"', print_r($data, true))
                );
            }

            $medias = $this->mediaManager->get($locale, [
                'ids' => $data['ids'],
            ]);
        }

        // no media, no thumbnail URL
        if (!$medias) {
            return;
        }

        $media = current($medias);

        if (!$media) {
            return;
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
