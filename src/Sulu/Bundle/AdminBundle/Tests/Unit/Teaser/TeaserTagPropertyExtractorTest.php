<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Teaser;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Teaser\TeaserTagPropertyExtractor;

class TeaserTagPropertyExtractorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MetadataProviderInterface>
     */
    private ObjectProphecy $metadataProvider;

    private TeaserTagPropertyExtractor $extractor;

    protected function setUp(): void
    {
        $this->metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $this->resolver = new TeaserTagPropertyExtractor($this->metadataProvider->reveal());
    }

    public function testExtractDescriptionWithTaggedProperty(): void
    {
        $templateData = ['teaser_text' => 'This is the teaser description'];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_text', TeaserTagPropertyExtractor::TAG_TEASER_DESCRIPTION);

        $result = $this->resolver->extractDescription('page', 'default', 'en', $templateData);

        $this->assertSame('This is the teaser description', $result);
    }

    public function testExtractDescriptionReturnsNullWhenNoTaggedProperty(): void
    {
        $templateData = ['some_field' => 'Some value'];
        $this->setupMetadataWithoutTag('page', 'default', 'en');

        $result = $this->resolver->extractDescription('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractDescriptionReturnsNullWhenPropertyValueIsEmpty(): void
    {
        $templateData = ['teaser_text' => ''];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_text', TeaserTagPropertyExtractor::TAG_TEASER_DESCRIPTION);

        $result = $this->resolver->extractDescription('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractDescriptionReturnsNullWhenPropertyValueIsNull(): void
    {
        $templateData = ['teaser_text' => null];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_text', TeaserTagPropertyExtractor::TAG_TEASER_DESCRIPTION);

        $result = $this->resolver->extractDescription('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractDescriptionReturnsNullWhenPropertyMissing(): void
    {
        $templateData = ['other_field' => 'value'];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_text', TeaserTagPropertyExtractor::TAG_TEASER_DESCRIPTION);

        $result = $this->resolver->extractDescription('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractDescriptionReturnsNullWhenTemplateNotFound(): void
    {
        $templateData = ['teaser_text' => 'value'];
        $this->setupMetadataWithMissingTemplate('page', 'missing-template', 'en');

        $result = $this->resolver->extractDescription('page', 'missing-template', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractMediaIdWithSingleMediaSelection(): void
    {
        $templateData = ['teaser_image' => ['id' => 42, 'displayOption' => 'left']];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_image', TeaserTagPropertyExtractor::TAG_TEASER_MEDIA);

        $result = $this->resolver->extractMediaId('page', 'default', 'en', $templateData);

        $this->assertSame(42, $result);
    }

    public function testExtractMediaIdWithMediaSelection(): void
    {
        $templateData = ['teaser_images' => ['ids' => [10, 20, 30], 'displayOption' => 'left']];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_images', TeaserTagPropertyExtractor::TAG_TEASER_MEDIA);

        $result = $this->resolver->extractMediaId('page', 'default', 'en', $templateData);

        $this->assertSame(10, $result);
    }

    public function testExtractMediaIdReturnsNullWhenNoTaggedProperty(): void
    {
        $templateData = ['some_field' => ['id' => 42]];
        $this->setupMetadataWithoutTag('page', 'default', 'en');

        $result = $this->resolver->extractMediaId('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractMediaIdReturnsNullWhenPropertyValueIsEmpty(): void
    {
        $templateData = ['teaser_image' => []];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_image', TeaserTagPropertyExtractor::TAG_TEASER_MEDIA);

        $result = $this->resolver->extractMediaId('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractMediaIdReturnsNullWhenPropertyValueIsNull(): void
    {
        $templateData = ['teaser_image' => null];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_image', TeaserTagPropertyExtractor::TAG_TEASER_MEDIA);

        $result = $this->resolver->extractMediaId('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    public function testExtractMediaIdReturnsNullWhenEmptyMediaSelection(): void
    {
        $templateData = ['teaser_images' => []];
        $this->setupMetadataWithTag('page', 'default', 'en', 'teaser_images', TeaserTagPropertyExtractor::TAG_TEASER_MEDIA);

        $result = $this->resolver->extractMediaId('page', 'default', 'en', $templateData);

        $this->assertNull($result);
    }

    private function setupMetadataWithTag(string $templateType, string $templateKey, string $locale, string $propertyName, string $tagName): void
    {
        $tag = new TagMetadata();
        $tag->setName($tagName);

        $field = new FieldMetadata($propertyName);
        $field->addTag($tag);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey($templateKey);
        $formMetadata->addItem($field);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($templateKey, $formMetadata);

        $this->metadataProvider->getMetadata($templateType, $locale, [])
            ->willReturn($typedFormMetadata);
    }

    private function setupMetadataWithoutTag(string $templateType, string $templateKey, string $locale): void
    {
        $field = new FieldMetadata('some_field');

        $formMetadata = new FormMetadata();
        $formMetadata->setKey($templateKey);
        $formMetadata->addItem($field);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($templateKey, $formMetadata);

        $this->metadataProvider->getMetadata($templateType, $locale, [])
            ->willReturn($typedFormMetadata);
    }

    private function setupMetadataWithMissingTemplate(string $templateType, string $templateKey, string $locale): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('other-template');

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('other-template', $formMetadata);

        $this->metadataProvider->getMetadata($templateType, $locale, [])
            ->willReturn($typedFormMetadata);
    }
}
