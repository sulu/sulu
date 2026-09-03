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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\ContentDataMapper\DataMapper\TemplateDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\DependencyInjection\Container;

class TemplateDataMapperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param ItemMetadata[] $properties
     */
    protected function createTemplateDataMapperInstance(
        array $properties = [],
        ?string $defaultTemplateKey = null,
    ): TemplateDataMapper {
        $container = new Container();
        $container->set(
            'form',
            new class ($this->createTypedFormMetadata($properties, $defaultTemplateKey)) implements MetadataProviderInterface {
            public function __construct(private readonly TypedFormMetadata $typedFormMetadata)
            {
            }

            public function getMetadata(string $key, string $locale, array $metadataOptions): TypedFormMetadata
            {
                return $this->typedFormMetadata;
            }
            }
        );
        $metadataProviderRegistry = new MetadataProviderRegistry($container);

        return new TemplateDataMapper($metadataProviderRegistry);
    }

    public function testMapNoTemplateInstance(): void
    {
        $data = [
            'template' => 'template-key',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $templateMapper = $this->createTemplateDataMapperInstance();
        $templateMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);

        $this->assertTrue(true); // Avoid risky test as this is an early return test // @phpstan-ignore method.alreadyNarrowedType
    }

    public function testMapNoTemplateKey(): void
    {
        $this->expectException(\RuntimeException::class);

        $data = [
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $templateMapper = $this->createTemplateDataMapperInstance([], 'none-exist-template');
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);
    }

    public function testMapNoMetadataFound(): void
    {
        $this->expectException(\RuntimeException::class);

        $data = [
            'template' => 'none-exist-template',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $templateMapper = $this->createTemplateDataMapperInstance();
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $templateMapper = $this->createTemplateDataMapperInstance([], 'template-key');
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($unlocalizedDimensionContent->getTemplateKey());
        $this->assertNull($localizedDimensionContent->getTemplateKey());
        $this->assertSame([], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame([], $localizedDimensionContent->getTemplateData());
    }

    public function testMapData(): void
    {
        $data = [
            'template' => 'template-key',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $templateMapper = $this->createTemplateDataMapperInstance();
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($unlocalizedDimensionContent->getTemplateKey());
        $this->assertSame('template-key', $localizedDimensionContent->getTemplateKey());
        $this->assertSame(['unlocalizedField' => 'Test Unlocalized'], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame(['title' => 'Test Localized'], $localizedDimensionContent->getTemplateData());
    }

    public function testMapDataPreview(): void
    {
        $data = [
            'template' => 'template-key',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
        ];

        $example = new Example();
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $templateMapper = $this->createTemplateDataMapperInstance();
        $templateMapper->map($localizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('template-key', $localizedDimensionContent->getTemplateKey());
        $this->assertSame(['unlocalizedField' => 'Test Unlocalized', 'title' => 'Test Localized'], $localizedDimensionContent->getTemplateData());
    }

    public function testMapNestedPropertyData(): void
    {
        $data = [
            'template' => 'template-key',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
            'nestedProperty' => ['value' => 'Nested Value'],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $nestedPropertyMetadata = new FieldMetadata('nestedProperty/value');
        $nestedPropertyMetadata->setMultilingual(true);
        $nestedPropertyMetadata->setType('text_line');

        $templateMapper = $this->createTemplateDataMapperInstance([$nestedPropertyMetadata]);
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($unlocalizedDimensionContent->getTemplateKey());
        $this->assertSame('template-key', $localizedDimensionContent->getTemplateKey());
        $this->assertSame(['unlocalizedField' => 'Test Unlocalized'], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame(
            ['title' => 'Test Localized', 'nestedProperty' => ['value' => 'Nested Value']],
            $localizedDimensionContent->getTemplateData(),
        );
    }

    public function testMapMultipleNestedPropertyData(): void
    {
        $data = [
            'template' => 'template-key',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
            'nestedProperty' => [
                'value_1' => 'Nested Value 1',
                'value_2' => 'Nested Value 2',
            ],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $nestedPropertyValue1Metadata = new FieldMetadata('nestedProperty/value_1');
        $nestedPropertyValue1Metadata->setMultilingual(true);
        $nestedPropertyValue1Metadata->setType('text_line');

        $nestedPropertyValue2Metadata = new FieldMetadata('nestedProperty/value_2');
        $nestedPropertyValue2Metadata->setMultilingual(true);
        $nestedPropertyValue2Metadata->setType('text_line');

        $templateMapper = $this->createTemplateDataMapperInstance([
            $nestedPropertyValue1Metadata,
            $nestedPropertyValue2Metadata,
        ]);
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($unlocalizedDimensionContent->getTemplateKey());
        $this->assertSame('template-key', $localizedDimensionContent->getTemplateKey());
        $this->assertSame(['unlocalizedField' => 'Test Unlocalized'], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame(
            [
                'title' => 'Test Localized',
                'nestedProperty' => [
                    'value_1' => 'Nested Value 1',
                    'value_2' => 'Nested Value 2',
                ],
            ],
            $localizedDimensionContent->getTemplateData(),
        );
    }

    public function testMapUnlocalizedNestedProperty(): void
    {
        $data = [
            'template' => 'template-key',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
            'unlocalizedNested' => ['value' => 'Unlocalized Nested Value'],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $unlocalizedNestedField = new FieldMetadata('unlocalizedNested/value');
        $unlocalizedNestedField->setMultilingual(false);
        $unlocalizedNestedField->setType('text_line');

        $templateMapper = $this->createTemplateDataMapperInstance([$unlocalizedNestedField]);
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(
            [
                'unlocalizedField' => 'Test Unlocalized',
                'unlocalizedNested' => ['value' => 'Unlocalized Nested Value'],
            ],
            $unlocalizedDimensionContent->getTemplateData(),
        );
        $this->assertSame(['title' => 'Test Localized'], $localizedDimensionContent->getTemplateData());
    }

    public function testMapDefaultValuesFromCorrectSource(): void
    {
        $data = [
            'template' => 'template-key',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setTemplateData(['unlocalizedField' => 'Existing Unlocalized']);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setTemplateData(['title' => 'Existing Localized']);

        $templateMapper = $this->createTemplateDataMapperInstance();
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(['unlocalizedField' => 'Existing Unlocalized'], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame(['title' => 'Existing Localized'], $localizedDimensionContent->getTemplateData());
    }

    public function testMapDefaultValuesFromCorrectSourceWithNestedUnlocalized(): void
    {
        $data = [
            'template' => 'template-key',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $unlocalizedDimensionContent->setTemplateData([
            'unlocalizedField' => 'Existing Unlocalized',
            'unlocalizedNested' => ['value' => 'Existing Nested'],
        ]);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');
        $localizedDimensionContent->setTemplateData(['title' => 'Existing Localized']);

        $unlocalizedNestedField = new FieldMetadata('unlocalizedNested/value');
        $unlocalizedNestedField->setMultilingual(false);
        $unlocalizedNestedField->setType('text_line');

        $templateMapper = $this->createTemplateDataMapperInstance([$unlocalizedNestedField]);
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(
            [
                'unlocalizedField' => 'Existing Unlocalized',
                'unlocalizedNested' => ['value' => 'Existing Nested'],
            ],
            $unlocalizedDimensionContent->getTemplateData(),
        );
        $this->assertSame(['title' => 'Existing Localized'], $localizedDimensionContent->getTemplateData());
    }

    public function testMapFloatData(): void
    {
        $data = [
            'template' => 'template-key',
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
            '1.1' => 'Test Float',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $floatPropertyMetadata = new FieldMetadata((string) 1.1);
        $floatPropertyMetadata->setMultilingual(true);
        $floatPropertyMetadata->setType('text_line');

        $templateMapper = $this->createTemplateDataMapperInstance([$floatPropertyMetadata]);
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($unlocalizedDimensionContent->getTemplateKey());
        $this->assertSame('template-key', $localizedDimensionContent->getTemplateKey());
        $this->assertSame(['unlocalizedField' => 'Test Unlocalized'], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame(['title' => 'Test Localized', '1.1' => 'Test Float'], $localizedDimensionContent->getTemplateData());
    }

    public function testMapWithDefaultTemplate(): void
    {
        $data = [
            'unlocalizedField' => 'Test Unlocalized',
            'title' => 'Test Localized',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $templateMapper = $this->createTemplateDataMapperInstance([], 'template-key');
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($unlocalizedDimensionContent->getTemplateKey());
        $this->assertSame('template-key', $localizedDimensionContent->getTemplateKey());
        $this->assertSame(['unlocalizedField' => 'Test Unlocalized'], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame(['title' => 'Test Localized'], $localizedDimensionContent->getTemplateData());
    }

    public function testMapDataSkipsFieldsWithSkipTag(): void
    {
        $data = [
            'template' => 'template-key',
            'title' => 'Test Title',
            'url' => '/should-not-end-up-in-template-data',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setLocale('en');

        $urlFieldMetadata = new FieldMetadata('url');
        $urlFieldMetadata->setType('route');
        $urlFieldMetadata->setMultilingual(true);
        $skipTag = new TagMetadata();
        $skipTag->setName(TemplateDataMapper::SKIP_TAG);
        $urlFieldMetadata->addTag($skipTag);

        $templateMapper = $this->createTemplateDataMapperInstance([$urlFieldMetadata]);
        $templateMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(['title' => 'Test Title'], $localizedDimensionContent->getTemplateData());
        $this->assertArrayNotHasKey('url', $localizedDimensionContent->getTemplateData());
        $this->assertArrayNotHasKey('url', $unlocalizedDimensionContent->getTemplateData());
    }

    /**
     * @param ItemMetadata[] $properties
     */
    private function createTypedFormMetadata(array $properties = [], ?string $defaultTemplateKey = null): TypedFormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setTitle('Example Template', 'en');
        $formMetadata->setKey('template-key');

        $unlocalizedPropertyMetadata = new FieldMetadata('unlocalizedField');
        $unlocalizedPropertyMetadata->setMultilingual(false);
        $unlocalizedPropertyMetadata->setType('text_line');

        $localizedPropertyMetadata = new FieldMetadata('title');
        $localizedPropertyMetadata->setMultilingual(true);
        $localizedPropertyMetadata->setType('text_line');

        $formMetadata->addItem($unlocalizedPropertyMetadata);
        $formMetadata->addItem($localizedPropertyMetadata);

        foreach ($properties as $property) {
            $formMetadata->addItem($property);
        }

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);

        if (null !== $defaultTemplateKey) {
            $typedFormMetadata->setDefaultType($defaultTemplateKey);
        }

        return $typedFormMetadata;
    }
}
