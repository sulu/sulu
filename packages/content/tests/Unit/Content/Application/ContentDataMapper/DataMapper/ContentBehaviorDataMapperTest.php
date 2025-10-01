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
use Sulu\Content\Application\ContentDataMapper\DataMapper\ContentBehaviorDataMapper;
use Sulu\Content\Domain\Model\ContentBehaviorInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentBehaviorDataMapperTest extends TestCase
{
    private ContentBehaviorDataMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ContentBehaviorDataMapper();
    }

    /**
     * @return array{ExampleDimensionContent, ExampleDimensionContent}
     */
    private function createExampleDimensionContents(): array
    {
        $example = new Example();

        return [
            new ExampleDimensionContent($example),
            new ExampleDimensionContent($example),
        ];
    }

    public function testMapNoData(): void
    {
        $data = [];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_CONTENT, $localizedDimensionContent->getBehavior());
        $this->assertNull($localizedDimensionContent->getBehaviorData());
    }

    public function testMapBehaviorContent(): void
    {
        $data = [
            'behavior' => 'content',
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();
        $localizedDimensionContent->setBehavior(ContentBehaviorInterface::BEHAVIOR_INTERNAL);
        $localizedDimensionContent->setBehaviorData(['internal' => ['href' => 'uuid-123']]);

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_CONTENT, $localizedDimensionContent->getBehavior());
        $this->assertNull($localizedDimensionContent->getBehaviorData());
    }

    public function testMapBehaviorInternal(): void
    {
        $data = [
            'behavior' => 'internal',
            'behaviorDataInternal' => [
                'href' => 'uuid-456',
                'target' => '_self',
            ],
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_INTERNAL, $localizedDimensionContent->getBehavior());
        $this->assertSame([
            'internal' => [
                'href' => 'uuid-456',
                'target' => '_self',
            ],
        ], $localizedDimensionContent->getBehaviorData());
    }

    public function testMapBehaviorExternal(): void
    {
        $data = [
            'behavior' => 'external',
            'behaviorDataExternal' => [
                'href' => 'https://example.com',
                'target' => '_blank',
            ],
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_EXTERNAL, $localizedDimensionContent->getBehavior());
        $this->assertSame([
            'external' => [
                'href' => 'https://example.com',
                'target' => '_blank',
            ],
        ], $localizedDimensionContent->getBehaviorData());
    }

    public function testMapInvalidBehavior(): void
    {
        $data = [
            'behavior' => 'invalid-type',
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();
        $localizedDimensionContent->setBehavior(ContentBehaviorInterface::BEHAVIOR_CONTENT);

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_CONTENT, $localizedDimensionContent->getBehavior());
    }

    public function testMapChangeBehaviorReplacesData(): void
    {
        $data = [
            'behavior' => 'external',
            'behaviorDataExternal' => [
                'href' => 'https://newsite.com',
            ],
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();
        $localizedDimensionContent->setBehavior(ContentBehaviorInterface::BEHAVIOR_INTERNAL);
        $localizedDimensionContent->setBehaviorData(['internal' => ['href' => 'old-uuid']]);

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_EXTERNAL, $localizedDimensionContent->getBehavior());
        $this->assertSame([
            'external' => ['href' => 'https://newsite.com'],
        ], $localizedDimensionContent->getBehaviorData());
    }

    public function testMapDataWithoutValidation(): void
    {
        $data = [
            'behavior' => 'internal',
            'behaviorDataInternal' => 'not-an-array',
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame(ContentBehaviorInterface::BEHAVIOR_INTERNAL, $localizedDimensionContent->getBehavior());
        $this->assertSame(['internal' => 'not-an-array'], $localizedDimensionContent->getBehaviorData());
    }
}
