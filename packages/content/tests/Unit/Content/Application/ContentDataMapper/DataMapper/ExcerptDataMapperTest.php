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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentDataMapper\DataMapper\ExcerptDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
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
            'excerpt/title' => $this->createFieldMetadata('excerpt/title', true),
            'excerpt/description' => $this->createFieldMetadata('excerpt/description', true),
            'excerpt/more' => $this->createFieldMetadata('excerpt/more', true),
            'excerpt/image' => $this->createFieldMetadata('excerpt/image', true),
            'excerpt/icon' => $this->createFieldMetadata('excerpt/icon', true),
            'excerpt/unlocalizedField' => $this->createFieldMetadata('excerpt/unlocalizedField', false),
        ]);

        $formMetadataProvider->getMetadata(
            'content_excerpt',
            Argument::any(),
            Argument::any()
        )->willReturn($formMetadata->reveal());

        return new ExcerptDataMapper($formMetadataProvider->reveal());
    }

    private function createFieldMetadata(string $name, bool $multilingual): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata($name);
        $fieldMetadata->setMultilingual($multilingual);

        return $fieldMetadata;
    }

    public function testMapNoExcerptInterface(): void
    {
        $data = [
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
                'image' => ['id' => 1],
                'icon' => ['id' => 2],
            ],
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);

        $this->expectNotToPerformAssertions();
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
            'excerpt/title' => 'Excerpt Title',
            'excerpt/description' => 'Excerpt Description',
            'excerpt/more' => 'Excerpt More',
            'excerpt/image' => ['id' => 1],
            'excerpt/icon' => ['id' => 2],
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
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'more' => 'Excerpt More',
                'image' => ['id' => 1],
                'icon' => ['id' => 2],
            ],
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
        $this->assertSame([], $unlocalizedDimensionContent->getExcerptData());
    }

    public function testMapWithUnlocalizedExcerptProperty(): void
    {
        $data = [
            'excerpt' => [
                'title' => 'Excerpt Title',
                'unlocalizedField' => 'Unlocalized Value',
            ],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setExcerptData(['otherField' => 'Other Value']);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setExcerptData(['unlocalizedField' => 'Previously Localized Value']);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(['title' => 'Excerpt Title'], $localizedDimensionContent->getExcerptData());
        $this->assertSame([
            'otherField' => 'Other Value',
            'unlocalizedField' => 'Unlocalized Value',
        ], $unlocalizedDimensionContent->getExcerptData());
    }

    public function testMapWithoutUnlocalizedExcerptPropertyKeepsUnlocalizedData(): void
    {
        $data = [
            'excerpt' => [
                'title' => 'Excerpt Title',
            ],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setExcerptData(['unlocalizedField' => 'Unlocalized Value']);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(['title' => 'Excerpt Title'], $localizedDimensionContent->getExcerptData());
        $this->assertSame(['unlocalizedField' => 'Unlocalized Value'], $unlocalizedDimensionContent->getExcerptData());
    }

    public function testMapWithInvalidExcerptProperty(): void
    {
        $this->markTestSkipped('TODO: we should implement this as soon as we implemented also unlocalized properties.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $data = [
            'excerpt' => [
                'title' => 'Excerpt Title',
                'description' => 'Excerpt Description',
                'customField' => 'Should be filtered out',
            ],
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
        $this->markTestSkipped('TODO: we should implement this as soon as we implemented also unlocalized properties.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $data = [
            'excerpt' => [
                'title' => 'Valid Title',
                'customField' => 'Invalid - not in form',
                'description' => 'Valid Description',
                'foo' => 'Invalid - not in form',
                'image' => ['id' => 5],
                'bar' => 'Invalid - not in form',
            ],
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
