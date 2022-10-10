<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Search\Subscriber\MediaSearchSubscriber;
use Sulu\Bundle\SearchBundle\Search\Document;

class MediaSearchSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MediaManagerInterface>
     */
    private $mediaManager;

    private $subscriber;

    /**
     * @var ObjectProphecy<ClassMetadata>
     */
    private $metadata;

    /**
     * @var ObjectProphecy<IndexMetadata>
     */
    private $indexMetadata;

    /**
     * @var ObjectProphecy<FileVersionMeta>
     */
    private $fileVersionMeta;

    /**
     * @var ObjectProphecy<FileVersion>
     */
    private $fileVersion;

    /**
     * @var ObjectProphecy<File>
     */
    private $file;

    /**
     * @var ObjectProphecy<Media>
     */
    private $media;

    /**
     * @var ObjectProphecy<PreIndexEvent>
     */
    private $event;

    /**
     * @var ObjectProphecy<Document>
     */
    private $document;

    private $reflection;

    /**
     * @var ObjectProphecy<Factory>
     */
    private $factory;

    public function setUp(): void
    {
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->factory = $this->prophesize(Factory::class);
        $this->subscriber = new MediaSearchSubscriber(
            $this->mediaManager->reveal(),
            $this->factory->reveal(),
            ['image/jpeg'],
            'test_format',
            $this->logger->reveal()
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

        $this->field1 = $this->prophesize(Field::class);
        $this->field2 = $this->prophesize(Field::class);
        $this->field3 = $this->prophesize(Field::class);

        $this->event->getMetadata()->willReturn($this->indexMetadata->reveal());
        $this->event->getDocument()->willReturn($this->document->reveal());

        $this->fileVersionMeta->getFileVersion()->willReturn($this->fileVersion->reveal());
        $this->fileVersion->getFile()->willReturn($this->file->reveal());
        $this->file->getMedia()->willReturn($this->media->reveal());
        $this->indexMetadata->getClassMetadata()->willReturn($this->metadata);
    }

    /**
     * It should return early if the entity is not a FileVersionMeta instance.
     */
    public function testNotMedia(): void
    {
        $this->indexMetadata->getName()->willReturn('Foo');
        $this->event->getSubject()->willReturn(new \stdClass());
        $this->fileVersionMeta->getFileVersion()->shouldNotBeCalled();

        $this->subscriber->handlePreIndex($this->event->reveal());
    }

    /**
     * It should set the image URL, ID and mime type.
     */
    public function testSubscriber(): void
    {
        $this->setupSubscriber(
            123,
            'image/jpeg',
            321
        );
        $imageUrl = 'foo';

        $this->mediaManager->addFormatsAndUrl(Argument::any())->will(function($args) use ($imageUrl): void {
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
    public function testSubscriberNoThumbnailLog(): void
    {
        $this->setupSubscriber(
            123,
            'image/jpeg',
            321
        );

        $this->mediaManager->addFormatsAndUrl(Argument::any())->will(function($args): void {
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
    public function testSubscriberNotImage(): void
    {
        $this->setupSubscriber(
            123,
            'video/mpeg',
            321
        );

        $this->mediaManager->addFormatsAndUrl(Argument::any())->will(function($args): void {
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

    private function setupSubscriber($mediaId, $mediaMime, $collectionId): void
    {
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
