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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentMerger;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentMerger\ContentMerger;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ContentMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @param iterable<MergerInterface> $mergers
     */
    protected function createContentMergerInstance(
        iterable $mergers
    ): ContentMergerInterface {
        return new ContentMerger($mergers, new PropertyAccessor());
    }

    public function testMerge(): void
    {
        $merger1 = $this->prophesize(MergerInterface::class);
        $merger2 = $this->prophesize(MergerInterface::class);

        $contentMerger = $this->createContentMergerInstance([
            $merger1->reveal(),
            $merger2->reveal(),
        ]);

        $mergedDimensionContent = $this->prophesize(ExampleDimensionContent::class);

        $resource = $this->prophesize(Example::class);
        $resource->createDimensionContent()
            ->willReturn($mergedDimensionContent->reveal());

        $dimensionContent1 = new ExampleDimensionContent($resource->reveal());
        $dimensionContent1->setLocale(null);
        $dimensionContent1->setStage('draft');
        $dimensionContent2 = new ExampleDimensionContent($resource->reveal());
        $dimensionContent1->setLocale('en');
        $dimensionContent1->setStage('draft');

        $merger1->merge($mergedDimensionContent->reveal(), $dimensionContent1)->shouldBeCalled();
        $merger2->merge($mergedDimensionContent->reveal(), $dimensionContent1)->shouldBeCalled();

        $merger1->merge($mergedDimensionContent->reveal(), $dimensionContent2)->shouldBeCalled();
        $merger2->merge($mergedDimensionContent->reveal(), $dimensionContent2)->shouldBeCalled();

        $mergedDimensionContent->setLocale(null) // TODO FIXME find a way to avoid this call (ExampleControllerTest::testPostTriggerUnpublish) currently fails without this
            ->shouldBeCalled();
        $mergedDimensionContent->setLocale('en')
            ->shouldBeCalled();
        $mergedDimensionContent->setStage('draft')
            ->shouldBeCalled();
        $mergedDimensionContent->markAsMerged()
            ->shouldBeCalled();
        $mergedDimensionContent->setVersion(ExampleDimensionContent::CURRENT_VERSION)
            ->shouldBeCalled();

        $dimensionContentCollection = new DimensionContentCollection([
            $dimensionContent1,
            $dimensionContent2,
        ], [
            'locale' => 'en',
            'stage' => 'draft',
        ], $dimensionContent1::class);

        $this->assertSame(
            $mergedDimensionContent->reveal(),
            $contentMerger->merge($dimensionContentCollection)
        );
    }

    public function testMergeEmptyCollection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected at least one dimensionContent given.');

        $merger1 = $this->prophesize(MergerInterface::class);
        $merger2 = $this->prophesize(MergerInterface::class);

        $contentMerger = $this->createContentMergerInstance([
            $merger1->reveal(),
            $merger2->reveal(),
        ]);

        $dimensionContentCollection = new DimensionContentCollection([], [], ExampleDimensionContent::class);

        $contentMerger->merge($dimensionContentCollection);
    }

    public function testMergeSortsByDimensionAttributes(): void
    {
        /** @var array<int, string> $callOrder */
        $callOrder = [];

        $trackingMerger = new class($callOrder) implements MergerInterface {
            /**
             * @var array<int, string>
             *
             * @phpstan-ignore property.onlyWritten
             */
            private array $callOrder;

            /**
             * @param array<int, string> $callOrder
             */
            public function __construct(array &$callOrder)
            {
                $this->callOrder = &$callOrder;
            }

            public function merge(object $mergedDimensionContent, object $dimensionContent): void
            {
                \assert($dimensionContent instanceof DimensionContentInterface);
                $locale = $dimensionContent->getLocale();
                $this->callOrder[] = $locale ?? 'unlocalized';
            }
        };

        $contentMerger = $this->createContentMergerInstance([$trackingMerger]);

        $resource = new Example();

        $localizedDimension = new ExampleDimensionContent($resource);
        $localizedDimension->setLocale('de');
        $localizedDimension->setStage('draft');

        $unlocalizedDimension = new ExampleDimensionContent($resource);
        $unlocalizedDimension->setLocale(null);
        $unlocalizedDimension->setStage('draft');

        $dimensionContentCollection = new DimensionContentCollection([
            $localizedDimension,
            $unlocalizedDimension,
        ], [
            'locale' => 'de',
            'stage' => 'draft',
        ], ExampleDimensionContent::class);

        $result = $contentMerger->merge($dimensionContentCollection);

        $this->assertSame(['unlocalized', 'de'], $callOrder);
        $this->assertSame('de', $result->getLocale());
        $this->assertSame('draft', $result->getStage());
    }

    public function testMergeWithMultipleLocales(): void
    {
        /** @var array<int, string> $callOrder */
        $callOrder = [];

        $trackingMerger = new class($callOrder) implements MergerInterface {
            /**
             * @var array<int, string>
             *
             * @phpstan-ignore property.onlyWritten
             */
            private array $callOrder;

            /**
             * @param array<int, string> $callOrder
             */
            public function __construct(array &$callOrder)
            {
                $this->callOrder = &$callOrder;
            }

            public function merge(object $mergedDimensionContent, object $dimensionContent): void
            {
                \assert($dimensionContent instanceof DimensionContentInterface);
                $locale = $dimensionContent->getLocale();
                $this->callOrder[] = $locale ?? 'unlocalized';
            }
        };

        $contentMerger = $this->createContentMergerInstance([$trackingMerger]);

        $resource = new Example();

        $enDimension = new ExampleDimensionContent($resource);
        $enDimension->setLocale('en');
        $enDimension->setStage('draft');

        $unlocalizedDimension = new ExampleDimensionContent($resource);
        $unlocalizedDimension->setLocale(null);
        $unlocalizedDimension->setStage('draft');

        $deDimension = new ExampleDimensionContent($resource);
        $deDimension->setLocale('de');
        $deDimension->setStage('draft');

        $dimensionContentCollection = new DimensionContentCollection([
            $enDimension,
            $unlocalizedDimension,
            $deDimension,
        ], [
            'locale' => 'de',
            'stage' => 'draft',
        ], ExampleDimensionContent::class);

        $result = $contentMerger->merge($dimensionContentCollection);

        $this->assertSame(['unlocalized', 'de'], $callOrder);
        $this->assertSame('de', $result->getLocale());
        $this->assertSame('draft', $result->getStage());
    }

    public function testMergeOrderAffectsResult(): void
    {
        $titleMerger = new class() implements MergerInterface {
            public function merge(object $mergedDimensionContent, object $dimensionContent): void
            {
                \assert($mergedDimensionContent instanceof TemplateInterface);
                \assert($dimensionContent instanceof TemplateInterface);

                if ($dimensionContent->getTemplateData()) {
                    $mergedDimensionContent->setTemplateData($dimensionContent->getTemplateData());
                }
            }
        };

        $contentMerger = $this->createContentMergerInstance([$titleMerger]);

        $resource = new Example();

        $localizedDimension = new ExampleDimensionContent($resource);
        $localizedDimension->setLocale('de');
        $localizedDimension->setStage('draft');
        $localizedDimension->setTemplateData(['title' => 'Localized Title']);

        $unlocalizedDimension = new ExampleDimensionContent($resource);
        $unlocalizedDimension->setLocale(null);
        $unlocalizedDimension->setStage('draft');
        $unlocalizedDimension->setTemplateData(['title' => 'Unlocalized Title']);

        $dimensionContentCollection = new DimensionContentCollection([
            $localizedDimension,
            $unlocalizedDimension,
        ], [
            'locale' => 'de',
            'stage' => 'draft',
        ], ExampleDimensionContent::class);

        $result = $contentMerger->merge($dimensionContentCollection);

        $this->assertSame('de', $result->getLocale());
        $this->assertSame('draft', $result->getStage());
        $this->assertSame('Localized Title', $result->getTitle());
    }
}
