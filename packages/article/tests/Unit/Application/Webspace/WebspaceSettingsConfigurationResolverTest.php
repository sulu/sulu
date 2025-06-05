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
use Sulu\Article\Application\Webspace\WebspaceSettingsConfigurationResolver;

class WebspaceSettingsConfigurationResolverTest extends TestCase
{
    public function testResolveMainWebspaceWithLocaleSpecificConfig(): void
    {
        $defaultMainWebspace = [
            'en' => 'sulu-io-en',
            'de' => 'sulu-io-de',
            'default' => 'sulu-io-default',
        ];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveMainWebspace('en');
        $this->assertSame('sulu-io-en', $result);

        $result = $resolver->resolveMainWebspace('de');
        $this->assertSame('sulu-io-de', $result);
    }

    public function testResolveMainWebspaceWithDefaultConfig(): void
    {
        $defaultMainWebspace = [
            'en' => 'sulu-io-en',
            'default' => 'sulu-io-default',
        ];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveMainWebspace('fr');
        $this->assertSame('sulu-io-default', $result);
    }

    public function testResolveMainWebspaceWithNoConfig(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveMainWebspace('en');
        $this->assertNull($result);
    }

    public function testResolveMainWebspaceWithNullLocale(): void
    {
        $defaultMainWebspace = [
            'default' => 'sulu-io-default',
        ];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveMainWebspace(null);
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

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveAdditionalWebspaces('en');
        $this->assertSame(['sulu-io', 'example-com'], $result);

        $result = $resolver->resolveAdditionalWebspaces('de');
        $this->assertSame(['sulu-io', 'beispiel-de'], $result);
    }

    public function testResolveAdditionalWebspacesWithDefaultConfig(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [
            'en' => ['sulu-io', 'example-com'],
            'default' => ['sulu-io'],
        ];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveAdditionalWebspaces('fr');
        $this->assertSame(['sulu-io'], $result);
    }

    public function testResolveAdditionalWebspacesWithNoConfig(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveAdditionalWebspaces('en');
        $this->assertSame([], $result);
    }

    public function testResolveAdditionalWebspacesWithNullLocale(): void
    {
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = [
            'default' => ['sulu-io', 'example-com'],
        ];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveAdditionalWebspaces(null);
        $this->assertSame(['sulu-io', 'example-com'], $result);
    }

    public function testResolveMainWebspaceWithStringConfig(): void
    {
        // Test the bundle configuration normalization where a string becomes ['default' => $string]
        $defaultMainWebspace = ['default' => 'sulu-io'];
        $defaultAdditionalWebspaces = [];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveMainWebspace('en');
        $this->assertSame('sulu-io', $result);

        $result = $resolver->resolveMainWebspace('de');
        $this->assertSame('sulu-io', $result);
    }

    public function testResolveAdditionalWebspacesWithArrayConfig(): void
    {
        // Test the bundle configuration normalization where an array becomes ['default' => $array]
        $defaultMainWebspace = [];
        $defaultAdditionalWebspaces = ['default' => ['sulu-io', 'example-com']];

        $resolver = new WebspaceSettingsConfigurationResolver($defaultMainWebspace, $defaultAdditionalWebspaces);

        $result = $resolver->resolveAdditionalWebspaces('en');
        $this->assertSame(['sulu-io', 'example-com'], $result);

        $result = $resolver->resolveAdditionalWebspaces('de');
        $this->assertSame(['sulu-io', 'example-com'], $result);
    }
}