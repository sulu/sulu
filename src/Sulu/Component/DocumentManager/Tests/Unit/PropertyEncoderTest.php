<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PropertyEncoder;

class PropertyEncoderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var ObjectProphecy<NamespaceRegistry>
     */
    private $namespaceRegistry;

    public function setUp(): void
    {
        $map = [
            'system' => 'nsys',
            'system_localized' => 'lsys',
        ];

        $this->namespaceRegistry = $this->prophesize(NamespaceRegistry::class);
        $this->namespaceRegistry->getPrefix(Argument::type('string'))->will(function($args) use ($map) {
            return $map[$args[0]];
        });
        $this->encoder = new PropertyEncoder($this->namespaceRegistry->reveal());
    }

    /**
     * It should encode localized system properties.
     */
    public function testEncodeLocalizedSystem(): void
    {
        $name = $this->encoder->localizedSystemName('created', 'fr');
        $this->assertEquals('lsys:fr-created', $name);
    }

    /**
     * It should encode system properties.
     */
    public function testEncodeSystem(): void
    {
        $name = $this->encoder->systemName('created');
        $this->assertEquals('nsys:created', $name);
    }

    /**
     * It should throw exception.
     */
    public function testEncodeLocalizedSystemEmptyLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->encoder->encode('system_localized', 'test', null);
    }

    /**
     * It should throw exception.
     */
    public function testEncodeLocalizedContentEmptyLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->encoder->encode('content_localized', 'test', null);
    }

    /**
     * It should throw exception.
     */
    public function testLocalizedContentEmptyLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->encoder->encode('content_localized', 'test', null);
    }

    /**
     * It should throw exception.
     */
    public function testLocalizedSystemEmptyLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->encoder->encode('system_localized', 'test', null);
    }
}
