<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ContentMerger\Merger;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentMerger\Merger\LinkMerger;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class LinkMergerTest extends TestCase
{
    private LinkMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new LinkMerger();
    }

    public function testMerge(): void
    {
        $example = new Example();

        $source = new ExampleDimensionContent($example);
        $source->setLinkData([
            'provider' => 'example',
            'href' => '019a251e-4251-7779-be14-af69441496e7',
        ]);

        $target = new ExampleDimensionContent($example);

        $this->merger->merge($target, $source);

        $this->assertSame([
            'provider' => 'example',
            'href' => '019a251e-4251-7779-be14-af69441496e7',
        ], $target->getLinkData());
    }

    public function testMergeLinkDataNull(): void
    {
        $example = new Example();

        $source = new ExampleDimensionContent($example);
        $source->setLinkData(null);

        $target = new ExampleDimensionContent($example);
        $target->setLinkData(['provider' => 'old', 'href' => 'data']);

        $this->merger->merge($target, $source);

        $this->assertSame(['provider' => 'old', 'href' => 'data'], $target->getLinkData());
    }
}
