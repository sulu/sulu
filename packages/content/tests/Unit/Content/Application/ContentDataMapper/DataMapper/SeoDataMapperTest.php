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
use Sulu\Content\Application\ContentDataMapper\DataMapper\SeoDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class SeoDataMapperTest extends TestCase
{
    use ProphecyTrait;

    protected function createSeoDataMapperInstance(): SeoDataMapper
    {
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()->willReturn([
            'seo/title' => $this->createFieldMetadata('seo/title', true),
            'seo/description' => $this->createFieldMetadata('seo/description', true),
            'seo/keywords' => $this->createFieldMetadata('seo/keywords', true),
            'seo/canonicalUrl' => $this->createFieldMetadata('seo/canonicalUrl', true),
            'seo/unlocalizedField' => $this->createFieldMetadata('seo/unlocalizedField', false),
        ]);

        $formMetadataProvider->getMetadata(
            'content_seo',
            Argument::any(),
            Argument::any()
        )->willReturn($formMetadata->reveal());

        return new SeoDataMapper($formMetadataProvider->reveal());
    }

    private function createFieldMetadata(string $name, bool $multilingual): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata($name);
        $fieldMetadata->setMultilingual($multilingual);

        return $fieldMetadata;
    }

    public function testMapNoSeoInterface(): void
    {
        $data = [
            'seo' => [
                'title' => 'Seo Title',
                'description' => 'Seo Description',
                'keywords' => 'Seo Keyword 1, Seo Keyword 2',
                'canonicalUrl' => 'http://example.localhost',
            ],
            'seoHideInSitemap' => true,
            'seoNoIndex' => true,
            'seoNoFollow' => true,
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $seoMapper = $this->createSeoDataMapperInstance();
        $seoMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
        $this->expectNotToPerformAssertions();
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $seoMapper = $this->createSeoDataMapperInstance();

        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getSeoTitle());
        $this->assertNull($localizedDimensionContent->getSeoDescription());
        $this->assertNull($localizedDimensionContent->getSeoKeywords());
        $this->assertNull($localizedDimensionContent->getSeoCanonicalUrl());
        $this->assertFalse($localizedDimensionContent->getSeoHideInSitemap());
        $this->assertFalse($localizedDimensionContent->getSeoNoFollow());
        $this->assertFalse($localizedDimensionContent->getSeoNoIndex());
    }

    public function testMapData(): void
    {
        $data = [
            'seo' => [
                'title' => 'Seo Title',
                'description' => 'Seo Description',
                'keywords' => 'Seo Keyword 1, Seo Keyword 2',
                'canonicalUrl' => 'http://example.localhost',
            ],
            'seoHideInSitemap' => true,
            'seoNoIndex' => true,
            'seoNoFollow' => true,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $seoMapper = $this->createSeoDataMapperInstance();

        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Seo Title', $localizedDimensionContent->getSeoTitle());
        $this->assertSame('Seo Description', $localizedDimensionContent->getSeoDescription());
        $this->assertSame('Seo Keyword 1, Seo Keyword 2', $localizedDimensionContent->getSeoKeywords());
        $this->assertSame('http://example.localhost', $localizedDimensionContent->getSeoCanonicalUrl());
        $this->assertTrue($localizedDimensionContent->getSeoHideInSitemap());
        $this->assertTrue($localizedDimensionContent->getSeoNoFollow());
        $this->assertTrue($localizedDimensionContent->getSeoNoIndex());
        $this->assertSame([], $unlocalizedDimensionContent->getSeoData());
    }

    public function testMapWithUnlocalizedSeoProperty(): void
    {
        $data = [
            'seo' => [
                'title' => 'Seo Title',
                'unlocalizedField' => 'Unlocalized Value',
            ],
            'seoNoIndex' => true,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setSeoData(['otherField' => 'Other Value']);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setSeoData(['unlocalizedField' => 'Previously Localized Value']);

        $seoMapper = $this->createSeoDataMapperInstance();
        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(['title' => 'Seo Title'], $localizedDimensionContent->getSeoData());
        $this->assertSame([
            'otherField' => 'Other Value',
            'unlocalizedField' => 'Unlocalized Value',
        ], $unlocalizedDimensionContent->getSeoData());
        $this->assertTrue($localizedDimensionContent->getSeoNoIndex());
    }

    public function testMapWithoutUnlocalizedSeoPropertyKeepsUnlocalizedData(): void
    {
        $data = [
            'seo' => [
                'title' => 'Seo Title',
            ],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setSeoData(['unlocalizedField' => 'Unlocalized Value']);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $seoMapper = $this->createSeoDataMapperInstance();
        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(['title' => 'Seo Title'], $localizedDimensionContent->getSeoData());
        $this->assertSame(['unlocalizedField' => 'Unlocalized Value'], $unlocalizedDimensionContent->getSeoData());
    }

    public function testMapWithInvalidSeoProperty(): void
    {
        $this->markTestSkipped('TODO: we should implement this as soon as we implemented also unlocalized properties.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $data = [
            'seo' => [
                'title' => 'Seo Title',
                'description' => 'Seo Description',
                'customField' => 'Should be filtered out',
            ],
            'seoHideInSitemap' => true,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $seoMapper = $this->createSeoDataMapperInstance();
        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Seo Title', $localizedDimensionContent->getSeoTitle());
        $this->assertSame('Seo Description', $localizedDimensionContent->getSeoDescription());
        $this->assertTrue($localizedDimensionContent->getSeoHideInSitemap());
        $this->assertArrayNotHasKey('customField', $localizedDimensionContent->getSeoData());
    }

    public function testMapWithMixedValidAndInvalidProperties(): void
    {
        $this->markTestSkipped('TODO: we should implement this as soon as we implemented also unlocalized properties.');

        // @phpstan-ignore-next-line deadCode.unreachable
        $data = [
            'seo' => [
                'title' => 'Valid Title',
                'customField' => 'Invalid - not in form',
                'description' => 'Valid Description',
                'foo' => 'Invalid - not in form',
                'keywords' => 'Valid Keywords',
                'bar' => 'Invalid - not in form',
            ],
            'seoNoIndex' => true,
            'seoNoFollow' => false,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $seoMapper = $this->createSeoDataMapperInstance();
        $seoMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Valid Title', $localizedDimensionContent->getSeoTitle());
        $this->assertSame('Valid Description', $localizedDimensionContent->getSeoDescription());
        $this->assertSame('Valid Keywords', $localizedDimensionContent->getSeoKeywords());
        $this->assertTrue($localizedDimensionContent->getSeoNoIndex());
        $this->assertFalse($localizedDimensionContent->getSeoNoFollow());

        $seoData = $localizedDimensionContent->getSeoData();
        $this->assertArrayNotHasKey('customField', $seoData);
        $this->assertArrayNotHasKey('foo', $seoData);
        $this->assertArrayNotHasKey('bar', $seoData);
    }
}
