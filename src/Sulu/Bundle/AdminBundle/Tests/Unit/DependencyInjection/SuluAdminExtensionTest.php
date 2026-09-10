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
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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

        // The shipped configs arrive through prepend(), exactly as the kernel assembles them.
        $extension = new SuluAdminExtension();
        $extension->prepend($container);
        $extension->load(
            [...$container->getExtensionConfig('sulu_admin'), ['text_editor' => ['configs' => $config]]],
            $container
        );

        /** @var array<string, array{enterMode: string, tags: string[], features: string[]}> $configs */
        $configs = $container->getParameter('sulu_admin.text_editor_configs');

        return $configs;
    }

    /**
     * Pins the shipped vocabulary from Resources/config/text_editor.yaml. The same keys are mirrored by the CKEditor 5
     * registrations in Resources/js/containers/CKEditor5/index.js.
     *
     * "p" and "br" are intentionally absent: the editor always produces them and no plugin gates them.
     */
    public function testShippedConfigs(): void
    {
        $configs = $this->loadTextEditorConfigs([]);

        $this->assertSame(['default', 'mini'], \array_keys($configs));

        $this->assertSame('p', $configs['default']['enterMode']);
        $this->assertSame(
            ['h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'i', 'u', 's', 'sub', 'sup', 'ul', 'ol', 'a', 'table', 'code'],
            $configs['default']['tags'],
            'the default config reproduces the toolbar Sulu shipped before the text editor configs'
        );
        $this->assertSame(['align'], $configs['default']['features']);

        $this->assertSame('br', $configs['mini']['enterMode']);
        $this->assertSame(['a', 'strong', 'i'], $configs['mini']['tags']);
        $this->assertSame([], $configs['mini']['features']);
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

    public function testBlockKeyWithLineBreakEnterModeIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/enter_mode: br/');

        // "h2" needs a paragraph to carry it, which removePTags() strips out of the stored value.
        $this->loadTextEditorConfigs(['teaser' => ['enter_mode' => 'br', 'tags' => ['h2' => true]]]);
    }

    public function testBlockFeatureWithLineBreakEnterModeIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadTextEditorConfigs(['teaser' => ['enter_mode' => 'br', 'features' => ['align' => true]]]);
    }

    public function testDisabledBlockKeyWithLineBreakEnterModeIsAllowed(): void
    {
        $configs = $this->loadTextEditorConfigs([
            'teaser' => ['enter_mode' => 'br', 'tags' => ['a' => true, 'h2' => false]],
        ]);

        $this->assertSame(['a'], $configs['teaser']['tags']);
    }

    public function testNonStringConfigNameIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadTextEditorConfigs([2024 => ['tags' => ['a' => true]]]);
    }

    public function testNonStringTagIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loadTextEditorConfigs(['teaser' => ['tags' => [0 => true]]]);
    }

    public function testOwnConfigDefaultsToParagraphEnterMode(): void
    {
        $configs = $this->loadTextEditorConfigs(['teaser' => ['tags' => ['a' => true]]]);

        $this->assertSame('p', $configs['teaser']['enterMode']);
    }
}
