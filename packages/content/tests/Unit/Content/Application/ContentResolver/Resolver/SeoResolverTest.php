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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Resolver\SeoResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class SeoResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveWithNonTemplateInterface(): void
    {
        $templateResolver = new SeoResolver(
            $this->prophesize(MetadataProviderInterface::class)->reveal(),
            $this->prophesize(MetadataResolver::class)->reveal(),
        );

        self::assertNull($templateResolver->resolve($this->prophesize(DimensionContentInterface::class)->reveal()));
    }

    public function testResolveWithPropertiesNotInMetadata(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $dimensionContent->setSeoData([
            'title' => 'Sulu',
        ]);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('content_seo', 'en', ['instanceOf' => ExampleDimensionContent::class])
            ->willReturn($formMetadata->reveal());

        $metadataResolver = $this->prophesize(MetadataResolver::class);
        $metadataResolver->resolveItems(Argument::any(), Argument::any(), Argument::any())
            ->willReturn([]);

        $resolver = new SeoResolver(
            $formMetadataProvider->reveal(),
            $metadataResolver->reveal(),
        );

        $contentView = $resolver->resolve($dimensionContent, ['seoTitle' => 'seo.title', 'nonExistent' => 'seo.nonExistent']);
        self::assertInstanceOf(ContentView::class, $contentView);

        $content = $contentView->getContent();
        self::assertIsArray($content);
        self::assertCount(2, $content);
        self::assertArrayHasKey('seoTitle', $content);
        self::assertNull($content['seoTitle']);
        self::assertArrayHasKey('nonExistent', $content);
        self::assertNull($content['nonExistent']);
    }

    public function testResolve(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $dimensionContent->setSeoData([
            'title' => 'Sulu',
            'description' => 'Sulu is awesome',
            'keywords' => 'Sulu, awesome',
            'canonicalUrl' => 'https://sulu.io',
        ]);
        $dimensionContent->setSeoNoIndex(true);
        $dimensionContent->setSeoNoFollow(true);
        $dimensionContent->setSeoHideInSitemap(true);

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('content_seo', 'en', ['instanceOf' => ExampleDimensionContent::class])
            ->willReturn($formMetadata->reveal());

        $metadataResolver = $this->prophesize(MetadataResolver::class);
        $metadataResolver->resolveItems(Argument::any(), Argument::any(), Argument::any())
            ->willReturn([]);

        $templateResolver = new SeoResolver(
            $formMetadataProvider->reveal(),
            $metadataResolver->reveal(),
        );

        $contentView = $templateResolver->resolve($dimensionContent);

        self::assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        self::assertIsArray($content);
    }

    public function testResolveKeepsSeoRootKey(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getFlatFieldMetadata()
            ->willReturn([]);
        $formMetadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $formMetadataProvider->getMetadata('content_seo', 'en', ['instanceOf' => ExampleDimensionContent::class])
            ->willReturn($formMetadata->reveal());

        $metadataResolver = $this->prophesize(MetadataResolver::class);
        $metadataResolver->resolveItems(Argument::any(), Argument::any(), Argument::any())
            ->willReturn([
                'seo' => ContentView::create(['title' => 'Seo Title'], []),
            ]);

        $resolver = new SeoResolver(
            $formMetadataProvider->reveal(),
            $metadataResolver->reveal(),
        );

        $contentView = $resolver->resolve($dimensionContent);
        self::assertInstanceOf(ContentView::class, $contentView);

        $content = $contentView->getContent();
        self::assertIsArray($content);
        self::assertArrayHasKey('seo', $content);
        self::assertArrayNotHasKey('', $content);
    }
}
