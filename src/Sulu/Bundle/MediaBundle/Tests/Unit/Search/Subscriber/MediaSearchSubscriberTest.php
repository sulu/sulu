<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Search\Subscriber;

use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Bundle\MediaBundle\Search\Subscriber\MediaSearchSubscriber;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sulu\Bundle\SearchBundle\Search\Document;
use Prophecy\Argument;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;

class MediaSearchSubscriberTest extends ProphecyTestCase
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

    public function setUp()
    {
        parent::setUp();
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->subscriber = new MediaSearchSubscriber(
            $this->mediaManager->reveal(),
            'test_format'
        );

        $this->indexMetadata = $this->prophesize(IndexMetadata::class);
        $this->metadata = $this->prophesize(ClassMetadata::class);
        $this->fileVersionMeta = $this->prophesize(FileVersionMeta::class);
        $this->fileVersion = $this->prophesize(FileVersion::class);
        $this->file = $this->prophesize(File::class);
        $this->media = $this->prophesize(Media::class);
        $this->event = $this->prophesize(PreIndexEvent::class);
        $this->document = $this->prophesize(Document::class);
        $this->reflection = $this->prophesize(\ReflectionClass::class);

        $this->event->getMetadata()->willReturn($this->indexMetadata->reveal());
        $this->event->getDocument()->willReturn($this->document->reveal());

        $this->fileVersionMeta->getFileVersion()->willReturn($this->fileVersion->reveal());
        $this->fileVersion->getFile()->willReturn($this->file->reveal());
        $this->file->getMedia()->willReturn($this->media->reveal());
        $this->indexMetadata->getClassMetadata()->willReturn($this->metadata);
        $this->metadata->reflection = $this->reflection;
    }

    public function testNotMedia()
    {
        $this->indexMetadata->getName()->willReturn('Foo');
        $this->event->getSubject()->willReturn(new \stdClass);
        $this->reflection->isSubclassOf(FileVersionMeta::class)->willReturn(false);
        $this->fileVersionMeta->getFileVersion()->shouldNotBeCalled();

        $this->subscriber->handlePreIndex($this->event->reveal());
    }

    public function testSubscriber()
    {
        $this->reflection->isSubclassOf(FileVersionMeta::class)->willReturn(true);
        $this->metadata->getName()->willReturn(FileVersionMeta::class);
        $this->event->getSubject()->willReturn($this->fileVersionMeta->reveal());
        $this->fileVersionMeta->getLocale()->willReturn('de');
        $this->mediaManager->addFormatsAndUrl(Argument::any())->will(function ($args) {
            $mediaApi = $args[0];
            $mediaApi->setFormats(array(
                'test_format' => 'foo',
            ));
        });

        $this->document->setImageUrl('foo')->shouldBeCalled();

        $this->subscriber->handlePreIndex($this->event->reveal());
    }
}
