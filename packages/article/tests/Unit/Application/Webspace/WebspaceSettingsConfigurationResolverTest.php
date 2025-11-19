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
use Sulu\Article\Application\Webspace\WebspaceSettingsConfigurationResolver;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceSettingsConfigurationResolverTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;

    protected function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
    }

    public function testResolveMainWebspaceWithLocaleSpecificConfig(): void
    {
        $defaultMainWebspace = [
            'en' => 'sulu-io-en',
            'de' => 'sulu-io-de',
            'default' => 'sulu-io-default',
        ];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultMainWebspaceForLocale('en');
        $this->assertSame('sulu-io-en', $result);

        $result = $resolver->getDefaultMainWebspaceForLocale('de');
        $this->assertSame('sulu-io-de', $result);
    }

    public function testResolveMainWebspaceWithDefaultConfig(): void
    {
        $defaultMainWebspace = [
            'en' => 'sulu-io-en',
            'default' => 'sulu-io-default',
        ];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultMainWebspaceForLocale('fr');
        $this->assertSame('sulu-io-default', $result);
    }

    public function testResolveMainWebspaceWithNoConfig(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $webspace = new Webspace();
        $webspace->setName('sulu-io');
        $webspace->setKey('sulu-io');
        $webspaceCollection = new WebspaceCollection(['default' => $webspace]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);
        $result = $resolver->getDefaultMainWebspaceForLocale('en');
        $this->assertSame('sulu-io', $result);
    }

    public function testResolveMainWebspaceWithNoConfigMultipleWebspaces(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $webspace = new Webspace();
        $webspace->setName('sulu-io');
        $webspace->setKey('sulu-io');
        $webspace2 = new Webspace();
        $webspace2->setName('blog');
        $webspace2->setKey('blog');
        $webspaceCollection = new WebspaceCollection(['default' => $webspace, 'additional' => $webspace2]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);
        $this->expectException(\Exception::class);
        $result = $resolver->getDefaultMainWebspaceForLocale('en');
    }

    public function testResolveMainWebspaceWithNullLocale(): void
    {
        $defaultMainWebspace = [
            'default' => 'sulu-io-default',
        ];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultMainWebspaceForLocale('default');
        $this->assertSame('sulu-io-default', $result);
    }

    public function testResolveAdditionalWebspacesWithLocaleSpecificConfig(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [
            'en' => ['sulu-io', 'example-com'],
            'de' => ['sulu-io', 'beispiel-de'],
            'default' => ['sulu-io'],
        ];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultAdditionalWebspacesForLocale('en');
        $this->assertSame(['sulu-io', 'example-com'], $result);

        $result = $resolver->getDefaultAdditionalWebspacesForLocale('de');
        $this->assertSame(['sulu-io', 'beispiel-de'], $result);
    }

    public function testResolveAdditionalWebspacesWithDefaultConfig(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [
            'en' => ['sulu-io', 'example-com'],
            'default' => ['sulu-io'],
        ];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultAdditionalWebspacesForLocale('fr');
        $this->assertSame(['sulu-io'], $result);
    }

    public function testResolveAdditionalWebspacesWithNoConfig(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultAdditionalWebspacesForLocale('en');
        $this->assertSame([], $result);
    }

    public function testResolveAdditionalWebspacesWithNullLocale(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [
            'default' => ['sulu-io', 'example-com'],
        ];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultAdditionalWebspacesForLocale('default');
        $this->assertSame(['sulu-io', 'example-com'], $result);
    }

    public function testResolveMainWebspaceWithStringConfig(): void
    {
        // Test the bundle configuration normalization where a string becomes ['default' => $string]
        $defaultMainWebspace = ['default' => 'sulu-io'];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultMainWebspaceForLocale('en');
        $this->assertSame('sulu-io', $result);

        $result = $resolver->getDefaultMainWebspaceForLocale('de');
        $this->assertSame('sulu-io', $result);
    }

    public function testResolveAdditionalWebspacesWithArrayConfig(): void
    {
        // Test the bundle configuration normalization where an array becomes ['default' => $array]
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = ['default' => ['sulu-io', 'example-com']];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces, $this->webspaceManager->reveal());

        $result = $resolver->getDefaultAdditionalWebspacesForLocale('en');
        $this->assertSame(['sulu-io', 'example-com'], $result);

        $result = $resolver->getDefaultAdditionalWebspacesForLocale('de');
        $this->assertSame(['sulu-io', 'example-com'], $result);
    }
}
