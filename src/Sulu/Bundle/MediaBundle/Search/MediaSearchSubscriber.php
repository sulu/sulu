<?php

namespace Sulu\Bundle\MediaBundle\Search;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Bundle\SearchBundle\Search\SuluSearchEvents;
use Sulu\Bundle\SearchBundle\Search\Event\StructureMetadataLoadEvent;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;

class MediaSearchSubscriber implements EventSubscriberInterface
{
    protected $mediaManager;
    protected $requestAnalyzer;
    protected $searchImageFormat;

    public function __construct(MediaManagerInterface $mediaManager, RequestAnalyzerInterface $requestAnalyzer = null, $searchImageFormat)
    {
        $this->mediaManager = $mediaManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->searchImageFormat = $searchImageFormat;
    }

    public static function getSubscribedEvents()
    {
        return array(
            SuluSearchEvents::STRUCTURE_LOAD_METADATA => 'handleStructureLoadMetadata',
            SearchEvents::PRE_INDEX => 'handlePreIndex',
        );
    }

    public function handleStructureLoadMetadata(StructureMetadataLoadEvent $event)
    {
        $structure = $event->getStructure();
        $indexMetadata = $event->getIndexMetadata();

        $this->loadMetadata($structure, $indexMetadata);
    }

    public function handlePreIndex(PreIndexEvent $e)
    {
        $metadata = $e->getMetadata();
        $document = $e->getDocument();
        $subject = $e->getSubject();

        if (false === $metadata->reflection->isSubclassOf('Sulu\Component\Content\Structure')) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        if (!$imageUrlField = $metadata->getImageUrlField()) {
            return;
        }

        $media = $accessor->getValue($subject, $imageUrlField);
        $media = (array) $media;

        // always use the first media
        $media = current($media);

        // no media, no thumbnail URL
        if (!$media) {
            return;
        }

        $formats = $media->getThumbnails();

        if (!isset($formats[$this->searchImageFormat])) {
            throw new \InvalidArgumentException(sprintf('Search image format "%s" is not known', $this->searchImageFormat));
        }

        $document->setImageUrl($formats[$this->searchImageFormat]);
    }

    private function loadMetadata(StructureInterface $structure, IndexMetadata $indexMetadata)
    {
        foreach ($structure->getProperties(true) as $property) {
            if ($property->hasTag('sulu.search.field')) {
                $tag = $property->getTag('sulu.search.field');
                $attrs = $tag->getAttributes();
                if (isset($attrs['role']) && $attrs['role'] === 'image') {
                    $contentType = $property->getContentTypeName();
                    if ($contentType !== 'media_selection') {
                        throw new \InvalidArgumentException('Cannot use content type "%s" in search role "%s"', $contentType, $attrs['role']);
                    }
                    $indexMetadata->setImageUrlField($property->getName());
                }
            }
        }
    }
}
