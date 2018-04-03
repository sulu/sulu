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
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
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

    /**
     * @var LoggerInterface
     */
    private $referenceStore;

    public function setUp()
    {
        parent::setUp();

        $this->container->setParameter('kernel.debug', true);
        $this->container->setParameter('kernel.environment', 'test');

        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->replacer = $this->prophesize(ReplacerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStorePoolInterface::class);

        $this->container->set('sulu_core.webspace.webspace_manager', $this->webspaceManager->reveal());
        $this->container->set('sulu.content.type_manager', $this->contentTypeManager->reveal());
        $this->container->set('request_stack', $this->requestStack->reveal());
        $this->container->set('sulu_core.webspace.webspace_manager.url_replacer', $this->replacer->reveal());
        $this->container->set('logger', $this->logger->reveal());
        $this->container->set('sulu_website.reference_store_pool', $this->referenceStore->reveal());
    }

    protected function getContainerExtensions()
    {
        return [
            new SuluHttpCacheExtension(),
        ];
    }

    public function provideEnvConfig()
    {
        return [
            [true, 'test', false],
            [false, 'test', false],
            [true, 'dev', false],
            [false, 'dev', false],
            [true, 'prod', true],
            [false, 'prod', true],
        ];
    }

    public function testDefaultConfig()
    {
        $this->load();
        $this->compile();

        $this->assertTrue($this->container->has('sulu_http_cache.cache_lifetime.resolver'));
        $this->assertTrue($this->container->has('sulu_http_cache.event_subscriber.invalidation'));
        $this->assertFalse($this->container->has('sulu_http_cache.cache_manager'));
    }

    /**
     * @dataProvider provideEnvConfig
     */
    public function testVarnishConfig(bool $debug, string $env, bool $expected)
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->container->setParameter('kernel.environment', $env);

        $config = [
            'proxy_client' => [
                'varnish' => [
                    'enabled' => true,
                ],
            ],
        ];

        $this->load($config);
        $this->compile();

        $this->assertEquals($expected, $this->container->has('sulu_http_cache.cache_manager'));

        if ($expected) {
            $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.max_age', 240);
            $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.shared_max_age', 240);
        }
    }

    /**
     * @dataProvider provideEnvConfig
     */
    public function testSymfonyConfig(bool $debug, string $env, bool $expected)
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->container->setParameter('kernel.environment', $env);

        $config = [
            'proxy_client' => [
                'symfony' => [
                    'enabled' => true,
                ],
            ],
        ];

        $this->load($config);
        $this->compile();

        $this->assertEquals($expected, $this->container->has('sulu_http_cache.cache_manager'));

        if ($expected) {
            $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.max_age', 240);
            $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.shared_max_age', 240);
        }
    }
}
