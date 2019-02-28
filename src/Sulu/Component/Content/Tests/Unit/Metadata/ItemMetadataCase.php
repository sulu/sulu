<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Metadata;

abstract class ItemMetadataCase extends \PHPUnit\Framework\TestCase
{
    abstract public function getMetadata();

    public function testGetTagNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $metadata = $this->getMetadata();
        $metadata->getTag('foo');
    }

    public function testGetTag()
    {
        $metadata = $this->getMetadata();
        $tag = ['name' => 'foo'];
        $metadata->setTags([$tag]);
        $this->assertEquals($tag, $metadata->getTag('foo'));
    }

    public function testGetTitle()
    {
        $metadata = $this->getMetadata();
        $metadata->setTitles(['fr' => 'Foobar']);
        $this->assertEquals('Foobar', $metadata->getTitle('fr'));
    }

    public function testGetNotExistingTitle()
    {
        $metadata = $this->getMetadata();
        $metadata->setName('foobar');
        $this->assertNull($metadata->getTitle('es'));
    }

    public function testGetParameters()
    {
        $metadata = $this->getMetadata();
        $metadata->setParameters([
            'param1' => 'param',
        ]);
        $this->assertEquals('param', $metadata->getParameter('param1'));
    }

    public function testGetParametersInvalid()
    {
        $this->expectExceptionMessage('Unknown parameter "param5", known parameters: "param1"');
        $this->expectException(\InvalidArgumentException::class);
        $metadata = $this->getMetadata();
        $metadata->setParameters([
            'param1' => 'param',
        ]);
        $metadata->getParameter('param5');
    }
}
