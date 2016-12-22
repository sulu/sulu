<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Behavior\AuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Subscriber\AuthorSubscriber;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

/**
 * Tests for author-subscriber.
 */
class AuthorSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var AuthorSubscriber
     */
    private $authorSubscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->authorSubscriber = new AuthorSubscriber($this->propertyEncoder->reveal());
    }

    public function testSetAuthorOnDocument()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('sulu:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORS_PROPERTY_NAME, 'de')
            ->willReturn('sulu:authors');

        $node->getPropertyValueWithDefault('sulu:authored', null)->willReturn('2017-01-01');
        $node->getPropertyValueWithDefault('sulu:authors', [])->willReturn([1, 2, 3]);

        $document->setAuthored('2017-01-01')->shouldBeCalled();
        $document->setAuthors([1, 2, 3])->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnDocument($event->reveal());
    }

    public function testSetAuthorOnDocumentLocalized()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(LocalizedAuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system_localized', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authored');
        $this->propertyEncoder->encode('system_localized', AuthorSubscriber::AUTHORS_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authors');

        $node->getPropertyValueWithDefault('i18n:authored', null)->willReturn('2017-01-01');
        $node->getPropertyValueWithDefault('i18n:authors', [])->willReturn([1, 2, 3]);

        $document->setAuthored('2017-01-01')->shouldBeCalled();
        $document->setAuthors([1, 2, 3])->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnDocument($event->reveal());
    }

    public function testSetAuthorOnNode()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('sulu:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORS_PROPERTY_NAME, 'de')
            ->willReturn('sulu:authors');

        $document->getAuthors()->willReturn([1, 2, 3]);
        $document->getAuthored()->willReturn('2017-01-01');

        $node->setProperty('sulu:authors', [1, 2, 3])->shouldBeCalled();
        $node->setProperty('sulu:authored', '2017-01-01')->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnNode($event->reveal());
    }

    public function testSetAuthorOnNodeLocalized()
    {
        $event = $this->prophesize(AbstractMappingEvent::class);
        $node = $this->prophesize(NodeInterface::class);
        $document = $this->prophesize(AuthorBehavior::class);

        $event->getDocument()->willReturn($document->reveal());
        $event->getNode()->willReturn($node->reveal());
        $event->getLocale()->willReturn('de');

        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORED_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authored');
        $this->propertyEncoder->encode('system', AuthorSubscriber::AUTHORS_PROPERTY_NAME, 'de')
            ->willReturn('i18n:authors');

        $document->getAuthors()->willReturn([1, 2, 3]);
        $document->getAuthored()->willReturn('2017-01-01');

        $node->setProperty('i18n:authors', [1, 2, 3])->shouldBeCalled();
        $node->setProperty('i18n:authored', '2017-01-01')->shouldBeCalled();

        $this->authorSubscriber->setAuthorOnNode($event->reveal());
    }
}
