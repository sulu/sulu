<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\HttpCacheBundle\DependencyInjection\SuluHttpCacheExtension;

class SuluHttpCacheExtensionTest extends AbstractExtensionTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->container->setParameter('kernel.environment', 'test');
        $this->container->set('sulu_core.webspace.webspace_manager', $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface'));
        $this->container->set('sulu.content.type_manager', $this->getMock('Sulu\Component\Content\ContentTypeManagerInterface'));
        $this->container->set('logger', $this->getMock('Psr\Log\LoggerInterface'));
    }

    protected function getContainerExtensions()
    {
        return [
            new SuluHttpCacheExtension(),
        ];
    }

    public function testDefaultConfig()
    {
        $this->load();
        $this->compile();

        $this->assertTrue($this->container->has('sulu_http_cache.handler'));
        $this->assertTrue($this->container->has('sulu_http_cache.handler.aggregate'));
        $this->assertFalse($this->container->has('sulu_http_cache.handler.paths'));
        $this->assertFalse($this->container->has('sulu_http_cache.handler.tags'));
    }

    public function provideHandler()
    {
        return [
            ['tags'],
            ['paths'],
            ['public'],
            ['debug'],
            ['aggregate'],
        ];
    }

    /**
     * @dataProvider provideHandler
     */
    public function testHandler($handler)
    {
        $config = [];
        if ($handler !== 'aggregate') {
            $config = [
                'handlers' => [
                    $handler => [
                        'enabled' => true,
                    ],
                ],
            ];
        }

        $this->load($config);
        $this->compile();

        $this->assertTrue($this->container->has('sulu_http_cache.handler.aggregate'));
        $this->assertTrue($this->container->has('sulu_http_cache.handler.' . $handler));

        $this->container->get('sulu_http_cache.handler.' . $handler);
    }

    public function testVarnishConfig()
    {
        $config = [
            'proxy_client' => [
                'varnish' => [
                    'enabled' => true,
                    'servers' => ['foobar.dom', 'dom.foobar'],
                    'base_url' => 'http://foo.dom',
                ],
            ],
        ];

        $this->load($config);
        $this->compile();

        $res = $this->container->getParameter('sulu_http_cache.proxy_client.varnish.servers');
        $this->assertEquals($config['proxy_client']['varnish']['servers'], $res);

        $res = $this->container->getParameter('sulu_http_cache.proxy_client.varnish.base_url');
        $this->assertEquals($config['proxy_client']['varnish']['base_url'], $res);
    }

    public function provideEventSubscribers()
    {
        return [
            ['content_mapper'],
            ['flush'],
            ['update_response'],
        ];
    }

    /**
     * @dataProvider provideEventSubscribers
     */
    public function testEventSubscribers($name)
    {
        $this->load([]);
        $this->compile();

        $this->container->get('sulu_http_cache.event_subscriber.' . $name);
    }
}
