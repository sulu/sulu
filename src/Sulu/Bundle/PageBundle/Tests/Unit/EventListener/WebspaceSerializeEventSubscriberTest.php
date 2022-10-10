<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\EventListener;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\EventListener\WebspaceSerializeEventSubscriber;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\WebspaceUrlProviderInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WebspaceSerializeEventSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<WebspaceUrlProviderInterface>
     */
    private $webspaceUrlProvider;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyPoolInterface>
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var ObjectProphecy<AccessControlManagerInterface>
     */
    private $accessControlManager;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var WebspaceSerializeEventSubscriber
     */
    private $webspaceSerializeEventSubscriber;

    public function setUp(): void
    {
        $this->webspaceUrlProvider = $this->prophesize(WebspaceUrlProviderInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $this->webspaceSerializeEventSubscriber = new WebspaceSerializeEventSubscriber(
            $this->webspaceManager->reveal(),
            $this->webspaceUrlProvider->reveal(),
            $this->resourceLocatorStrategyPool->reveal(),
            $this->accessControlManager->reveal(),
            $this->tokenStorage->reveal(),
            'prod'
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = $this->webspaceSerializeEventSubscriber->getSubscribedEvents();

        $reflection = new \ReflectionClass(\get_class($this->webspaceSerializeEventSubscriber));

        foreach ($events as $event) {
            $this->assertTrue($reflection->hasMethod($event['method']));
            $this->assertEquals('json', $event['format']);
            $this->assertContains(
                $event['event'],
                [Events::POST_DESERIALIZE, Events::POST_SERIALIZE, Events::PRE_DESERIALIZE, Events::PRE_SERIALIZE]
            );
        }
    }

    public function testAppendPortalInformation(): void
    {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $portalInformation = [
            'test-1' => new PortalInformation(1),
            'test-2' => new PortalInformation(2),
        ];

        $context = $this->prophesize(Context::class);
        $graphNavigator = $this->prophesize(GraphNavigatorInterface::class);
        $context->getNavigator()->willReturn($graphNavigator->reveal());
        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $graphNavigator->accept(\array_values($portalInformation))->willReturn('[{}, {}]')->shouldBeCalled();
        $visitor->visitProperty(
            Argument::that(function(StaticPropertyMetadata $metadata) {
                return 'portalInformation' === $metadata->name;
            }),
            '[{}, {}]'
        )->shouldBeCalled();

        $this->webspaceManager->getPortalInformationsByWebspaceKey('prod', 'sulu_io')->willReturn($portalInformation);

        $reflection = new \ReflectionClass(\get_class($this->webspaceSerializeEventSubscriber));
        $method = $reflection->getMethod('appendPortalInformation');
        $method->setAccessible(true);

        $method->invokeArgs(
            $this->webspaceSerializeEventSubscriber,
            [$webspace->reveal(), $context->reveal(), $visitor->reveal()]
        );
    }

    public function testAppendUrls(): void
    {
        $urls = [
            new Url('sulu.lo'),
            new Url('*.sulu.lo'),
            new Url('sulu.io'),
            new Url('*.sulu.io'),
        ];

        $webspace = $this->prophesize(Webspace::class);
        $this->webspaceUrlProvider->getUrls($webspace->reveal(), 'prod')->willReturn($urls);

        $context = $this->prophesize(Context::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);
        $graphNavigator = $this->prophesize(GraphNavigatorInterface::class);
        $context->getNavigator()->willReturn($graphNavigator->reveal());

        $serialzedData = '[{"url": "sulu.lo"}, {"url": "*.sulu.lo"}, {"url": "sulu.io"}, {"url": "*.sulu.io"}]';
        $graphNavigator->accept($urls)->willReturn($serialzedData);

        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'urls', $serialzedData),
            $serialzedData
        )->shouldBeCalled();

        $reflection = new \ReflectionClass(\get_class($this->webspaceSerializeEventSubscriber));
        $method = $reflection->getMethod('appendUrls');
        $method->setAccessible(true);

        $method->invokeArgs(
            $this->webspaceSerializeEventSubscriber,
            [$webspace->reveal(), $context->reveal(), $visitor->reveal()]
        );
    }

    public function testAppendCustomUrls(): void
    {
        $customUrls = [
            new CustomUrl('sulu.lo'),
            new CustomUrl('*.sulu.lo'),
            new CustomUrl('sulu.io'),
            new CustomUrl('*.sulu.io'),
        ];

        $locales = [new Localization('de'), new Localization('en')];

        $environments = [$this->prophesize(Environment::class), $this->prophesize(Environment::class)];
        $portals = [$this->prophesize(Portal::class), $this->prophesize(Portal::class)];
        $portals[0]->getEnvironment('prod')->willReturn($environments[0]->reveal());
        $portals[0]->getLocalizations()->willReturn($locales);
        $portals[1]->getEnvironment('prod')->willReturn($environments[1]->reveal());
        $portals[1]->getLocalizations()->willReturn($locales);

        $environments[0]->getCustomUrls()->willReturn([$customUrls[0], $customUrls[1]]);
        $environments[1]->getCustomUrls()->willReturn([$customUrls[2], $customUrls[3]]);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getPortals()->willReturn(
            \array_map(
                function($portal) {
                    return $portal->reveal();
                },
                $portals
            )
        );

        $context = $this->prophesize(Context::class);
        $navigator = $this->prophesize(GraphNavigatorInterface::class);
        $context->getNavigator()->willReturn($navigator->reveal());
        $visitor = $this->prophesize(SerializationVisitorInterface::class);

        $serialzedData = '[{"url": "sulu.lo", "locales": [{"localization":"de"}, {"localization":"en"}]}, {"url": "*.sulu.lo","locales": [{"localization":"de"}, {"localization":"en"}]}, {"url": "sulu.io","locales": [{"localization":"de"}, {"localization":"en"}]}, {"url": "*.sulu.io","locales": [{"localization":"de"}, {"localization":"en"}]}]';
        $navigator->accept($customUrls[0])->willReturn(['url' => 'sulu.lo']);
        $navigator->accept($customUrls[1])->willReturn(['url' => '*.sulu.lo']);
        $navigator->accept($customUrls[2])->willReturn(['url' => 'sulu.io']);
        $navigator->accept($customUrls[3])->willReturn(['url' => '*.sulu.io']);
        $navigator->accept($locales)->willReturn([['localization' => 'de'], ['localization' => 'en']]);
        $navigator->accept(
            [
                ['url' => 'sulu.lo', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
                ['url' => '*.sulu.lo', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
                ['url' => 'sulu.io', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
                ['url' => '*.sulu.io', 'locales' => [['localization' => 'de'], ['localization' => 'en']]],
            ]
        )->willReturn($serialzedData);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'customUrls', $serialzedData),
            $serialzedData
        )->shouldBeCalled();

        $reflection = new \ReflectionClass(\get_class($this->webspaceSerializeEventSubscriber));
        $method = $reflection->getMethod('appendCustomUrls');
        $method->setAccessible(true);

        $method->invokeArgs(
            $this->webspaceSerializeEventSubscriber,
            [$webspace->reveal(), $context->reveal(), $visitor->reveal()]
        );
    }

    public function testAppendPermissions(): void
    {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu');

        $permissions = ['view' => true, 'add' => false, 'edit' => true];

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $token->getUser()->willReturn($user->reveal());
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $this->accessControlManager
             ->getUserPermissions(
                 new SecurityCondition('sulu.webspaces.sulu'),
                 $user->reveal()
             )
             ->willReturn($permissions);

        $context = $this->prophesize(Context::class);
        $visitor = $this->prophesize(SerializationVisitorInterface::class);
        $graphNavigator = $this->prophesize(GraphNavigatorInterface::class);
        $context->getNavigator()->willReturn($graphNavigator->reveal());

        $serialzedData = '{"view": true, "add": false, "edit": true}';
        $graphNavigator->accept($permissions)->willReturn($serialzedData);

        $visitor->visitProperty(
            new StaticPropertyMetadata('', '_permissions', $serialzedData),
            $serialzedData
        )->shouldBeCalled();

        $reflection = new \ReflectionClass(\get_class($this->webspaceSerializeEventSubscriber));
        $method = $reflection->getMethod('appendPermissions');
        $method->setAccessible(true);

        $method->invokeArgs(
            $this->webspaceSerializeEventSubscriber,
            [$webspace->reveal(), $context->reveal(), $visitor->reveal()]
        );
    }
}
