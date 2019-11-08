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
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;

class PreviewTest extends TestCase
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var PreviewRendererInterface
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
     * @var PreviewObjectProviderInterface
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
     * @var \stdClass
     */
    private $object;

    protected function setUp(): void
    {
        $this->cache = $this->prophesize(Cache::class);
        $this->renderer = $this->prophesize(PreviewRendererInterface::class);
        $this->provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $this->object = $this->prophesize(\stdClass::class);

        $providers = [$this->providerKey => $this->provider->reveal()];

        $this->preview = new Preview($providers, $this->cache->reveal(), $this->renderer->reveal());
    }

    public function testStart()
    {
        $data = ['title' => 'Sulu'];
        $dataJson = json_encode($data);

        $this->provider->getObject(1, $this->locale)->willReturn($this->object->reveal());
        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();

        $this->provider->serialize($this->object->reveal())->willReturn($dataJson);

        $token = $this->preview->start($this->providerKey, 1, $this->locale, 1, $data);

        $expectedData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();
    }

    public function testStartWithoutData()
    {
        $data = ['title' => 'Sulu is awesome'];
        $dataJson = json_encode($data);

        $this->provider->getObject(1, $this->locale)->willReturn($this->object->reveal());
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();

        $this->provider->serialize($this->object->reveal())->willReturn($dataJson);

        $token = $this->preview->start($this->providerKey, 1, $this->locale, 1);

        $expectedData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();
    }

    public function testStartWithoutProvider()
    {
        $this->expectException(ProviderNotFoundException::class);

        $this->preview->start('xxx', 1, $this->locale, 1);
    }

    public function testStop()
    {
        $this->cache->contains('123-123-123')->willReturn(true);
        $this->cache->delete('123-123-123')->shouldBeCalled();

        $this->preview->stop('123-123-123');
    }

    public function testStopNotExists()
    {
        $this->cache->contains('123-123-123')->willReturn(false);
        $this->cache->delete(Argument::any())->shouldNotBeCalled();

        $this->preview->stop('123-123-123');

        // nothing should happen
    }

    public function testExists()
    {
        $this->cache->contains('123-123-123')->willReturn(true);

        $this->assertTrue($this->preview->exists('123-123-123'));
    }

    public function testExistsNot()
    {
        $this->cache->contains('123-123-123')->willReturn(false);

        $this->assertFalse($this->preview->exists('123-123-123'));
    }

    public function testUpdate()
    {
        $data = ['title' => 'Sulu'];
        $dataJson = json_encode($data);

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => json_encode(['title' => 'test']),
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];
        $expectedData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues($this->object->reveal(), $this->locale, $data)->shouldBeCalled();
        $this->provider->serialize($this->object->reveal())->willReturn($dataJson)->shouldBeCalled();

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, true, null)
            ->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

        $result = $this->preview->update($token, $this->webspaceKey, $data, null);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testUpdateNoData()
    {
        $data = ['title' => 'Sulu'];
        $dataJson = json_encode($data);

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize(Argument::cetera())->shouldNotBeCalled();

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, true, null)->willReturn(
            '<h1 property="title">SULU</h1>'
        );

        $this->cache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->preview->update($token, $this->webspaceKey, [], null);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testUpdateTokenNotExists()
    {
        $this->expectException(TokenNotFoundException::class);

        $object = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->cache->contains($token)->willReturn(false);
        $this->cache->fetch(Argument::cetera())->shouldNotBecalled();
        $this->cache->save(Argument::cetera())->shouldNotBeCalled();
        $this->provider->deserialize(Argument::cetera())->shouldNotBeCalled();
        $this->renderer->render(Argument::cetera())->shouldNotBeCalled();

        $this->preview->update($token, $this->webspaceKey, ['title' => 'SULU'], null);
    }

    public function testUpdateWithTargetGroup()
    {
        $data = ['title' => 'Sulu'];
        $dataJson = json_encode($data);

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize(Argument::cetera())->shouldNotBeCalled();

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, true, 2)
            ->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->preview->update($token, $this->webspaceKey, [], 2);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testUpdateContext()
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];
        $dataJson = json_encode($data);

        $context = ['template' => 'expert'];

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $newObject = $this->prophesize(\stdClass::class);
        $expectedData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => json_encode(array_merge($data, $context)),
            'objectClass' => get_class($newObject->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setContext($this->object->reveal(), $this->locale, $context)->willReturn($newObject->reveal());
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize($newObject->reveal())->willReturn($expectedData['object'])->shouldBeCalled();

        $this->renderer->render($newObject->reveal(), 1, $this->webspaceKey, $this->locale, false, null)->willReturn(
            '<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>'
        );

        $this->renderer->render($newObject->reveal(), 1, $this->webspaceKey, $this->locale, true, null)
            ->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

        $result = $this->preview->updateContext($token, $this->webspaceKey, $context, null);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testUpdateContextNoContentReplacer()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "{% block content %}" could not be found in the twig template');

        $data = ['title' => 'Sulu', 'template' => 'default'];
        $dataJson = json_encode($data);

        $context = ['template' => 'expert'];

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"></div></body></html>',
        ];

        $newObject = $this->prophesize(\stdClass::class);

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setContext($this->object->reveal(), $this->locale, $context)->willReturn($newObject->reveal());

        $this->renderer->render($newObject->reveal(), 1, $this->webspaceKey, $this->locale, false, null)->willReturn(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>'
        );

        $this->preview->updateContext($token, $this->webspaceKey, $context, null);
    }

    public function testUpdateContextNoContext()
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];
        $dataJson = json_encode($data);

        $context = [];

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setContext(Argument::cetera())->shouldNotBeCalled();
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize(Argument::cetera())->shouldNotBeCalled();

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, false, null)->willReturn(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>'
        );

        $this->cache->save(Argument::cetera())->shouldNotBeCalled();

        $result = $this->preview->updateContext($token, $this->webspaceKey, $context, null);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testUpdateContextWithTargetGroup()
    {
        $data = ['title' => 'Sulu', 'template' => 'default'];
        $dataJson = json_encode($data);

        $context = ['template' => 'expert'];

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $newObject = $this->prophesize(\stdClass::class);
        $expectedData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => json_encode(array_merge($data, $context)),
            'objectClass' => get_class($newObject->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($dataJson, $cacheData['objectClass'])->willReturn($this->object->reveal());
        $this->provider->setContext($this->object->reveal(), $this->locale, $context)->willReturn($newObject->reveal());
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize($newObject->reveal())->willReturn($expectedData['object'])->shouldBeCalled();

        $this->renderer->render($newObject->reveal(), 1, $this->webspaceKey, $this->locale, false, 2)->willReturn(
            '<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>'
        );

        $this->renderer->render($newObject->reveal(), 1, $this->webspaceKey, $this->locale, true, 2)
            ->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

        $result = $this->preview->updateContext($token, $this->webspaceKey, $context, 2);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testRender()
    {
        $data = ['title' => 'Sulu'];
        $dataJson = json_encode($data);

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];
        $expectedData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize($this->object->reveal())->willReturn($dataJson)->shouldBeCalled();

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, false, null)
            ->willReturn('<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>');

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, true, null)
            ->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

        $result = $this->preview->render($token, $this->webspaceKey, $this->locale, null);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }

    public function testRenderWithTargetGroup()
    {
        $data = ['title' => 'Sulu'];
        $dataJson = json_encode($data);

        $token = md5(sprintf('%s.%s.%s.%s', $this->providerKey, 1, $this->locale, 1));
        $cacheData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => null,
        ];
        $expectedData = [
            'id' => '1',
            'locale' => $this->locale,
            'providerKey' => $this->providerKey,
            'object' => $dataJson,
            'objectClass' => get_class($this->object->reveal()),
            'userId' => 1,
            'html' => '<html><body><div id="content"><!-- CONTENT-REPLACER --></div></body></html>',
        ];

        $this->cache->contains($token)->willReturn(true);
        $this->cache->fetch($token)->willReturn(json_encode($cacheData));

        $this->provider->deserialize($cacheData['object'], $cacheData['objectClass'])->willReturn($this->object);
        $this->provider->setValues(Argument::cetera())->shouldNotBeCalled();
        $this->provider->serialize($this->object->reveal())->willReturn($dataJson)->shouldBeCalled();

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, false, 2)
            ->willReturn('<html><body><div id="content"><!-- CONTENT-REPLACER --><h1 property="title">SULU</h1><!-- CONTENT-REPLACER --></div></body></html>');

        $this->renderer->render($this->object->reveal(), 1, $this->webspaceKey, $this->locale, true, 2)
            ->willReturn('<h1 property="title">SULU</h1>');

        $this->cache->save(
            $token,
            Argument::that(
                function($json) use ($expectedData) {
                    $this->assertEquals($expectedData, json_decode($json, true));

                    return true;
                }
            ),
            $this->cacheLifeTime
        )->shouldBeCalled();

        $result = $this->preview->render($token, $this->webspaceKey, $this->locale, 2);

        $this->assertEquals(
            '<html><body><div id="content"><h1 property="title">SULU</h1></div></body></html>',
            $result
        );
    }
}
