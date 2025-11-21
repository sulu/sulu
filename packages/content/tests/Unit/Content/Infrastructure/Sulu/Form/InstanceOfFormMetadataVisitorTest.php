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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Form;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Infrastructure\Sulu\Form\InstanceOfFormMetadataVisitor;

class InstanceOfFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<XmlFormMetadataLoader>
     */
    private $xmlFormMetadataLoader;

    /**
     * @var InstanceOfFormMetadataVisitor
     */
    private $instanceOfFormMetadataVisitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xmlFormMetadataLoader = $this->prophesize(XmlFormMetadataLoader::class);

        $excerptForms = [
            'content_excerpt_metadata' => [
                'instanceOf' => ExcerptInterface::class,
                'priority' => 100,
            ],
            'content_excerpt_taxonomies' => [
                'instanceOf' => ExcerptInterface::class,
                'priority' => 99,
            ],
        ];

        $this->instanceOfFormMetadataVisitor = new InstanceOfFormMetadataVisitor(
            $this->xmlFormMetadataLoader->reveal(),
            'content_excerpt',
            $excerptForms
        );
    }

    public function testVisitFormMetadataWithoutInstanceOf(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_excerpt');
        $formMetadata->setItems([]);
        $formMetadata->setSchema(new SchemaMetadata());

        $this->xmlFormMetadataLoader->getMetadata(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->instanceOfFormMetadataVisitor->visitFormMetadata($formMetadata, 'de', []);

        $this->assertCount(0, $formMetadata->getItems());
    }

    public function testVisitFormMetadataWithEmptyMetadataOptions(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_excerpt');
        $formMetadata->setItems([]);
        $formMetadata->setSchema(new SchemaMetadata());

        $this->xmlFormMetadataLoader->getMetadata(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->instanceOfFormMetadataVisitor->visitFormMetadata($formMetadata, 'de', ['some' => 'option']);

        $this->assertCount(0, $formMetadata->getItems());
    }

    public function testVisitFormMetadataWithDifferentFormKey(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_seo'); // Different form key
        $formMetadata->setItems([]);
        $formMetadata->setSchema(new SchemaMetadata());

        $metadataOptions = [
            'instanceOf' => ExcerptInterface::class,
        ];

        $this->xmlFormMetadataLoader->getMetadata(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->instanceOfFormMetadataVisitor->visitFormMetadata($formMetadata, 'de', $metadataOptions);

        $this->assertCount(0, $formMetadata->getItems());
    }

    public function testVisitFormMetadataSubFormMetadataIsNull(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_excerpt');
        $formMetadata->setItems([]);
        $formMetadata->setSchema(new SchemaMetadata());

        $metadataOptions = [
            'instanceOf' => ExcerptInterface::class,
        ];

        $this->xmlFormMetadataLoader->getMetadata('content_excerpt_metadata', 'de', $metadataOptions)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->xmlFormMetadataLoader->getMetadata('content_excerpt_taxonomies', 'de', $metadataOptions)
            ->shouldBeCalled()
            ->willReturn(null);

        $this->instanceOfFormMetadataVisitor->visitFormMetadata($formMetadata, 'de', $metadataOptions);

        $this->assertCount(0, $formMetadata->getItems());
    }

    public function testVisitFormMetadata(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_excerpt');
        $formMetadata->setItems([]);
        $formMetadata->setSchema(new SchemaMetadata());

        $metadataOptions = [
            'instanceOf' => ExcerptInterface::class,
        ];

        $subFormMetadata1 = new FormMetadata();
        $subFormMetadata1->setKey('content_excerpt_metadata');
        $subFormMetadata1->setItems([new FieldMetadata('excerptTitle'), new FieldMetadata('excerptDescription')]);
        $subFormMetadata1->setSchema(new SchemaMetadata());

        $subFormMetadata2 = new FormMetadata();
        $subFormMetadata2->setKey('content_excerpt_taxonomies');
        $subFormMetadata2->setItems([new FieldMetadata('excerptTags'), new FieldMetadata('excerptCategories')]);
        $subFormMetadata2->setSchema(new SchemaMetadata());

        $this->xmlFormMetadataLoader->getMetadata('content_excerpt_metadata', 'de', $metadataOptions)
            ->willReturn($subFormMetadata1);

        $this->xmlFormMetadataLoader->getMetadata('content_excerpt_taxonomies', 'de', $metadataOptions)
            ->willReturn($subFormMetadata2);

        $this->instanceOfFormMetadataVisitor->visitFormMetadata($formMetadata, 'de', $metadataOptions);

        $this->assertCount(4, $formMetadata->getItems());
        $this->assertSame('excerptTitle', $formMetadata->getItems()[0]->getName());
        $this->assertSame('excerptDescription', $formMetadata->getItems()[1]->getName());
        $this->assertSame('excerptTags', $formMetadata->getItems()[2]->getName());
        $this->assertSame('excerptCategories', $formMetadata->getItems()[3]->getName());
    }

    public function testVisitFormMetadataWithDerivedClass(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_excerpt');
        $formMetadata->setItems([]);
        $formMetadata->setSchema(new SchemaMetadata());

        $metadataOptions = [
            'instanceOf' => TestDimensionContent::class,
        ];

        $subFormMetadata1 = new FormMetadata();
        $subFormMetadata1->setKey('content_excerpt_metadata');
        $subFormMetadata1->setItems([new FieldMetadata('excerptTitle')]);
        $subFormMetadata1->setSchema(new SchemaMetadata());

        $subFormMetadata2 = new FormMetadata();
        $subFormMetadata2->setKey('content_excerpt_taxonomies');
        $subFormMetadata2->setItems([new FieldMetadata('excerptTags')]);
        $subFormMetadata2->setSchema(new SchemaMetadata());

        $this->xmlFormMetadataLoader->getMetadata('content_excerpt_metadata', 'de', $metadataOptions)
            ->willReturn($subFormMetadata1);

        $this->xmlFormMetadataLoader->getMetadata('content_excerpt_taxonomies', 'de', $metadataOptions)
            ->willReturn($subFormMetadata2);

        $this->instanceOfFormMetadataVisitor->visitFormMetadata($formMetadata, 'de', $metadataOptions);

        $this->assertCount(2, $formMetadata->getItems());
        $this->assertSame('excerptTitle', $formMetadata->getItems()[0]->getName());
        $this->assertSame('excerptTags', $formMetadata->getItems()[1]->getName());
    }
}

/**
 * @extends DimensionContentInterface<ContentRichEntityInterface>
 */
interface TestDimensionContent extends DimensionContentInterface, ExcerptInterface
{
}
