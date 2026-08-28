<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\WebsiteBundle\DependencyInjection\SuluWebsiteExtension;

class SuluWebsiteExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SuluWebsiteExtension(),
        ];
    }

    public function testLoadNoContext(): void
    {
        $this->container->setParameter('sulu.context', null);
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', false);
        $this->load();

        $this->assertContainerBuilderNotHasService('sulu_website.data_collector.sulu_collector');
        $this->assertContainerBuilderHasService('sulu_website.error_page_cache_clear_subscriber');
    }

    public function testLoadWithContextWebsite(): void
    {
        $this->container->setParameter('sulu.context', 'website');
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', false);
        $this->load();
        $this->assertContainerBuilderHasService('sulu_website.data_collector.sulu_collector');
    }

    public function testLoadWithContextAdmin(): void
    {
        $this->container->setParameter('sulu.context', 'admin');
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', false);
        $this->load();
        $this->assertContainerBuilderNotHasService('sulu_website.data_collector.sulu_collector');
    }

    public function testLoadWithContextAdminAndTrashBundle(): void
    {
        $this->container->setParameter('sulu.context', 'admin');
        $this->container->setParameter('kernel.bundles', ['SuluTrashBundle' => true]);
        $this->container->setParameter('kernel.debug', false);
        $this->load();
        $this->assertContainerBuilderNotHasService('sulu_website.data_collector.sulu_collector');
    }

    public function testLoadWithDebugShouldNotRegisterErrorCache(): void
    {
        $this->container->setParameter('sulu.context', 'admin');
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', true);

        $this->load();

        $this->assertContainerBuilderNotHasService('sulu_website.error_page_cache_clear_subscriber');
        $this->assertContainerBuilderNotHasService('sulu_website.error_page_cache');
    }
}
