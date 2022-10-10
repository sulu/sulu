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

use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;

class PreviewTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<Cache>
     */
    private $cache;

    /**
     * @var ObjectProphecy<PreviewRendererInterface>
     */
    private $renderer;

    /**
     * @var int
     */
    private $cacheLifeTime = 3600;

    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var ObjectProphecy<PreviewObjectProviderInterface>
     */
    private $provider;

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
     * @var ObjectProphecy<\stdClass>
     */
    private $object;

    protected function setUp(): void
    {
        $this->cache = $this->prophesize(Cache::class);
        $this->renderer = $this->prophesize(PreviewRendererInterface::class);
        $this->provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $this->object = $this->prophesize(\stdClass::class);

        $providers = [$this->providerKey => $this->provider->reveal()];
        $objectProviderRegistry = new PreviewObjectProviderRegistry($providers);

        $this->preview = new Preview($objectProviderRegistry, $this->cache->reveal(), $this->renderer->reveal());
    }

    public function testStart(): void
    {
        $data = ['title' => 'Sulu'];
        $dataJson = \json_encode($data);

        $this->provider->getObject(1, $this->locale)->willReturn($this->object->reveal());
        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();

        $this->provider->serialize($this->object->reveal())->willReturn($dataJson);

        $token = $this->preview->start($this->providerKey, 1, 1, $data, ['locale' => $this->locale]);

        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, \json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();
    }

    public function testStartWithoutData(): void
    {
        $data = ['title' => 'Sulu is awesome'];
        $dataJson = \json_encode($data);

        $this->provider->getObject(1, $this->locale)->willReturn($this->object->reveal());
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();

        $this->provider->serialize($this->object->reveal())->willReturn($dataJson);

        $token = $this->preview->start($this->providerKey, 1, 1, [], ['locale' => $this->locale]);

        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, \json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();
    }

    public function testStartWithoutProvider(): void
    {
        $this->expectException(ProviderNotFoundException::class);

        $this->preview->start('xxx', 1, 1, ['locale' => $this->locale]);
    }

    public function testStop(): void
    {
        $this->cache->contains('123-123-123')->willReturn(true);
        $this->cache->delete('123-123-123')->shouldBeCalled();

        $this->preview->stop('123-123-123');
    }

    public function testStopNotExists(): void
    {
        $this->cache->contains('123-123-123')->willReturn(false);
        $this->cache->delete(Argument::any())->shouldNotBeCalled();

        $this->preview->stop('123-123-123');

        // nothing should happen
    }

    public function testExists(): void
    {
        $this->cache->contains('123-123-123')->willReturn(true);

        $this->assertTrue($this->preview->exists('123-123-123'));
    }

    public function testExistsNot(): void
    {
        $this->cache->contains('123-123-123')->willReturn(false);

        $this->assertFalse($this->preview->exists('123-123-123'));
    }

    public function testUpdate(): void
    {
        $data = ['title' => 'Sulu'];
        $dataJson = \json_encode($data);

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => \json_encode(['title' => 'test']),
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];
        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();
        $this->provider->serialize($this->object->reveal())->willReturn($dataJson)->shouldBeCalled();

        $this->renderer->render(
            $this->object->reveal(),
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, \json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

        $result = $this->preview->update(
            $token,
            $data,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testUpdateNoData(): void
    {
        $data = ['title' => 'Sulu'];
        $dataJson = \json_encode($data);

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize(Argument::cetera())->shouldNotBeCalled();

        $this->renderer->render(
            $this->object->reveal(),
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->preview->update(
            $token,
            [],
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        );

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testUpdateTokenNotExists(): void
    {
        $this->expectException(TokenNotFoundException::class);

        $object = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->cache->contains($token)->willReturn(false);
        $this->cache->fetch(Argument::cetera())->shouldNotBecalled();
        $this->cache->save(Argument::cetera())->shouldNotBeCalled();
        $this->provider->deserialize(Argument::cetera())->shouldNotBeCalled();
        $this->renderer->render(Argument::cetera())->shouldNotBeCalled();

        $this->preview->update($token, ['title' => 'SULU'], ['webspaceKey' => $this->webspaceKey]);
    }

    public function testUpdateWithOptions(): void
    {
        $data = ['title' => 'Sulu'];
        $dataJson = \json_encode($data);

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize(Argument::cetera())->shouldNotBeCalled();

        $this->renderer->render(
            $this->object->reveal(),
            1,
            true,
            [
                'targetGroupId' => null,
                'segmentKey' => 'w',
                'webspaceKey' => $this->webspaceKey,
                'locale' => $this->locale,
            ]
        )->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->preview->update(
            $token,
            [],
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
    }

    public function testUpdateContext(): void
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];
        $dataJson = \json_encode($data);

        $context = ['template' => 'expert'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $newObject = $this->prophesize(\stdClass::class);
        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => \json_encode(\array_merge($data, $context)),
            'objectClass' => \get_class($newObject->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setContext($this->object->reveal(), $this->locale, $context)->willReturn($newObject->reveal());
        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();
        $this->provider->serialize($newObject->reveal())->willReturn($expectedData['object'])->shouldBeCalled();

        $this->renderer->render(
            $newObject->reveal(),
            1,
            false,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn(
            '<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>'
        );

        $this->renderer->render(
            $newObject->reveal(),
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, \json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

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
    }

    public function testUpdateContextNoContentReplacer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "{% block content %}" could not be found in the twig template');

        $data = ['title' => 'Sulu', 'template' => 'default'];
        $dataJson = \json_encode($data);

        $context = ['template' => 'expert'];

        $token = \md5(\sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"></div></body></html>',
        ];

        $newObject = $this->prophesize(\stdClass::class);

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();
        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setContext($this->object->reveal(), $this->locale, $context)->willReturn($newObject->reveal());

        $this->renderer->render(
            $newObject->reveal(),
            1,
            false,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>');

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
        $dataJson = \json_encode($data);

        $context = [];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();
        $this->provider->setContext(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize(Argument::cetera())->shouldNotBeCalled();

        $this->renderer->render(
            $this->object->reveal(),
            1,
            false,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>'
        );

        $this->cache->save(Argument::cetera())->shouldNotBeCalled();

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
    }

    public function testUpdateContextWithOptions(): void
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];
        $dataJson = \json_encode($data);

        $context = ['template' => 'expert'];

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $newObject = $this->prophesize(\stdClass::class);
        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => \json_encode(\array_merge($data, $context)),
            'objectClass' => \get_class($newObject->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setContext($this->object->reveal(), $this->locale, $context)->willReturn($newObject->reveal());
        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();
        $this->provider->serialize($newObject->reveal())->willReturn($expectedData['object'])->shouldBeCalled();

        $this->renderer->render(
            $newObject->reveal(),
            1,
            false,
            ['targetGroupId' => 2, 'segmentKey' => null, 'webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn(
            '<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>'
        );

        $this->renderer->render(
            $newObject->reveal(),
            1,
            true,
            ['targetGroupId' => 2, 'segmentKey' => null, 'webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, \json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

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
    }

    public function testRender(): void
    {
        $data = ['title' => 'Sulu'];
        $dataJson = \json_encode($data);

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];
        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize($this->object->reveal())->willReturn($dataJson)->shouldBeCalled();

        $this->renderer->render(
            $this->object->reveal(),
            1,
            false,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->willReturn('<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>');

        $this->renderer->render(
            $this->object->reveal(),
            1,
            true,
            ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]
        )->shouldBeCalled()->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, \json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

        $result = $this->preview->render($token, ['webspaceKey' => $this->webspaceKey, 'locale' => $this->locale]);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testRenderWithOptions(): void
    {
        $data = ['title' => 'Sulu'];
        $dataJson = \json_encode($data);

        $token = \md5(\sprintf('%s.%s.%s', $this->providerKey, 1, 1));
        $cacheData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];
        $expectedData = [
            'id' => '1',
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => \get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(\json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize($this->object->reveal())->willReturn($dataJson)->shouldBeCalled();

        $this->renderer->render(
            $this->object->reveal(),
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
            $this->object->reveal(),
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

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, \json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

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
    }
}
