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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentDataMapper\DataMapper\ExcerptDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ExcerptDataMapperTest extends TestCase
{
    use ProphecyTrait;

    protected function createExcerptDataMapperInstance(): ExcerptDataMapper
    {
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()->willReturn([
            'excerptTitle' => [],
            'excerptDescription' => [],
            'excerptMore' => [],
            'excerptImage' => [],
            'excerptIcon' => [],
        ]);

        $formMetadataProvider->getMetadata(
            'content_excerpt',
            Argument::any(),
            Argument::any()
        )->willReturn($formMetadata->reveal());

        $excerptForms = [
            'content_excerpt_metadata' => [
                'instanceOf' => ExcerptInterface::class,
            ],
        ];

        return new ExcerptDataMapper($formMetadataProvider->reveal(), $excerptForms);
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

        $this->assertTrue(true); // Avoid risky test as this is an early return test // @phpstan-ignore method.alreadyNarrowedType
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

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

        // Without locale, no mapping should occur
        $this->assertNull($localizedDimensionContent->getExcerptTitle());
        $this->assertNull($localizedDimensionContent->getExcerptDescription());
        $this->assertNull($localizedDimensionContent->getExcerptMore());
        $this->assertNull($localizedDimensionContent->getExcerptImage());
        $this->assertNull($localizedDimensionContent->getExcerptIcon());
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
        $localizedDimensionContent->setLocale('en');

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Excerpt Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertSame('Excerpt More', $localizedDimensionContent->getExcerptMore());
        $this->assertSame(['id' => 1], $localizedDimensionContent->getExcerptImage());
        $this->assertSame(['id' => 2], $localizedDimensionContent->getExcerptIcon());
    }

    public function testMapWithInvalidExcerptProperty(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptCustomField' => 'Should be filtered out',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Excerpt Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertArrayNotHasKey('customField', $localizedDimensionContent->getExcerptData());
    }

    public function testMapWithMixedValidAndInvalidProperties(): void
    {
        $data = [
            'excerptTitle' => 'Valid Title',
            'excerptCustomField' => 'Invalid - not in form',
            'excerptDescription' => 'Valid Description',
            'excerptFoo' => 'Invalid - not in form',
            'excerptImage' => ['id' => 5],
            'excerptBar' => 'Invalid - not in form',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Valid Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Valid Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertSame(['id' => 5], $localizedDimensionContent->getExcerptImage());

        $excerptData = $localizedDimensionContent->getExcerptData();
        $this->assertArrayNotHasKey('customField', $excerptData);
        $this->assertArrayNotHasKey('foo', $excerptData);
        $this->assertArrayNotHasKey('bar', $excerptData);
    }
}
