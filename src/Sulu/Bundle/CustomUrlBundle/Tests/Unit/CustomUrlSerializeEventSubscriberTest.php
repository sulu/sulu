<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Unit;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\CustomUrlBundle\EventListener\CustomUrlSerializeEventSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;

class CustomUrlSerializeEventSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSubscribedEvents(): void
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $subscriber = new CustomUrlSerializeEventSubscriber(
            $generator->reveal(),
            $userManager->reveal(),
            $documentInspector->reveal()
        );

        $events = $subscriber->getSubscribedEvents();

        $reflection = new \ReflectionClass(\get_class($subscriber));

        foreach ($events as $event) {
            $this->assertTrue($reflection->hasMethod($event['method']));
            $this->assertEquals('json', $event['format']);
            $this->assertContains(
                $event['event'],
                [Events::POST_DESERIALIZE, Events::POST_SERIALIZE, Events::PRE_DESERIALIZE, Events::PRE_SERIALIZE]
            );
        }
    }

    public function testOnPostSerialize(): void
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $subscriber = new CustomUrlSerializeEventSubscriber(
            $generator->reveal(),
            $userManager->reveal(),
            $documentInspector->reveal()
        );

        $event = $this->prophesize(ObjectEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);
        $pageDocument = $this->prophesize(PageDocument::class);
        $pageDocument->getUuid()->willReturn('some-uuid');
        $pageDocument->getTitle()->willReturn('test');
        $document->getTargetDocument()->willReturn($pageDocument->reveal());
        $document->getBaseDomain()->willReturn('*.sulu.io');
        $document->getDomainParts()->willReturn(['prefix' => 'test', 'suffix' => []]);
        $document->getCreator()->willReturn(1);
        $document->getChanger()->willReturn(2);

        $userManager->getFullNameByUserId(1)->willReturn('test1');
        $userManager->getFullNameByUserId(2)->willReturn('test2');

        $generator->generate('*.sulu.io', ['prefix' => 'test', 'suffix' => []])->willReturn('test.sulu.io');

        $documentInspector->getWebspace($document->reveal())->willReturn('test-webspace');

        $event->getObject()->willReturn($document->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber->onPostSerialize($event->reveal());

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'targetDocument' === $metadata->name;
        }), 'some-uuid')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'targetTitle' === $metadata->name;
        }), 'test')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'customUrl' === $metadata->name;
        }), 'test.sulu.io')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'webspace' === $metadata->name;
        }), 'test-webspace')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'creatorFullName' === $metadata->name;
        }), 'test1')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'changerFullName' === $metadata->name;
        }), 'test2')->shouldBeCalled();
    }

    public function testOnPostSerializeWrongDocument(): void
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $subscriber = new CustomUrlSerializeEventSubscriber(
            $generator->reveal(),
            $userManager->reveal(),
            $documentInspector->reveal()
        );

        $event = $this->prophesize(ObjectEvent::class);
        $document = $this->prophesize(\stdClass::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $event->getObject()->willReturn($document->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber->onPostSerialize($event->reveal());

        $visitor->visitProperty(Argument::any(), Argument::any())->shouldNotBeCalled();
        $generator->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
    }

    public function testOnPostSerializeNoTarget(): void
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $subscriber = new CustomUrlSerializeEventSubscriber(
            $generator->reveal(),
            $userManager->reveal(),
            $documentInspector->reveal()
        );

        $event = $this->prophesize(ObjectEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);
        $document->getTargetDocument()->willReturn(null);
        $document->getBaseDomain()->willReturn('*.sulu.io');
        $document->getDomainParts()->willReturn(['prefix' => 'test', 'suffix' => []]);
        $document->getCreator()->willReturn(1);
        $document->getChanger()->willReturn(2);

        $userManager->getFullNameByUserId(1)->willReturn('test1');
        $userManager->getFullNameByUserId(2)->willReturn('test2');

        $generator->generate('*.sulu.io', ['prefix' => 'test', 'suffix' => []])->willReturn('test.sulu.io');

        $documentInspector->getWebspace($document->reveal())->willReturn('test-webspace');

        $event->getObject()->willReturn($document->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber->onPostSerialize($event->reveal());

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'targetTitle' === $metadata->name;
        }), Argument::any())->shouldNotBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'customUrl' === $metadata->name;
        }), 'test.sulu.io')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'webspace' === $metadata->name;
        }), 'test-webspace')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'creatorFullName' === $metadata->name;
        }), 'test1')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'changerFullName' === $metadata->name;
        }), 'test2')->shouldBeCalled();
    }

    public function testOnPostSerializeNoCreatorAndChanger(): void
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $subscriber = new CustomUrlSerializeEventSubscriber(
            $generator->reveal(),
            $userManager->reveal(),
            $documentInspector->reveal()
        );

        $event = $this->prophesize(ObjectEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);
        $pageDocument = $this->prophesize(PageDocument::class);
        $pageDocument->getUuid()->willReturn('some-uuid');
        $pageDocument->getTitle()->willReturn('test');
        $document->getTargetDocument()->willReturn($pageDocument->reveal());
        $document->getBaseDomain()->willReturn('*.sulu.io');
        $document->getDomainParts()->willReturn(['prefix' => 'test', 'suffix' => []]);
        $document->getCreator()->willReturn(null);
        $document->getChanger()->willReturn(null);

        $userManager->getFullNameByUserId(Argument::any())->shouldNotBeCalled();
        $userManager->getFullNameByUserId(Argument::any())->shouldNotBeCalled();

        $generator->generate('*.sulu.io', ['prefix' => 'test', 'suffix' => []])->willReturn('test.sulu.io');

        $documentInspector->getWebspace($document->reveal())->willReturn('test-webspace');

        $event->getObject()->willReturn($document->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber->onPostSerialize($event->reveal());

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'targetDocument' === $metadata->name;
        }), 'some-uuid')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'targetTitle' === $metadata->name;
        }), 'test')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'customUrl' === $metadata->name;
        }), 'test.sulu.io')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'webspace' === $metadata->name;
        }), 'test-webspace')->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'creatorFullName' === $metadata->name;
        }), null)->shouldBeCalled();

        $visitor->visitProperty(Argument::that(function(StaticPropertyMetadata $metadata) {
            return 'changerFullName' === $metadata->name;
        }), null)->shouldBeCalled();
    }
}
