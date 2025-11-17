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

namespace Sulu\Content\Tests\Unit\Content\Application\DimensionContentCollectionFactory;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Application\DimensionContentCollectionFactory\DimensionContentCollectionFactory;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DimensionContentCollectionFactoryTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function createDimensionContentCollectionFactoryInstance(
        ContentDataMapperInterface $contentDataMapper
    ): DimensionContentCollectionFactory {
        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $contentMetadataInspector->getDimensionContentClass(Example::class)
            ->willReturn(ExampleDimensionContent::class);

        return new DimensionContentCollectionFactory(
            $contentMetadataInspector->reveal(),
            $contentDataMapper,
            new PropertyAccessor()
        );
    }

    public function testCreateWithExistingDimensionContent(): void
    {
        $example = new Example();
        $example->id = 1;

        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent1->setVersion(0);
        $example->addDimensionContent($dimensionContent1);

        $dimensionContent2 = new ExampleDimensionContent($example);
        $dimensionContent2->setStage('draft');
        $dimensionContent2->setVersion(0);
        $dimensionContent2->setLocale('de');
        $example->addDimensionContent($dimensionContent2);

        $attributes = [
            'locale' => 'de',
            'stage' => 'draft',
            'version' => 0,
        ];

        $data = [
            'data' => 'value',
        ];

        $contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);
        $contentDataMapper->map(
            Argument::that(
                function(DimensionContentCollectionInterface $collection) use ($dimensionContent1, $dimensionContent2) {
                    return [$dimensionContent1, $dimensionContent2] === \iterator_to_array($collection);
                }
            ),
            $attributes,
            $data
        )->shouldBeCalled();

        $factory = $this->createDimensionContentCollectionFactoryInstance($contentDataMapper->reveal());

        $dimensionContentCollection = $factory->create($example, $attributes, $data);

        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(ExampleDimensionContent::class, $dimensionContentCollection->getDimensionContentClass());
        $this->assertSame($attributes, $dimensionContentCollection->getDimensionAttributes());
        $this->assertSame(
            [$dimensionContent1, $dimensionContent2],
            \iterator_to_array($dimensionContentCollection)
        );
    }

    public function testCreateWithoutExistingDimensionContent(): void
    {
        $example = new Example();
        $example->id = 1;

        $attributes = [
            'locale' => 'de',
            'stage' => 'draft',
            'version' => 0,
        ];

        $data = [
            'data' => 'value',
        ];

        $contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);
        $contentDataMapper->map(Argument::any(), $attributes, $data)->shouldBeCalled();

        $factory = $this->createDimensionContentCollectionFactoryInstance($contentDataMapper->reveal());

        $dimensionContentCollection = $factory->create($example, $attributes, $data);

        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(ExampleDimensionContent::class, $dimensionContentCollection->getDimensionContentClass());
        $this->assertSame($attributes, $dimensionContentCollection->getDimensionAttributes());

        /** @var ExampleDimensionContent[] $dimensionContents */
        $dimensionContents = \iterator_to_array($dimensionContentCollection);
        $dimensionContent1 = $dimensionContents[0]; // unlocalized
        $dimensionContent2 = $dimensionContents[1]; // localized (de)

        $this->assertNull($dimensionContent1->getLocale());
        $this->assertSame('de', $dimensionContent2->getLocale());
        $this->assertSame('de', $dimensionContent1->getGhostLocale());
        $this->assertSame(['de'], $dimensionContent1->getAvailableLocales());

        // Verify they were added to the entity
        $this->assertCount(2, $example->getDimensionContents());
    }

    public function testCreateWithoutExistingLocalizedDimensionContent(): void
    {
        // Create real Example entity with existing unlocalized dimension content
        $example = new Example();
        $example->id = 1;

        $dimensionContent1 = new ExampleDimensionContent($example);
        $dimensionContent1->setStage('draft');
        $dimensionContent1->setVersion(0);
        $example->addDimensionContent($dimensionContent1);

        $attributes = [
            'locale' => 'de',
            'stage' => 'draft',
            'version' => 0,
        ];

        $data = [
            'data' => 'value',
        ];

        $contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);
        $contentDataMapper->map(Argument::any(), $attributes, $data)->shouldBeCalled();

        $factory = $this->createDimensionContentCollectionFactoryInstance($contentDataMapper->reveal());

        $dimensionContentCollection = $factory->create($example, $attributes, $data);

        $this->assertCount(2, $dimensionContentCollection);
        $this->assertSame(ExampleDimensionContent::class, $dimensionContentCollection->getDimensionContentClass());
        $this->assertSame($attributes, $dimensionContentCollection->getDimensionAttributes());

        /** @var ExampleDimensionContent[] $dimensionContents */
        $dimensionContents = \iterator_to_array($dimensionContentCollection);
        $this->assertSame($dimensionContent1, $dimensionContents[0]); // reused unlocalized
        $this->assertNull($dimensionContents[0]->getLocale());
        $this->assertSame('de', $dimensionContents[1]->getLocale()); // new localized

        $this->assertCount(2, $example->getDimensionContents());
    }

    public function testCreateMultipleLocalesReusesSingleUnlocalizedDimensionContent(): void
    {
        $example = new Example();
        $example->id = 1;

        $attributes = [
            'stage' => 'draft',
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        $data = [
            'data' => 'value',
        ];

        $contentDataMapper = $this->prophesize(ContentDataMapperInterface::class);
        $contentDataMapper->map(Argument::any(), Argument::any(), Argument::any())->shouldBeCalled();

        $factory = $this->createDimensionContentCollectionFactoryInstance($contentDataMapper->reveal());

        $attributesEn = \array_merge($attributes, ['locale' => 'en']);
        $collectionEn = $factory->create($example, $attributesEn, $data);

        $this->assertCount(2, $collectionEn);
        /** @var ExampleDimensionContent[] $dimensionContentsEn */
        $dimensionContentsEn = \iterator_to_array($collectionEn);
        $unlocalizedDimensionContent = $dimensionContentsEn[0];
        $localizedDimensionContentEn = $dimensionContentsEn[1];

        $this->assertNull($unlocalizedDimensionContent->getLocale());
        $this->assertSame('en', $localizedDimensionContentEn->getLocale());
        $this->assertSame(['en'], $unlocalizedDimensionContent->getAvailableLocales());

        $attributesDe = \array_merge($attributes, ['locale' => 'de']);
        $collectionDe = $factory->create($example, $attributesDe, $data);

        $this->assertCount(2, $collectionDe);
        /** @var ExampleDimensionContent[] $dimensionContentsDe */
        $dimensionContentsDe = \iterator_to_array($collectionDe);
        $unlocalizedDimensionContent2 = $dimensionContentsDe[0];
        $localizedDimensionContentDe = $dimensionContentsDe[1];

        $this->assertSame($unlocalizedDimensionContent, $unlocalizedDimensionContent2, 'Should reuse the same unlocalized dimension content instance');

        $this->assertNull($unlocalizedDimensionContent2->getLocale());
        $this->assertSame('de', $localizedDimensionContentDe->getLocale());

        $this->assertSame(['en', 'de'], $unlocalizedDimensionContent->getAvailableLocales());

        $this->assertCount(3, $example->getDimensionContents());

        $unlocalizedCount = 0;
        foreach ($example->getDimensionContents() as $dc) {
            if (null === $dc->getLocale()) {
                ++$unlocalizedCount;
            }
        }

        $this->assertSame(1, $unlocalizedCount, 'Should have exactly 1 unlocalized dimension content in entity collection');
    }
}
