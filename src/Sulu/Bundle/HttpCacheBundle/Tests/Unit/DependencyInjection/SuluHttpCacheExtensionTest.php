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
use Psr\Log\LoggerInterface;
use Sulu\Bundle\HttpCacheBundle\DependencyInjection\SuluHttpCacheExtension;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SuluHttpCacheExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function setUp()
    {
        parent::setUp();

        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->replacer = $this->prophesize(ReplacerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->container->setParameter('kernel.environment', 'test');
        $this->container->set('sulu_core.webspace.webspace_manager', $this->webspaceManager->reveal());
        $this->container->set('sulu.content.type_manager', $this->contentTypeManager->reveal());
        $this->container->set('request_stack', $this->requestStack->reveal());
        $this->container->set('sulu_core.webspace.webspace_manager.url_replacer', $this->replacer->reveal());
        $this->container->set('logger', $this->logger->reveal());
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
        $this->assertTrue($this->container->has('sulu_http_cache.handler.url'));
        $this->assertFalse($this->container->has('sulu_http_cache.handler.tags'));
    }

    public function provideHandler()
    {
        return [
            ['tags'],
            ['url'],
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
