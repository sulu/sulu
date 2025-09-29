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
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Application\Webspace\WebspaceSettingsConfigurationResolver;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class WebspaceResolverTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;
    /** @var ObjectProphecy<WebspaceSettingsConfigurationResolver> */
    private ObjectProphecy $configurationResolver;

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

    public function testResolveMainWebspaceWithCustomizedSettings(): void
    {
        $locale = 'en';
        $mainWebspaceKey = 'sulu-io';

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->getCustomizeWebspaceSettings()->willReturn(true);
        $dimensionContent->getMainWebspace()->willReturn($mainWebspaceKey);

        $webspaceCollection = $this->prophesize(WebspaceCollection::class);
        $webspace1 = $this->prophesize(Webspace::class);
        $webspace2 = $this->prophesize(Webspace::class);
        $webspaceCollection->getWebspaces()->willReturn([$webspace1->reveal(), $webspace2->reveal()]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection->reveal());

        $resolver = $this->getWebspaceResolverInstance();
        $result = $resolver->resolveMainWebspace($dimensionContent->reveal(), $locale);

        $this->assertSame($mainWebspaceKey, $result);
    }

    public function testResolveAdditionalWebspacesWithCustomizedSettings(): void
    {
        $locale = 'en';
        $additionalWebspaces = ['sulu-io', 'example-com'];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->getCustomizeWebspaceSettings()->willReturn(true);
        $dimensionContent->getAdditionalWebspaces()->willReturn($additionalWebspaces);

        $webspaceCollection = $this->prophesize(WebspaceCollection::class);
        $webspace1 = $this->prophesize(Webspace::class);
        $webspace2 = $this->prophesize(Webspace::class);
        $webspaceCollection->getWebspaces()->willReturn([$webspace1->reveal(), $webspace2->reveal()]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection->reveal());

        $resolver = $this->getWebspaceResolverInstance();
        $result = $resolver->resolveAdditionalWebspaces($dimensionContent->reveal(), $locale);

        $this->assertSame($additionalWebspaces, $result);
    }

    public function testHasCustomizedWebspaceSettingsTrue(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->getCustomizeWebspaceSettings()->willReturn(true);

        $result = $resolver->hasCustomizedWebspaceSettings($dimensionContent->reveal());

        $this->assertTrue($result);
    }

    public function testHasCustomizedWebspaceSettingsFalse(): void
    {
        $resolver = $this->getWebspaceResolverInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->getCustomizeWebspaceSettings()->willReturn(false);

        $result = $resolver->hasCustomizedWebspaceSettings($dimensionContent->reveal());

        $this->assertFalse($result);
    }
}
