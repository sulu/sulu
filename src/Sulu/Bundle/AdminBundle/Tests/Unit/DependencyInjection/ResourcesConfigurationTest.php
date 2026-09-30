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
use Sulu\Bundle\AdminBundle\DependencyInjection\Configuration;
use Sulu\Bundle\AdminBundle\DependencyInjection\SuluAdminExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResourcesConfigurationTest extends TestCase
{
    public function testProjectConfigOverridesPrependedResourceView(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new SuluAdminExtension());

        $container->loadFromExtension('sulu_admin', [
            'resources' => [
                'pages' => [
                    'views' => ['detail' => 'app.page_edit_form'],
                ],
            ],
        ]);

        $container->prependExtensionConfig('sulu_admin', [
            'resources' => [
                'pages' => [
                    'routes' => ['list' => 'sulu_page.get_pages', 'detail' => 'sulu_page.get_page'],
                    'views' => ['detail' => 'sulu_page.page_edit_form'],
                ],
            ],
        ]);

        /** @var array{resources: array<string, array{routes: array<string, string>, views: array<string, string>}>} $config */
        $config = (new Processor())->processConfiguration(
            new Configuration(false),
            $container->getExtensionConfig('sulu_admin'),
        );

        $this->assertSame('app.page_edit_form', $config['resources']['pages']['views']['detail']);
        $this->assertSame(
            ['list' => 'sulu_page.get_pages', 'detail' => 'sulu_page.get_page'],
            $config['resources']['pages']['routes'],
        );
    }
}
