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
use Sulu\Content\Application\ContentMerger\Merger\ContentBehaviorMerger;
use Sulu\Content\Domain\Model\ContentBehaviorInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentBehaviorMergerTest extends TestCase
{
    private ContentBehaviorMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new ContentBehaviorMerger();
    }

    public function testMerge(): void
    {
        $example = new Example();

        $source = new ExampleDimensionContent($example);
        $source->setBehavior(ContentBehaviorInterface::BEHAVIOR_INTERNAL);
        $source->setBehaviorData(['internal' => ['href' => 'uuid-123']]);

        $target = new ExampleDimensionContent($example);

        $this->merger->merge($target, $source);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_INTERNAL, $target->getBehavior());
        $this->assertSame(['internal' => ['href' => 'uuid-123']], $target->getBehaviorData());
    }

    public function testMergeBehaviorDataNull(): void
    {
        $example = new Example();

        $source = new ExampleDimensionContent($example);
        $source->setBehavior(ContentBehaviorInterface::BEHAVIOR_CONTENT);
        $source->setBehaviorData(null);

        $target = new ExampleDimensionContent($example);
        $target->setBehaviorData(['old' => 'data']);

        $this->merger->merge($target, $source);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_CONTENT, $target->getBehavior());
        $this->assertSame(['old' => 'data'], $target->getBehaviorData());
    }
}
