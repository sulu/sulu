<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Document\Subscriber\TitleSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocalizedTitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class TitleSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PropertyEncoder>
     */
    private $propertyEncoder;

    /**
     * @var TitleSubscriber
     */
    private $titleSubscriber;

    public function setUp(): void
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->titleSubscriber = new TitleSubscriber($this->propertyEncoder->reveal());
    }

    public function testSetTitleOnDocument(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $document = $this->prophesize(TitleBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $this->propertyEncoder->contentName('title')->willReturn('title');
        $node->getPropertyValueWithDefault('title', '')->willReturn('title');

        $document->setTitle('title')->shouldBeCalled();

        $this->titleSubscriber->setTitleOnDocument($event->reveal());
    }

    public function testSetTitleOnDocumentLocalized(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $document = $this->prophesize(LocalizedTitleBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getNode()->willReturn($node->reveal());
        $this->propertyEncoder->localizedContentName('title', 'de')->willReturn('i18n:de-title');
        $node->getPropertyValueWithDefault('i18n:de-title', '')->willReturn('title');

        $document->setTitle('title')->shouldBeCalled();

        $this->titleSubscriber->setTitleOnDocument($event->reveal());
    }

    public function testSetTitleOnDocumentLocalizedWithoutLocale(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $document = $this->prophesize(LocalizedTitleBehavior::class);
        $event->getDocument()->willReturn($document);
        $event->getLocale()->willReturn(null);

        $event->getNode()->shouldNotBeCalled();

        $this->titleSubscriber->setTitleOnDocument($event->reveal());
    }

    public function testSetTitleOnDocumentWithWrongDocument(): void
    {
        $event = $this->prophesize(HydrateEvent::class);
        $document = new \stdClass();
        $event->getDocument()->willReturn($document);
        $event->getLocale()->willReturn('de');

        $event->getNode()->shouldNotBeCalled();

        $this->titleSubscriber->setTitleOnDocument($event->reveal());
    }

    public function testSetTitleOnNode(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(TitleBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $event->getDocument()->willReturn($document);
        $event->getLocale()->willReturn(null);
        $event->getNode()->willReturn($node->reveal());
        $this->propertyEncoder->contentName('title')->willReturn('title');
        $document->getTitle()->willReturn('title');

        $node->setProperty('title', 'title')->shouldBeCalled();

        $this->titleSubscriber->setTitleOnNode($event->reveal());
    }

    public function testSetTitleOnNodeLocalized(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(LocalizedTitleBehavior::class);
        $node = $this->prophesize(NodeInterface::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getNode()->willReturn($node->reveal());
        $this->propertyEncoder->localizedContentName('title', 'de')->willReturn('i18n:de-title');
        $document->getTitle()->willReturn('title');

        $node->setProperty('i18n:de-title', 'title')->shouldBeCalled();

        $this->titleSubscriber->setTitleOnNode($event->reveal());
    }

    public function testSetTitleOnNodeLocalizedWithoutLocale(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(LocalizedTitleBehavior::class);
        $event->getDocument()->willReturn($document);
        $event->getLocale()->willReturn(null);

        $event->getNode()->shouldNotBeCalled();

        $this->titleSubscriber->setTitleOnNode($event->reveal());
    }

    public function testSetTitleOnNodeWithWrongDocument(): void
    {
        $event = $this->prophesize(PersistEvent::class);
        $document = new \stdClass();
        $event->getDocument()->willReturn($document);
        $event->getLocale()->willReturn('de');

        $event->getNode()->shouldNotBeCalled();

        $this->titleSubscriber->setTitleOnNode($event->reveal());
    }
}
