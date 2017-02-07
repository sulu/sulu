<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Unit\Sulu\Bundle\WebsiteBundle\EventListener;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\WebsiteBundle\DependencyInjection\SuluWebsiteExtension;

class SuluWebsiteExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new SuluWebsiteExtension(),
        ];
    }

    public function testLoadNoContext()
    {
        $this->container->setParameter('sulu.context', null);
        $this->load();
        $this->assertContainerBuilderNotHasService('sulu_website.data_collector.sulu_collector');
    }

    public function testLoadWithContextWebsite()
    {
        $this->container->setParameter('sulu.context', 'website');
        $this->load();
        $this->assertContainerBuilderHasService('sulu_website.data_collector.sulu_collector');
    }

    public function testLoadWithContextAdmin()
    {
        $this->container->setParameter('sulu.context', 'admin');
        $this->load();
        $this->assertContainerBuilderNotHasService('sulu_website.data_collector.sulu_collector');
    }
}
