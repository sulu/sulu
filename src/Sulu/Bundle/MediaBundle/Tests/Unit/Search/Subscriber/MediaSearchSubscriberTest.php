<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Search\Subscriber;

use Doctrine\ORM\Mapping\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Search\Subscriber\MediaSearchSubscriber;
use Sulu\Bundle\SearchBundle\Search\Document;

class MediaSearchSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $mediaManager;
    private $subscriber;
    private $metadata;
    private $indexMetadata;
    private $fileVersionMeta;
    private $fileVersion;
    private $file;
    private $media;
    private $event;
    private $document;
    private $reflection;
    private $factory;

    public function setUp()
    {
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->factory = $this->prophesize(Factory::class);
        $this->subscriber = new MediaSearchSubscriber(
            $this->mediaManager->reveal(),
            $this->factory->reveal(),
            $this->logger->reveal(),
            ['image/jpeg'],
            'test_format'
        );

        $this->indexMetadata = $this->prophesize(IndexMetadata::class);
        $this->metadata = $this->prophesize(ClassMetadata::class);
        $this->fileVersionMeta = $this->prophesize(FileVersionMeta::class);
        $this->fileVersion = $this->prophesize(FileVersion::class);
        $this->file = $this->prophesize(File::class);
        $this->media = $this->prophesize(Media::class);
        $this->collection = $this->prophesize(Collection::class);
        $this->event = $this->prophesize(PreIndexEvent::class);
        $this->document = $this->prophesize(Document::class);
        $this->reflection = $this->prophesize(\ReflectionClass::class);

        $this->field1 = $this->prophesize(Field::class);
        $this->field2 = $this->prophesize(Field::class);
        $this->field3 = $this->prophesize(Field::class);

        $this->event->getMetadata()->willReturn($this->indexMetadata->reveal());
        $this->event->getDocument()->willReturn($this->document->reveal());

        $this->fileVersionMeta->getFileVersion()->willReturn($this->fileVersion->reveal());
        $this->fileVersion->getFile()->willReturn($this->file->reveal());
        $this->file->getMedia()->willReturn($this->media->reveal());
        $this->indexMetadata->getClassMetadata()->willReturn($this->metadata);
        $this->metadata->reflection = $this->reflection;
    }

    /**
     * It should return early if the entity is not a FileVersionMeta instance.
     */
    public function testNotMedia()
    {
        $this->indexMetadata->getName()->willReturn('Foo');
        $this->event->getSubject()->willReturn(new \stdClass());
        $this->reflection->isSubclassOf(FileVersionMeta::class)->willReturn(false);
        $this->fileVersionMeta->getFileVersion()->shouldNotBeCalled();

        $this->subscriber->handlePreIndex($this->event->reveal());
    }

    /**
     * It should set the image URL, ID and mime type.
     */
    public function testSubscriber()
    {
        $this->setupSubscriber(
            123,
            'image/jpeg',
            321
        );
        $imageUrl = 'foo';

        $this->mediaManager->addFormatsAndUrl(Argument::any())->will(function ($args) use ($imageUrl) {
            $mediaApi = $args[0];
            $mediaApi->setFormats([
                'test_format' => $imageUrl,
            ]);
        });

        $this->document->setImageUrl($imageUrl)->shouldBeCalled();
        $this->document->addField($this->field1->reveal())->shouldBeCalled();
        $this->document->addField($this->field2->reveal())->shouldBeCalled();
        $this->document->addField($this->field3->reveal())->shouldBeCalled();

        $this->subscriber->handlePreIndex($this->event->reveal());
    }

    /**
     * It should log a warning if the media does not have a thumbnail.
     */
    public function testSubscriberNoThumbnailLog()
    {
        $this->setupSubscriber(
            123,
            'image/jpeg',
            321
        );

        $this->mediaManager->addFormatsAndUrl(Argument::any())->will(function ($args) {
            $mediaApi = $args[0];
            $mediaApi->setFormats([
                'for' => '/fo',
            ]);
        });

        $this->document->setImageUrl(null)->shouldBeCalled();
        $this->document->addField($this->field1->reveal())->shouldBeCalled();
        $this->document->addField($this->field2->reveal())->shouldBeCalled();
        $this->document->addField($this->field3->reveal())->shouldBeCalled();
        $this->logger->warning(Argument::any())->shouldBeCalled();

        $this->subscriber->handlePreIndex($this->event->reveal());
    }

    /**
     * It should set the image URL to NULL if the media is not in the list of medias with
     * thumbnails.
     */
    public function testSubscriberNotImage()
    {
        $this->setupSubscriber(
            123,
            'video/mpeg',
            321
        );

        $this->mediaManager->addFormatsAndUrl(Argument::any())->will(function ($args) {
            $mediaApi = $args[0];
            $mediaApi->setFormats([]);
        });

        $this->document->setImageUrl(Argument::any())->shouldNotBeCalled();
        $this->document->addField($this->field1->reveal())->shouldBeCalled();
        $this->document->addField($this->field2->reveal())->shouldBeCalled();
        $this->document->addField($this->field3->reveal())->shouldBeCalled();

        $this->logger->warning(Argument::any())->shouldNotBeCalled();

        $this->subscriber->handlePreIndex($this->event->reveal());
    }

    private function setupSubscriber($mediaId, $mediaMime, $collectionId)
    {
        $this->reflection->isSubclassOf(FileVersionMeta::class)->willReturn(true);
        $this->metadata->getName()->willReturn(FileVersionMeta::class);
        $this->event->getSubject()->willReturn($this->fileVersionMeta->reveal());
        $this->fileVersionMeta->getLocale()->willReturn('de');
        $this->media->getId()->willReturn($mediaId);
        $this->media->getCollection()->willReturn($this->collection->reveal());
        $this->collection->getId()->willReturn($collectionId);
        $this->fileVersion->getMimeType()->willReturn($mediaMime);
        $this->factory->createField('media_id', $mediaId)->willReturn($this->field1->reveal());
        $this->factory->createField('media_mime', $mediaMime)->willReturn($this->field2->reveal());
        $this->factory->createField('collection_id', $collectionId)->willReturn($this->field3->reveal());
    }
}
