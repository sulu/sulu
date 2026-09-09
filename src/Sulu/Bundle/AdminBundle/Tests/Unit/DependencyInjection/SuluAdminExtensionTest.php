<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\DependencyInjection\SuluAdminExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SuluAdminExtensionTest extends TestCase
{
    /**
     * @param mixed[] $config
     *
     * @return array<string, array{enterMode: string, tags: string[], features: string[]}>
     */
    private function loadTextEditorConfigs(array $config): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.bundles', []);

        (new SuluAdminExtension())->load([['text_editor' => ['configs' => $config]]], $container);

        /** @var array<string, array{enterMode: string, tags: string[], features: string[]}> $configs */
        $configs = $container->getParameter('sulu_admin.text_editor_configs');

        return $configs;
    }

    public function testShipsDefaultAndMiniConfig(): void
    {
        $configs = $this->loadTextEditorConfigs([]);

        $this->assertSame(['default', 'mini'], \array_keys($configs));
        $this->assertSame('p', $configs['default']['enterMode']);
        $this->assertSame('br', $configs['mini']['enterMode']);
        $this->assertSame(['align'], $configs['default']['features']);
        $this->assertSame(['br', 'a', 'strong', 'em'], $configs['mini']['tags']);
    }

    public function testDefaultConfigMatchesTheToolbarSuluShippedBefore(): void
    {
        $configs = $this->loadTextEditorConfigs([]);

        $this->assertSame(
            ['br', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'em', 'u', 's', 'sub', 'sup', 'ul', 'ol', 'a', 'table', 'code'],
            $configs['default']['tags']
        );
    }

    public function testProjectConfigSwitchesASingleTagOff(): void
    {
        $configs = $this->loadTextEditorConfigs([
            'default' => ['tags' => ['table' => false, 'code' => false]],
        ]);

        $this->assertNotContains('table', $configs['default']['tags']);
        $this->assertNotContains('code', $configs['default']['tags']);
        $this->assertContains('strong', $configs['default']['tags']);
        $this->assertSame(['align'], $configs['default']['features']);
    }

    public function testProjectConfigAddsATagToAShippedConfig(): void
    {
        $configs = $this->loadTextEditorConfigs([
            'mini' => ['tags' => ['h2' => true]],
        ]);

        $this->assertSame(['br', 'a', 'strong', 'em', 'h2'], $configs['mini']['tags']);
        $this->assertSame('br', $configs['mini']['enterMode'], 'the shipped enter mode is kept when not overridden');
    }

    public function testProjectConfigOverridesTheEnterModeOfAShippedConfig(): void
    {
        $configs = $this->loadTextEditorConfigs([
            'mini' => ['enter_mode' => 'p'],
        ]);

        $this->assertSame('p', $configs['mini']['enterMode']);
    }

    public function testOwnConfigStartsEmpty(): void
    {
        $configs = $this->loadTextEditorConfigs([
            'teaser' => ['tags' => ['a' => true, 'strong' => true], 'features' => ['align' => true]],
        ]);

        $this->assertSame(['a', 'strong'], $configs['teaser']['tags']);
        $this->assertSame(['align'], $configs['teaser']['features']);
        $this->assertSame('p', $configs['teaser']['enterMode']);
    }

    public function testDisabledKeysAreNotDeliveredToTheAdministrationInterface(): void
    {
        $configs = $this->loadTextEditorConfigs([
            'teaser' => ['tags' => ['a' => true, 'strong' => false]],
        ]);

        $this->assertSame(['a'], $configs['teaser']['tags']);
    }
}
