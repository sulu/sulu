<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview;

use Doctrine\Common\Cache\Cache;
use Prophecy\Argument;
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TokenNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;

class PreviewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Cache
     */
    private $dataCache;

    /**
     * @var PreviewRendererInterface
     */
    private $renderer;

    /**
     * @var int
     */
    private $cacheLifeTime = 7200;

    protected function setUp()
    {
        $this->dataCache = $this->prophesize(Cache::class);
        $this->renderer = $this->prophesize(PreviewRendererInterface::class);
    }

    protected function getPreview(array $objectProviderMocks = [])
    {
        $objectProvider = [];
        foreach ($objectProviderMocks as $key => $objectProviderMock) {
            $objectProvider[$key] = $objectProviderMock->reveal();
        }

        return new Preview(
            $objectProvider, $this->dataCache->reveal(), $this->renderer->reveal(), $this->cacheLifeTime
        );
    }

    public function testStart()
    {
        $object = $this->prophesize(\stdClass::class);

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->getObject(1, 'de')->willReturn($object->reveal());
        $provider->setValues($object->reveal(), 'de', ['title' => 'SULU'])->shouldBeCalled();
        $provider->serialize($object->reveal())->willReturn('{"title": "SULU"}');

        $preview = $this->getPreview([get_class($object->reveal()) => $provider]);
        $token = $preview->start(get_class($object->reveal()), 1, 1, 'sulu_io', 'de', ['title' => 'SULU']);

        $this->dataCache->save($token, get_class($object->reveal()) . "\n{\"title\": \"SULU\"}", $this->cacheLifeTime)
            ->shouldBeCalled();
    }

    public function testStartWithoutData()
    {
        $object = $this->prophesize(\stdClass::class);

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->getObject(1, 'de')->willReturn($object->reveal());
        $provider->setValues(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $provider->serialize($object->reveal())->willReturn('{"title": "SULU"}');

        $preview = $this->getPreview([get_class($object->reveal()) => $provider]);
        $token = $preview->start(get_class($object->reveal()), 1, 1, 'sulu_io', 'de');

        $this->dataCache->save($token, get_class($object->reveal()) . "\n{\"title\": \"SULU\"}", $this->cacheLifeTime)
            ->shouldBeCalled();
    }

    public function testStartWithoutProvider()
    {
        $this->setExpectedException(ProviderNotFoundException::class);

        $preview = $this->getPreview();
        $preview->start('\\Example', 1, 1, 'sulu_io', 'de');
    }

    public function testStop()
    {
        $this->dataCache->contains('123-123-123')->willReturn(true);
        $this->dataCache->delete('123-123-123')->shouldBeCalled();

        $preview = $this->getPreview();
        $preview->stop('123-123-123');
    }

    public function testStopNotExists()
    {
        $this->dataCache->contains('123-123-123')->willReturn(false);

        $preview = $this->getPreview();
        $preview->stop('123-123-123');

        // nothing should happen
    }

    public function testExists()
    {
        $this->dataCache->contains('123-123-123')->willReturn(true);

        $preview = $this->getPreview();
        $this->assertTrue($preview->exists('123-123-123'));
    }

    public function testExistsNot()
    {
        $this->dataCache->contains('123-123-123')->willReturn(false);

        $preview = $this->getPreview();
        $this->assertFalse($preview->exists('123-123-123'));
    }

    public function testUpdate()
    {
        $object = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->dataCache->contains($token)->willReturn(true);
        $this->dataCache->fetch($token)->willReturn(get_class($object->reveal()) . "\n{\"title\": \"test\"}");
        $this->dataCache->save($token, get_class($object->reveal()) . "\n{\"title\": \"SULU\"}", $this->cacheLifeTime)
            ->shouldBeCalled();

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->deserialize('{"title": "test"}', get_class($object->reveal()))->willReturn($object->reveal());
        $provider->setValues($object->reveal(), 'de', ['title' => 'SULU'])->shouldBeCalled();
        $provider->serialize($object->reveal())->willReturn('{"title": "SULU"}');
        $provider->getId($object->reveal())->willReturn(1);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de', true)
            ->willReturn('<h1 property="title">SULU</h1>');

        $preview = $this->getPreview([get_class($object->reveal()) => $provider]);
        $changes = $preview->update($token, 'sulu_io', 'de', ['title' => 'SULU']);

        $this->assertEquals(['title' => [['property' => 'title', 'html' => 'SULU']]], $changes);
    }

    public function testUpdateNoData()
    {
        $preview = $this->getPreview();
        $changes = $preview->update('123-123-123', 'sulu_io', 'de', []);

        $this->assertEquals([], $changes);
    }

    public function testUpdateTokenNotExists()
    {
        $this->setExpectedException(TokenNotFoundException::class);

        $object = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->dataCache->contains($token)->willReturn(false);
        $this->dataCache->fetch($token)->shouldNotBecalled();
        $this->dataCache->save($token, Argument::any(), Argument::any())->shouldNotBeCalled();

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->deserialize(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->renderer->render(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $preview = $this->getPreview([get_class($object->reveal()) => $provider]);
        $preview->update($token, 'sulu_io', 'de', ['title' => 'SULU']);
    }

    public function testUpdateContext()
    {
        $object = $this->prophesize(\stdClass::class);
        $newObject = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->dataCache->contains($token)->willReturn(true);
        $this->dataCache->fetch($token)->willReturn(get_class($object->reveal()) . "\n{\"title\": \"test\"}");
        $this->dataCache->save($token, get_class($newObject->reveal()) . "\n{\"title\": \"SULU\"}", $this->cacheLifeTime)
            ->shouldBeCalled();

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->deserialize('{"title": "test"}', get_class($object->reveal()))->willReturn($object->reveal());
        $provider->setContext($object->reveal(), 'de', ['template' => 'test-template'])
            ->shouldBeCalled()->willReturn($newObject->reveal());
        $provider->setValues($newObject->reveal(), 'de', ['title' => 'SULU'])->shouldBeCalled();
        $provider->serialize($newObject->reveal())->willReturn('{"title": "SULU"}');
        $provider->getId($newObject->reveal())->willReturn(1);

        $this->renderer->render($newObject->reveal(), 1, 'sulu_io', 'de')
            ->willReturn('<html><body><h1 property="title">SULU</h1></html></body>');

        $preview = $this->getPreview(
            [get_class($object->reveal()) => $provider, get_class($newObject->reveal()) => $provider]
        );
        $response = $preview->updateContext(
            $token,
            'sulu_io',
            'de',
            ['template' => 'test-template'],
            ['title' => 'SULU']
        );

        $this->assertEquals('<html><body><h1 property="title">SULU</h1></html></body>', $response);
    }

    public function testUpdateContextNoContext()
    {
        $object = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->dataCache->contains($token)->willReturn(true);
        $this->dataCache->fetch($token)->willReturn(get_class($object->reveal()) . "\n{\"title\": \"test\"}");

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->deserialize('{"title": "test"}', get_class($object->reveal()))->willReturn($object->reveal());
        $provider->getId($object->reveal())->willReturn(1);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de')
            ->willReturn('<html><body><h1 property="title">SULU</h1></html></body>');

        $preview = $this->getPreview([get_class($object->reveal()) => $provider]);
        $response = $preview->updateContext(
            $token,
            'sulu_io',
            'de',
            [],
            ['title' => 'SULU']
        );

        $this->assertEquals('<html><body><h1 property="title">SULU</h1></html></body>', $response);
    }

    public function testUpdateContextNoData()
    {
        $object = $this->prophesize(\stdClass::class);
        $newObject = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->dataCache->contains($token)->willReturn(true);
        $this->dataCache->fetch($token)->willReturn(get_class($object->reveal()) . "\n{\"title\": \"test\"}");
        $this->dataCache->save($token, get_class($newObject->reveal()) . "\n{\"title\": \"test\"}", $this->cacheLifeTime)
            ->shouldBeCalled();

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->deserialize('{"title": "test"}', get_class($object->reveal()))->willReturn($object->reveal());
        $provider->setContext($object->reveal(), 'de', ['template' => 'test-template'])
            ->shouldBeCalled()->willReturn($newObject->reveal());
        $provider->serialize($newObject->reveal())->willReturn('{"title": "test"}');
        $provider->getId($newObject->reveal())->willReturn(1);

        $this->renderer->render($newObject->reveal(), 1, 'sulu_io', 'de')
            ->willReturn('<html><body><h1 property="title">test</h1></html></body>');

        $preview = $this->getPreview(
            [get_class($object->reveal()) => $provider, get_class($newObject->reveal()) => $provider]
        );
        $response = $preview->updateContext(
            $token,
            'sulu_io',
            'de',
            ['template' => 'test-template'],
            []
        );

        $this->assertEquals('<html><body><h1 property="title">test</h1></html></body>', $response);
    }

    public function testRender()
    {
        $object = $this->prophesize(\stdClass::class);

        $token = '123-123-123';
        $this->dataCache->contains($token)->willReturn(true);
        $this->dataCache->fetch($token)->willReturn(get_class($object->reveal()) . "\n{\"title\": \"test\"}");

        $provider = $this->prophesize(PreviewObjectProviderInterface::class);
        $provider->deserialize('{"title": "test"}', get_class($object->reveal()))->willReturn($object->reveal());
        $provider->getId($object->reveal())->willReturn(1);

        $this->renderer->render($object->reveal(), 1, 'sulu_io', 'de')
            ->willReturn('<h1 property="title">test</h1>');

        $preview = $this->getPreview([get_class($object->reveal()) => $provider]);
        $response = $preview->render($token, 'sulu_io', 'de');

        $this->assertEquals('<h1 property="title">test</h1>', $response);
    }
}
