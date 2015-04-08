<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm\EventSubscriber;

use DTL\Component\Content\Document\DocumentInterface;
use DTL\Component\Content\PhpcrOdm\EventSubscriber\Marker\AutoNameMarker;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Cmf\Bundle\CoreBundle\Slugifier\SlugifierInterface;

class NameSubscriberTest extends ProphecyTestCase
{
    private $subscriber;
    private $documentManager;
    private $slugifier;
    private $document;
    private $parentDocument;
    private $classMetadata;
    private $unitOfWork;
    private $event;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->slugifier = $this->prophesize(SlugifierInterface::class);
        $this->document = $this->prophesize(AutoNameMarker::class);
        $this->parentDocument = new \stdClass;
        $this->classMetadata = $this->prophesize(ClassMetadata::class);
        $this->unitOfWork = $this->prophesize(UnitOfWork::class);
        $this->event = $this->prophesize(LifecycleEventArgs::class);

        $this->subscriber = new NameSubscriber(
            $this->documentManager->reveal(),
            $this->slugifier->reveal()
        );

        $this->documentManager->getUnitOfWork()->willReturn($this->unitOfWork->reveal());

    }

    public function testNoDocument()
    {
        $this->document->getTitle()->shouldNotBeCalled();
        $this->event->getObject()->willReturn(new \stdClass);
        $this->subscriber->prePersist($this->event->reveal());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Document of class
     */
    public function testNoTitle()
    {
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->document->getTitle()->willReturn(null);

        $this->subscriber->prePersist($this->event->reveal());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Document with title "foo" has no parent
     */
    public function testNoParent()
    {
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->document->getTitle()->willReturn('foo');
        $this->document->getParent()->willReturn(null);

        $this->subscriber->prePersist($this->event->reveal());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Non-object detected
     */
    public function testNotObjectParent()
    {
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->document->getTitle()->willReturn('foo');
        $this->document->getParent()->willReturn('hai');

        $this->subscriber->prePersist($this->event->reveal());
    }

    public function testNewParent()
    {
        $this->initNewParent();

        $this->classMetadata->setFieldValue(
            $this->document->reveal(),
            'name',
            'foo'
        )->shouldBeCalled();

        $this->subscriber->prePersist($this->event->reveal());
    }

    public function testExistingParentNoConflict()
    {
        $this->initNewParent();

        $this->unitOfWork->getDocumentId(
            $this->parentDocument, false
        )->willReturn('/path/to');
        $this->documentManager->find(null, '/path/to/foo')->willReturn(null);

        $this->classMetadata->setFieldValue(
            $this->document->reveal(),
            'name',
            'foo'
        )->shouldBeCalled();

        $this->subscriber->prePersist($this->event->reveal());
    }

    public function testExistingParentConflict()
    {
        $this->initNewParent();

        $this->unitOfWork->getDocumentId(
            $this->parentDocument, false
        )->willReturn('/path/to');
        $this->documentManager->find(null, '/path/to/foo')->willReturn(new \stdClass);
        $this->documentManager->find(null, '/path/to/foo-1')->willReturn(new \stdClass);
        $this->documentManager->find(null, '/path/to/foo-2')->willReturn(null);

        $this->classMetadata->setFieldValue(
            $this->document->reveal(),
            'name',
            'foo-2'
        )->shouldBeCalled();

        $this->subscriber->prePersist($this->event->reveal());
    }

    private function initNewParent()
    {
        $this->event->getObject()->willReturn($this->document->reveal());
        $this->document->getTitle()->willReturn('foo');
        $this->document->getParent()->willReturn($this->parentDocument);
        $this->documentManager->getClassMetadata(
            get_class($this->document->reveal())
        )->willReturn($this->classMetadata->reveal());
        $this->slugifier->slugify('foo')->willReturn('foo');
    }
}
