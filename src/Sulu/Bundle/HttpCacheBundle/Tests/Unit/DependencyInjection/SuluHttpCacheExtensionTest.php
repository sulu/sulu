<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\DependencyInjection;

use FOS\HttpCacheBundle\CacheManager;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\HttpCacheBundle\DependencyInjection\SuluHttpCacheExtension;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SuluHttpCacheExtensionTest extends AbstractExtensionTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<ReplacerInterface>
     */
    private $replacer;

    /**
     * @var ReplacerInterface
     */
    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private $logger;

    /**
     * @var ObjectProphecy<ReferenceStorePoolInterface>
     */
    private $referenceStore;

    public function setUp(): void
    {
        parent::setUp();

        $this->container->setParameter('kernel.debug', true);
        $this->container->setParameter('kernel.environment', 'test');

        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->replacer = $this->prophesize(ReplacerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->referenceStore = $this->prophesize(ReferenceStorePoolInterface::class);

        $this->container->set('sulu_core.webspace.webspace_manager', $this->webspaceManager->reveal());
        $this->container->set('request_stack', $this->requestStack->reveal());
        $this->container->set('sulu_core.webspace.webspace_manager.url_replacer', $this->replacer->reveal());
        $this->container->set('logger', $this->logger->reveal());
        $this->container->set('sulu_website.reference_store_pool', $this->referenceStore->reveal());
        $this->container->set('sulu_tag.tag_manager', $this->prophesize(TagManagerInterface::class)->reveal());
        $this->container->set('fos_http_cache.cache_manager', $this->prophesize(CacheManager::class)->reveal());
        $this->container->set('fos_http_cache.http.symfony_response_tagger', $this->prophesize(SymfonyResponseTagger::class)->reveal());
    }

    protected function getContainerExtensions(): array
    {
        return [
            new SuluHttpCacheExtension(),
        ];
    }

    public function testDefaultConfig(): void
    {
        $this->load();
        $this->compile();

        $this->assertTrue($this->container->has('sulu_http_cache.cache_lifetime.resolver'));
        $this->assertFalse($this->container->has('sulu_http_cache.cache_manager'));
        $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.shared_max_age', 240);
        $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.max_age', 240);
    }

    public function testConfig(): void
    {
        $config = [
            'cache' => [
                'max_age' => 520,
                'shared_max_age' => 340,
            ],
        ];

        $this->load($config);
        $this->compile();

        $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.max_age', 520);
        $this->assertContainerBuilderHasParameter('sulu_http_cache.cache.shared_max_age', 340);
    }
}
