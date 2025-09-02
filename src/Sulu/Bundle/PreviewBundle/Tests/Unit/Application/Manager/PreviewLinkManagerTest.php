<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Application\Manager;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PreviewBundle\Application\Manager\PreviewLinkManager;
use Sulu\Bundle\PreviewBundle\Domain\Event\PreviewLinkGeneratedEvent;
use Sulu\Bundle\PreviewBundle\Domain\Event\PreviewLinkRevokedEvent;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;
use Symfony\Component\Routing\RouterInterface;

class PreviewLinkManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PreviewLinkRepositoryInterface>
     */
    private $previewLinkRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var ObjectProphecy<PreviewObjectProviderRegistryInterface>
     */
    private $previewObjectProviderRegistry;

    /**
     * @var ObjectProphecy<PreviewDefaultsProviderInterface>
     */
    private $previewObjectProvider;

    /**
     * @var ObjectProphecy<RouterInterface>
     */
    private $router;

    /**
     * @var PreviewLinkManager
     */
    private $previewLinkManager;

    /**
     * @var string
     */
    private $resourceKey = 'pages';

    protected function setUp(): void
    {
        $this->previewLinkRepository = $this->prophesize(PreviewLinkRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->previewObjectProviderRegistry = $this->prophesize(PreviewObjectProviderRegistryInterface::class);
        $this->previewObjectProvider = $this->prophesize(PreviewDefaultsProviderInterface::class);
        $this->router = $this->prophesize(RouterInterface::class);

        $this->previewLinkManager = new PreviewLinkManager(
            $this->previewLinkRepository->reveal(),
            $this->domainEventCollector->reveal(),
            $this->previewObjectProviderRegistry->reveal(),
            $this->router->reveal()
        );

        $this->previewObjectProviderRegistry->getPreviewObjectProvider($this->resourceKey)
            ->willReturn($this->previewObjectProvider->reveal());
    }

    public function testGenerate(): void
    {
        $resourceId = 'aac8f317-d479-457c-a6e4-d95a3f19c0a6';
        $locale = 'en';
        $link = 'http://loclhost/admin/p/123';

        $this->previewObjectProvider->getSecurityContext(Argument::any())
            ->willReturn(PageAdmin::getPageSecurityContext('example'));

        $previewLink = $this->prophesize(PreviewLinkInterface::class);
        $previewLink->getToken()->willReturn('123');
        $previewLink->getResourceId()->willReturn($resourceId);
        $previewLink->getResourceKey()->willReturn($this->resourceKey);
        $this->previewLinkRepository->create($this->resourceKey, $resourceId, $locale, [])
            ->shouldBeCalled()
            ->willReturn($previewLink->reveal());

        $this->router->generate('sulu_preview.public_render', ['token' => '123'], RouterInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn($link);

        $this->domainEventCollector->collect(
            Argument::that(function(PreviewLinkGeneratedEvent $event) use ($resourceId, $link) {
                static::assertSame($resourceId, $event->getResourceId());
                static::assertSame($this->resourceKey, $event->getResourceKey());
                static::assertSame($link, $event->getResourceTitle());

                return true;
            })
        )->shouldBeCalled();

        $this->previewLinkRepository->add($previewLink->reveal())->shouldBeCalled();
        $this->previewLinkRepository->commit()->shouldBeCalled();

        $result = $this->previewLinkManager->generate($this->resourceKey, $resourceId, $locale, []);
        static::assertEquals($previewLink->reveal(), $result);
    }

    public function testRevoke(): void
    {
        $resourceId = 'aac8f317-d479-457c-a6e4-d95a3f19c0a6';
        $locale = 'en';
        $link = 'http://loclhost/admin/p/123';

        $this->previewObjectProvider->getSecurityContext(Argument::any())->willReturn(
            PageAdmin::getPageSecurityContext('example')
        );

        $previewLink = $this->prophesize(PreviewLinkInterface::class);
        $previewLink->getToken()->willReturn('123');
        $this->previewLinkRepository->findByResource($this->resourceKey, $resourceId, $locale)
            ->shouldBeCalled()
            ->willReturn($previewLink->reveal());

        $this->router->generate('sulu_preview.public_render', ['token' => '123'], RouterInterface::ABSOLUTE_URL)
            ->shouldBeCalled()
            ->willReturn($link);

        $this->domainEventCollector->collect(
            Argument::that(function(PreviewLinkRevokedEvent $event) use ($resourceId, $link) {
                static::assertSame($resourceId, $event->getResourceId());
                static::assertSame($this->resourceKey, $event->getResourceKey());
                static::assertSame($link, $event->getResourceTitle());

                return true;
            })
        )->shouldBeCalled();

        $this->previewLinkRepository->remove($previewLink->reveal())->shouldBeCalled();
        $this->previewLinkRepository->commit()->shouldBeCalled();

        $this->previewLinkManager->revoke($this->resourceKey, $resourceId, $locale);
    }

    public function testRevokeNotFound(): void
    {
        $resourceId = 'aac8f317-d479-457c-a6e4-d95a3f19c0a6';
        $locale = 'en';

        $this->previewLinkRepository->findByResource($this->resourceKey, $resourceId, $locale)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->previewLinkRepository->remove(Argument::any())->shouldNotBeCalled();
        $this->previewLinkRepository->commit()->shouldNotBeCalled();

        $this->previewLinkManager->revoke($this->resourceKey, $resourceId, $locale);
    }
}
