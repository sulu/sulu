<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\EventListener;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Bundle\ContentBundle\EventListener\WebspaceSerializeEventSubscriber;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;

class WebspaceSerializeEventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), 'prod');

        $events = $subscriber->getSubscribedEvents();

        $reflection = new \ReflectionClass(get_class($subscriber));

        foreach ($events as $event) {
            self::assertTrue($reflection->hasMethod($event['method']));
            self::assertEquals('json', $event['format']);
            self::assertContains(
                $event['event'],
                [Events::POST_DESERIALIZE, Events::POST_SERIALIZE, Events::PRE_DESERIALIZE, Events::PRE_SERIALIZE]
            );
        }
    }

    public function testAppendPortalInformation()
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), 'prod');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $portalInformation = [
            'test-1' => new PortalInformation(1),
            'test-2' => new PortalInformation(2),
        ];

        $context = $this->prophesize(Context::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);

        $context->accept(array_values($portalInformation))->willReturn('[{}, {}]');
        $visitor->addData('urls', '[{}, {}]');

        $webspaceManager->getPortalInformationsByWebspaceKey('prod', 'sulu_io')->willReturn($portalInformation);

        $reflection = new \ReflectionClass(get_class($subscriber));
        $method = $reflection->getMethod('appendPortalInformation');
        $method->setAccessible(true);

        $method->invokeArgs($subscriber, [$webspace->reveal(), $context->reveal(), $visitor->reveal()]);
    }
}
