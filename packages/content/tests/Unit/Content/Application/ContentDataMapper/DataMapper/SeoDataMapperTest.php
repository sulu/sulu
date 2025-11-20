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
use Sulu\Content\Application\ContentDataMapper\DataMapper\SeoDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;
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
            'seoTitle' => [],
            'seoDescription' => [],
            'seoKeywords' => [],
            'seoCanonicalUrl' => [],
        ]);

        $formMetadataProvider->getMetadata(
            'content_seo',
            Argument::any(),
            Argument::any()
        )->willReturn($formMetadata->reveal());

        $seoForms = [
            'content_seo' => [
                'instanceOf' => SeoInterface::class,
            ],
        ];

        return new SeoDataMapper($formMetadataProvider->reveal(), $seoForms);
    }

    public function testMapNoSeoInterface(): void
    {
        $data = [
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoCanonicalUrl' => 'http://example.localhost',
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
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoKeywords' => 'Seo Keyword 1, Seo Keyword 2',
            'seoCanonicalUrl' => 'http://example.localhost',
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
    }

    public function testMapWithInvalidSeoProperty(): void
    {
        $data = [
            'seoTitle' => 'Seo Title',
            'seoDescription' => 'Seo Description',
            'seoCustomField' => 'Should be filtered out',
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
        $data = [
            'seoTitle' => 'Valid Title',
            'seoCustomField' => 'Invalid - not in form',
            'seoDescription' => 'Valid Description',
            'seoFoo' => 'Invalid - not in form',
            'seoKeywords' => 'Valid Keywords',
            'seoBar' => 'Invalid - not in form',
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
