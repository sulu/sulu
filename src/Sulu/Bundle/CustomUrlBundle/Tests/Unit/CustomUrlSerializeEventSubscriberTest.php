<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Unit;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\CustomUrlBundle\EventListener\CustomUrlSerializeEventSubscriber;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;

class CustomUrlSerializeEventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $subscriber = new CustomUrlSerializeEventSubscriber($generator->reveal(), $userManager->reveal());

        $events = $subscriber->getSubscribedEvents();

        $reflection = new \ReflectionClass(get_class($subscriber));

        foreach ($events as $event) {
            $this->assertTrue($reflection->hasMethod($event['method']));
            $this->assertEquals('json', $event['format']);
            $this->assertContains(
                $event['event'],
                [Events::POST_DESERIALIZE, Events::POST_SERIALIZE, Events::PRE_DESERIALIZE, Events::PRE_SERIALIZE]
            );
        }
    }

    public function testOnPostSerialize()
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $subscriber = new CustomUrlSerializeEventSubscriber($generator->reveal(), $userManager->reveal());

        $event = $this->prophesize(ObjectEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);
        $pageDocument = $this->prophesize(PageDocument::class);
        $pageDocument->getTitle()->willReturn('test');
        $document->getTargetDocument()->willReturn($pageDocument->reveal());
        $document->getBaseDomain()->willReturn('*.sulu.io');
        $document->getDomainParts()->willReturn(['prefix' => 'test', 'suffix' => []]);
        $document->getCreator()->willReturn(1);
        $document->getChanger()->willReturn(2);

        $userManager->getFullNameByUserId(1)->willReturn('test1');
        $userManager->getFullNameByUserId(2)->willReturn('test2');

        $generator->generate('*.sulu.io', ['prefix' => 'test', 'suffix' => []])->willReturn('test.sulu.io');

        $event->getObject()->willReturn($document->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber->onPostSerialize($event->reveal());

        $visitor->addData('targetTitle', 'test')->shouldBeCalled();
        $visitor->addData('customUrl', 'test.sulu.io')->shouldBeCalled();
        $visitor->addData('creatorFullName', 'test1')->shouldBeCalled();
        $visitor->addData('changerFullName', 'test2')->shouldBeCalled();
    }

    public function testOnPostSerializeWrongDocument()
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $subscriber = new CustomUrlSerializeEventSubscriber($generator->reveal(), $userManager->reveal());

        $event = $this->prophesize(ObjectEvent::class);
        $document = $this->prophesize(\stdClass::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);

        $event->getObject()->willReturn($document->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber->onPostSerialize($event->reveal());

        $visitor->addData(Argument::any(), Argument::any())->shouldNotBeCalled();
        $generator->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
    }

    public function testOnPostSerializeNoTarget()
    {
        $generator = $this->prophesize(GeneratorInterface::class);
        $userManager = $this->prophesize(UserManagerInterface::class);
        $subscriber = new CustomUrlSerializeEventSubscriber($generator->reveal(), $userManager->reveal());

        $event = $this->prophesize(ObjectEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);
        $document->getTargetDocument()->willReturn(null);
        $document->getBaseDomain()->willReturn('*.sulu.io');
        $document->getDomainParts()->willReturn(['prefix' => 'test', 'suffix' => []]);
        $document->getCreator()->willReturn(1);
        $document->getChanger()->willReturn(2);

        $userManager->getFullNameByUserId(1)->willReturn('test1');
        $userManager->getFullNameByUserId(2)->willReturn('test2');

        $generator->generate('*.sulu.io', ['prefix' => 'test', 'suffix' => []])->willReturn('test.sulu.io');

        $event->getObject()->willReturn($document->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber->onPostSerialize($event->reveal());

        $visitor->addData('targetTitle', Argument::any())->shouldNotBeCalled();
        $visitor->addData('customUrl', 'test.sulu.io')->shouldBeCalled();
        $visitor->addData('creatorFullName', 'test1')->shouldBeCalled();
        $visitor->addData('changerFullName', 'test2')->shouldBeCalled();
    }
}
