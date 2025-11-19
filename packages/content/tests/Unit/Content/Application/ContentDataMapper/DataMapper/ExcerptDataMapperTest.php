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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ContentDataMapper\DataMapper\ExcerptDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ExcerptDataMapperTest extends TestCase
{
    use ProphecyTrait;

    protected function createExcerptDataMapperInstance(): ExcerptDataMapper
    {
        return new ExcerptDataMapper();
    }

    public function testMapNoExcerptInterface(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getExcerptTitle());
        $this->assertNull($localizedDimensionContent->getExcerptDescription());
        $this->assertNull($localizedDimensionContent->getExcerptIcon());
        $this->assertNull($localizedDimensionContent->getExcerptImage());
    }

    public function testMapUnlocalizedExcerpt(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Excerpt Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertSame('Excerpt More', $localizedDimensionContent->getExcerptMore());
        $this->assertSame(['id' => 1], $localizedDimensionContent->getExcerptImage());
        $this->assertSame(['id' => 2], $localizedDimensionContent->getExcerptIcon());
    }

    public function testMapWithAllExcerptData(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Excerpt Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertSame('Excerpt More', $localizedDimensionContent->getExcerptMore());
        $this->assertSame(['id' => 1], $localizedDimensionContent->getExcerptImage());
        $this->assertSame(['id' => 2], $localizedDimensionContent->getExcerptIcon());
    }
}
