<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\DocumentManager\Metadata;

class MetadataTest extends TestCase
{
    /**
     * @var Metadata
     */
    private $metadata;

    public function setUp(): void
    {
        $this->metadata = new Metadata();
    }

    /**
     * It should throw an exception if no class is set and the ReflectionClass
     * is requested.
     */
    public function testNoClassGetReflection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->metadata->getReflectionClass();
    }

    /**
     * It should return the reflection class.
     */
    public function testReflectionClass(): void
    {
        $this->metadata->setClass('\stdClass');
        $reflection = $this->metadata->getReflectionClass();

        $this->assertInstanceOf('ReflectionClass', $reflection);
        $this->assertEquals('stdClass', $reflection->name);

        $this->assertSame($reflection, $this->metadata->getReflectionClass());

        $this->metadata->setClass('\stdClass');
        $this->assertNotSame($reflection, $this->metadata->getReflectionClass());
    }

    public function testGetFieldMappingsEmpty(): void
    {
        $this->assertIsArray($this->metadata->getFieldMappings());
    }
}
