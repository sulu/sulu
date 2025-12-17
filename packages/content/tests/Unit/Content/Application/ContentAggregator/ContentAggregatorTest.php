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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentAggregator;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregator;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentAggregatorTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    protected function createContentAggregatorInstance(
        ContentMergerInterface $contentMerger,
        bool $debug = false
    ): ContentAggregatorInterface {
        return new ContentAggregator(
            $contentMerger,
            $debug
        );
    }

    public function testAggregate(): void
    {
        $example = new Example();
        self::setPrivateProperty($example, 'id', 1);

        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage(DimensionContentInterface::STAGE_DRAFT);
        $dimensionContent1->setLocale(null);
        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setStage(DimensionContentInterface::STAGE_DRAFT);
        $dimensionContent2->setLocale('de');

        $example->addDimensionContent($dimensionContent1);
        $example->addDimensionContent($dimensionContent2);

        $attributes = [
            'locale' => 'de',
        ];

        $mergedDimensionContent = new ExampleDimensionContent($example);
        $mergedDimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $mergedDimensionContent->setLocale('de');

        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentMerger->merge(Argument::that(function($arg) {
            return $arg instanceof DimensionContentCollectionInterface;
        }))
            ->willReturn($mergedDimensionContent)
            ->shouldBeCalled();

        $contentAggregator = $this->createContentAggregatorInstance(
            $contentMerger->reveal(),
        );

        $this->assertSame($mergedDimensionContent, $contentAggregator->aggregate($example, $attributes));
    }

    public function testAggregateNotFound(): void
    {
        $this->expectException(ContentNotFoundException::class);

        $example = new Example();
        self::setPrivateProperty($example, 'id', 1);

        $attributes = [
            'locale' => 'de',
        ];

        $contentMerger = $this->prophesize(ContentMergerInterface::class);
        $contentMerger->merge(Argument::cetera())->shouldNotBeCalled();

        $contentAggregator = $this->createContentAggregatorInstance(
            $contentMerger->reveal()
        );

        $contentAggregator->aggregate($example, $attributes);
    }
}
