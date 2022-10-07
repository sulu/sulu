<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Tests\Unit\Tag;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagNotFoundException;
use Sulu\Bundle\MarkupBundle\Tag\TagRegistry;

class TagRegistryTest extends TestCase
{
    use ProphecyTrait;

    public function testGetTag(): void
    {
        $tag = $this->prophesize(TagInterface::class)->reveal();
        $registry = new TagRegistry(['html' => ['sulu' => ['test' => $tag]]]);

        $this->assertEquals($tag, $registry->getTag('test', 'html'));
    }

    public function testGetTagNotFound(): void
    {
        $this->expectException(TagNotFoundException::class);

        $registry = new TagRegistry(['test' => $this->prophesize(TagInterface::class)->reveal()]);
        $registry->getTag('test-2', 'html');
    }

    public function testGetTypeNotFound(): void
    {
        $this->expectException(TagNotFoundException::class);

        $tag = $this->prophesize(TagInterface::class)->reveal();
        $registry = new TagRegistry(['html' => ['sulu' => ['test' => $tag]]]);
        $registry->getTag('test-2', 'xml');
    }

    public function testGetTagNoTag(): void
    {
        $this->expectException(TagNotFoundException::class);

        $registry = new TagRegistry([]);
        $registry->getTag('test-2', 'html');
    }
}
