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
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url;
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

    public function testAppendUrls()
    {
        $urls = [
            new Url('sulu.lo'),
            new Url('*.sulu.lo'),
            new Url('sulu.io'),
            new Url('*.sulu.io'),
        ];

        $environments = [$this->prophesize(Environment::class), $this->prophesize(Environment::class)];
        $portals = [$this->prophesize(Portal::class), $this->prophesize(Portal::class)];
        $portals[0]->getEnvironment('prod')->willReturn($environments[0]->reveal());
        $portals[1]->getEnvironment('prod')->willReturn($environments[1]->reveal());

        $environments[0]->getUrls()->willReturn([$urls[0], $urls[1]]);
        $environments[1]->getUrls()->willReturn([$urls[2], $urls[3]]);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getPortals()->willReturn(
            array_map(
                function ($portal) {
                    return $portal->reveal();
                },
                $portals
            )
        );

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), 'prod');

        $context = $this->prophesize(Context::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);

        $serialzedData = '[{"url": "sulu.lo"}, {"url": "*.sulu.lo"}, {"url": "sulu.io"}, {"url": "*.sulu.io"}]';
        $context->accept($urls)->willReturn($serialzedData);
        $visitor->addData('urls', $serialzedData);

        $reflection = new \ReflectionClass(get_class($subscriber));
        $method = $reflection->getMethod('appendUrls');
        $method->setAccessible(true);

        $method->invokeArgs($subscriber, [$webspace->reveal(), $context->reveal(), $visitor->reveal()]);
    }

    public function testAppendCustomUrls()
    {
        $customUrls = [
            new CustomUrl('sulu.lo'),
            new CustomUrl('*.sulu.lo'),
            new CustomUrl('sulu.io'),
            new CustomUrl('*.sulu.io'),
        ];

        $environments = [$this->prophesize(Environment::class), $this->prophesize(Environment::class)];
        $portals = [$this->prophesize(Portal::class), $this->prophesize(Portal::class)];
        $portals[0]->getEnvironment('prod')->willReturn($environments[0]->reveal());
        $portals[1]->getEnvironment('prod')->willReturn($environments[1]->reveal());

        $environments[0]->getCustomUrls()->willReturn([$customUrls[0], $customUrls[1]]);
        $environments[1]->getCustomUrls()->willReturn([$customUrls[2], $customUrls[3]]);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getPortals()->willReturn(
            array_map(
                function ($portal) {
                    return $portal->reveal();
                },
                $portals
            )
        );

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $subscriber = new WebspaceSerializeEventSubscriber($webspaceManager->reveal(), 'prod');

        $context = $this->prophesize(Context::class);
        $visitor = $this->prophesize(JsonSerializationVisitor::class);

        $serialzedData = '[{"url": "sulu.lo"}, {"url": "*.sulu.lo"}, {"url": "sulu.io"}, {"url": "*.sulu.io"}]';
        $context->accept($customUrls)->willReturn($serialzedData);
        $visitor->addData('urls', $serialzedData);

        $reflection = new \ReflectionClass(get_class($subscriber));
        $method = $reflection->getMethod('appendCustomUrls');
        $method->setAccessible(true);

        $method->invokeArgs($subscriber, [$webspace->reveal(), $context->reveal(), $visitor->reveal()]);
    }
}
