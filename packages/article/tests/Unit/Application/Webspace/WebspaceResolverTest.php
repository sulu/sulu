<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Tests\Unit\Application\Webspace;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Application\Webspace\WebspaceSettingsConfigurationResolver;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceResolverTest extends TestCase
{
    use ProphecyTrait;

    private WebspaceManagerInterface $webspaceManager;
    private WebspaceSettingsConfigurationResolver $configurationResolver;

    protected function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->configurationResolver = $this->prophesize(WebspaceSettingsConfigurationResolver::class);
    }

    protected function getWebspaceResolverInstance(): WebspaceResolver
    {
        return new WebspaceResolver(
            $this->webspaceManager->reveal(),
            $this->configurationResolver->reveal()
        );
    }

    public function testResolveMainWebspaceWithValidWebspace(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $locale = 'en';
        $mainWebspaceKey = 'sulu-io';
        $webspace = $this->prophesize(Webspace::class);

        $this->configurationResolver->resolveMainWebspace($locale)->willReturn($mainWebspaceKey)->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey($mainWebspaceKey)->willReturn($webspace->reveal())->shouldBeCalled();

        $result = $resolver->resolveMainWebspace($locale);

        $this->assertSame($mainWebspaceKey, $result);
    }

    public function testResolveMainWebspaceWithInvalidWebspace(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $locale = 'en';
        $mainWebspaceKey = 'invalid-webspace';

        $this->configurationResolver->resolveMainWebspace($locale)->willReturn($mainWebspaceKey)->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey($mainWebspaceKey)->willReturn(null)->shouldBeCalled();

        $result = $resolver->resolveMainWebspace($locale);

        $this->assertNull($result);
    }

    public function testResolveMainWebspaceWithNullConfig(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $locale = 'en';

        $this->configurationResolver->resolveMainWebspace($locale)->willReturn(null)->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey()->shouldNotBeCalled();

        $result = $resolver->resolveMainWebspace($locale);

        $this->assertNull($result);
    }

    public function testResolveAdditionalWebspacesWithValidWebspaces(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $locale = 'en';
        $additionalWebspaceKeys = ['sulu-io', 'example-com'];
        $webspace1 = $this->prophesize(Webspace::class);
        $webspace2 = $this->prophesize(Webspace::class);

        $this->configurationResolver->resolveAdditionalWebspaces($locale)->willReturn($additionalWebspaceKeys)->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey('sulu-io')->willReturn($webspace1->reveal())->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey('example-com')->willReturn($webspace2->reveal())->shouldBeCalled();

        $result = $resolver->resolveAdditionalWebspaces($locale);

        $this->assertSame($additionalWebspaceKeys, $result);
    }

    public function testResolveAdditionalWebspacesWithSomeInvalidWebspaces(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $locale = 'en';
        $additionalWebspaceKeys = ['sulu-io', 'invalid-webspace', 'example-com'];
        $webspace1 = $this->prophesize(Webspace::class);
        $webspace2 = $this->prophesize(Webspace::class);

        $this->configurationResolver->resolveAdditionalWebspaces($locale)->willReturn($additionalWebspaceKeys)->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey('sulu-io')->willReturn($webspace1->reveal())->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey('invalid-webspace')->willReturn(null)->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey('example-com')->willReturn($webspace2->reveal())->shouldBeCalled();

        $result = $resolver->resolveAdditionalWebspaces($locale);

        $this->assertSame(['sulu-io', 'example-com'], $result);
    }

    public function testResolveAdditionalWebspacesWithEmptyConfig(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $locale = 'en';

        $this->configurationResolver->resolveAdditionalWebspaces($locale)->willReturn([])->shouldBeCalled();
        $this->webspaceManager->findWebspaceByKey()->shouldNotBeCalled();

        $result = $resolver->resolveAdditionalWebspaces($locale);

        $this->assertSame([], $result);
    }

    public function testResolveAdditionalWebspacesWithNullLocale(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $this->configurationResolver->resolveAdditionalWebspaces(null)->willReturn([])->shouldBeCalled();

        $result = $resolver->resolveAdditionalWebspaces(null);

        $this->assertSame([], $result);
    }
}