<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class PreviewTest extends TestCase
{
    use ProphecyTrait;

    private CacheItemPoolInterface $cache;

    /**
     * @var ObjectProphecy<PreviewRendererInterface>
     */
    private $renderer;

    /**
     * @var int
     */
    private $cacheLifeTime = 3600;

    /**
     * @var Preview
     */
    private $preview;

    private PreviewDefaultsProviderInterface $provider;

    /**
     * @var string
     */
    private $providerKey = 'test-provider';

    /**
     * @var string
     */
    private $locale = 'de';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var array<string, mixed>
     */
    private $object;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->renderer = $this->prophesize(PreviewRendererInterface::class);
        $this->object = ['object' => [], '_controller' => 'SuluTestBundle:Test:render'];

        $this->provider = new class() implements PreviewDefaultsProviderInterface {
            public function getDefaults(PreviewContext $previewContext): array
            {
                return ['object' => [], '_controller' => 'SuluTestBundle:Test:render'];
            }

            public function updateValues(PreviewContext $previewContext, array $defaults, array $data): array
            {
                $updateDefaults = $defaults;
                $updateDefaults['object'] = [
                    ...$defaults['object'],
                    ...$data,
                ];

                return $updateDefaults;
            }

            public function updateContext(PreviewContext $previewContext, array $defaults, array $context): array
            {
                $updateDefaults = $defaults;

                $updateDefaults['object'] = [
                    ...$defaults['object'],
                    ...$context,
                ];

                return $updateDefaults;
            }

            public function getSecurityContext(PreviewContext $previewContext): ?string
            {
                return 'sulu.page.pages';
            }
        };

        $providers = [$this->providerKey => $this->provider];
        $objectProviderRegistry = new PreviewObjectProviderRegistry($providers);

        $this->preview = new Preview($objectProviderRegistry, $this->cache, $this->renderer->reveal());
    }

    public function testStart(): void
    {
        $data = ['title' => 'Sulu'];

        $token = $this->preview->start($this->providerKey, '1', 1, $data, ['locale' => $this->locale]);

        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => null,
            'locale' => $this->locale,
        ];

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $expectedData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testStartWithoutData(): void
    {
        $token = $this->preview->start($this->providerKey, '1', 1, [], ['locale' => $this->locale]);

        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [...$this->object, 'object' => []],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => null,
            'locale' => $this->locale,
        ];

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $expectedData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testStartWithoutProvider(): void
    {
        $this->expectException(ProviderNotFoundException::class);

        $this->preview->start('xxx', '1', 1, [], ['locale' => $this->locale]);
    }

    public function testStop(): void
    {
        $token = '123-123-123';

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(['object' => []]);
        $this->cache->save($cacheItem);
        $this->assertTrue($this->cache->getItem($token)->isHit());

        $this->preview->stop($token);

        $this->assertFalse($this->cache->getItem($token)->isHit());
    }

    public function testStopNotExists(): void
    {
        $token = '123-123-123';

        $this->preview->stop($token);

        $this->assertFalse($this->cache->getItem($token)->isHit());
    }

    public function testExists(): void
    {
        $token = '123-123-123';

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(['object' => []]);
        $this->cache->save($cacheItem);
        $this->assertTrue($this->cache->getItem($token)->isHit());

        $this->assertTrue($this->preview->exists($token));
    }

    public function testExistsNot(): void
    {
        $token = '123-123-123';

        $this->assertFalse($this->preview->exists($token));
    }

    public function testUpdate(): void
    {
        $data = ['title' => 'Sulu'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => \json_encode(['title' => 'test']),
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $this->renderer->render(
            [
                ...$this->object,
                'object' => $data,
            ],
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $result = $this->preview->update(
            $token,
            $data,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $expectedData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testUpdateNoData(): void
    {
        $data = [];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(
            [
                ...$this->object,
                'object' => $data,
            ],
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $result = $this->preview->update(
            $token,
            [],
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $cacheData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testUpdateTokenNotExists(): void
    {
        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));

        $this->expectException(TokenNotFoundException::class);

        $this->preview->update($token, ['title' => 'SULU'], ['webspaceKey' => $this->webspaceKey]);
    }

    public function testUpdateWithOptions(): void
    {
        $data = ['title' => 'Sulu'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(
            [
                ...$this->object,
                'object' => $data,
            ],
            1,
            true,
            [
                'targetGroupId' => null,
                'segmentKey' => 'w',
                'webspaceKey' => $this->webspaceKey,
                'locale' => $this->locale,
            ]
        )->willReturn('<h1 property="title">SULU</h1>');

        $result = $this->preview->update(
            $token,
            $data,
            [
                'targetGroupId' => null,
                'segmentKey' => 'w',
                'webspaceKey' => $this->webspaceKey,
                'locale' => $this->locale,
            ]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $cacheData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testUpdateContext(): void
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];

        $context = ['template' => 'expert'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => [
                    ...$context,
                ],
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(
            [
                ...$this->object,
                'object' => [
                    ...$context,
                ],
            ],
            '1',
            false,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn(
            '<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>'
        );

        $this->renderer->render(
            [
                ...$this->object,
                'object' => [
                    ...$context,
                ],
            ],
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $result = $this->preview->updateContext(
            $token,
            $context,
            $data,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $expectedData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testUpdateContextNoContentReplacer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "{% block content %}" could not be found in the twig template');

        $data = ['title' => 'Sulu', 'template' => 'default'];
        $context = ['template' => 'expert'];

        $token = \md5(\sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(Argument::cetera())
            ->willReturn('<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>');

        $this->preview->updateContext(
            $token,
            $context,
            $data,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );
    }

    public function testUpdateContextNoContext(): void
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];

        $context = [];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(Argument::cetera())
            ->willReturn(
                '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>'
            );

        $result = $this->preview->updateContext(
            $token,
            $context,
            $data,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $cacheData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testUpdateContextWithOptions(): void
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];

        $context = ['template' => 'expert'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => [
                    ...$context,
                ],
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(
            [
                ...$this->object,
                'object' => [
                    ...$context,
                ],
            ],
            1,
            false,
            ['targetGroupId' => 2, 'segmentKey' => null, 'webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn(
            '<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>'
        );

        $this->renderer->render(
            [
                ...$this->object,
                'object' => [
                    ...$context,
                ],
            ],
            1,
            true,
            ['targetGroupId' => 2, 'segmentKey' => null, 'webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $result = $this->preview->updateContext(
            $token,
            $context,
            $data,
            ['targetGroupId' => 2, 'segmentKey' => null, 'webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $expectedData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testRender(): void
    {
        $data = ['title' => 'Sulu'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => null,
            'locale' => $this->locale,
        ];
        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => [],
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(
            $this->object,
            1,
            false,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>');

        $this->renderer->render(
            $this->object,
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->shouldBeCalled()->willReturn('<h1 property="title">SULU</h1>');

        $result = $this->preview->render($token, ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $expectedData,
            \json_decode($cacheItemResult, true)
        );
    }

    public function testRenderWithOptions(): void
    {
        $data = ['title' => 'Sulu'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => $data,
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => null,
            'locale' => $this->locale,
        ];
        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => [
                ...$this->object,
                'object' => [],
            ],
            'objectClass' => \get_debug_type($this->object),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
            'locale' => $this->locale,
        ];

        $cacheItem = $this->cache->getItem($token);
        $cacheItem->set(\json_encode($cacheData));
        $this->cache->save($cacheItem);

        $this->renderer->render(
            $this->object,
            1,
            false,
            [
                'targetGroupId' => null,
                'segmentKey' => 's',
                'webspaceKey' => $this->webspaceKey,
                'locale' => $this->locale,
            ]
        )->willReturn('<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>');

        $this->renderer->render(
            $this->object,
            1,
            true,
            [
                'targetGroupId' => null,
                'segmentKey' => 's',
                'webspaceKey' => $this->webspaceKey,
                'locale' => $this->locale,
            ]
        )
            ->willReturn('<h1 property="title">SULU</h1>');

        $result = $this->preview->render(
            $token,
            [
                'targetGroupId' => null,
                'segmentKey' => 's',
                'webspaceKey' => $this->webspaceKey,
                'locale' => $this->locale,
            ]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($token);
        $cacheItemResult = $cacheItem->get();
        $this->assertEquals(
            $expectedData,
            \json_decode($cacheItemResult, true)
        );
    }
}
