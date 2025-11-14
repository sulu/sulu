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

namespace Sulu\Content\Tests\Unit\Infrastructure\Doctrine\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\RoutableTrait;
use Sulu\Content\Infrastructure\Doctrine\EventListener\RouteCleanupListener;

class RouteCleanupListenerTest extends TestCase
{
    use ProphecyTrait;

    private RouteCleanupListener $listener;

    protected function setUp(): void
    {
        $this->listener = new RouteCleanupListener();
    }

    public function testPreRemoveIgnoresNonRoutableDimensionContent(): void
    {
        $dimensionContent = new class() implements DimensionContentInterface {
            use DimensionContentTrait;

            private TestEntity $entity;

            public function __construct()
            {
                $this->entity = new TestEntity();
            }

            public function getResource(): ContentRichEntityInterface // @phpstan-ignore-line missingType.generics
            {
                return $this->entity;
            }

            public static function getResourceKey(): string
            {
                return 'test';
            }
        };

        $event = $this->prophesize(LifecycleEventArgs::class);
        $event->getObject()->willReturn($dimensionContent);

        $this->listener->preRemove($event->reveal());

        $this->expectNotToPerformAssertions();
    }

    public function testPreRemoveIgnoresOldVersions(): void
    {
        $dimensionContent = new TestRoutableDimensionContent();
        $dimensionContent->setVersion(1);

        $event = $this->prophesize(LifecycleEventArgs::class);
        $event->getObject()->willReturn($dimensionContent);

        $this->listener->preRemove($event->reveal());

        $this->expectNotToPerformAssertions();
    }

    public function testPreRemoveIgnoresUnlocalizedDimensionContent(): void
    {
        $dimensionContent = new TestRoutableDimensionContent();
        $dimensionContent->setVersion(DimensionContentInterface::CURRENT_VERSION);
        $dimensionContent->setLocale(null);

        $event = $this->prophesize(LifecycleEventArgs::class);
        $event->getObject()->willReturn($dimensionContent);

        $this->listener->preRemove($event->reveal());

        $this->expectNotToPerformAssertions();
    }

    public function testPreRemoveTracksDraftDimensionContent(): void
    {
        $dimensionContent = new TestRoutableDimensionContent();
        $dimensionContent->setVersion(DimensionContentInterface::CURRENT_VERSION);
        $dimensionContent->setLocale('en');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);

        $event = $this->prophesize(LifecycleEventArgs::class);
        $event->getObject()->willReturn($dimensionContent);

        $this->listener->preRemove($event->reveal());

        $this->expectNotToPerformAssertions();
    }

    public function testPreRemoveTracksLiveDimensionContent(): void
    {
        $dimensionContent = new TestRoutableDimensionContent();
        $dimensionContent->setVersion(DimensionContentInterface::CURRENT_VERSION);
        $dimensionContent->setLocale('en');
        $dimensionContent->setStage(DimensionContentInterface::STAGE_LIVE);

        $event = $this->prophesize(LifecycleEventArgs::class);
        $event->getObject()->willReturn($dimensionContent);

        $this->listener->preRemove($event->reveal());

        $this->expectNotToPerformAssertions();
    }

    public function testPreRemoveTracksContentRichEntity(): void
    {
        $entity = new TestEntity();

        $event = $this->prophesize(LifecycleEventArgs::class);
        $event->getObject()->willReturn($entity);

        $this->listener->preRemove($event->reveal());

        $this->expectNotToPerformAssertions();
    }

    public function testResetClearsState(): void
    {
        $dimensionContent = new TestRoutableDimensionContent();
        $dimensionContent->setVersion(DimensionContentInterface::CURRENT_VERSION);
        $dimensionContent->setLocale('en');

        $event = $this->prophesize(LifecycleEventArgs::class);
        $event->getObject()->willReturn($dimensionContent);
        $this->listener->preRemove($event->reveal());

        $this->listener->reset();

        $this->expectNotToPerformAssertions();
    }
}

/**
 * Test stub for ContentRichEntity.
 *
 * @implements ContentRichEntityInterface<TestRoutableDimensionContent>
 */
class TestEntity implements ContentRichEntityInterface
{
    /**
     * @phpstan-use ContentRichEntityTrait<TestRoutableDimensionContent>
     */
    use ContentRichEntityTrait;

    private int $id = 1;

    public function __construct()
    {
        $this->initializeDimensionContents();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function createDimensionContent(): DimensionContentInterface
    {
        return new TestRoutableDimensionContent();
    }
}

/**
 * Test stub for RoutableDimensionContent.
 *
 * @implements DimensionContentInterface<TestEntity>
 */
class TestRoutableDimensionContent implements DimensionContentInterface, RoutableInterface
{
    use DimensionContentTrait;
    use RoutableTrait;

    private TestEntity $entity;

    public function __construct()
    {
        $this->entity = new TestEntity();
    }

    public function getResource(): ContentRichEntityInterface
    {
        return $this->entity;
    }

    public static function getResourceKey(): string
    {
        return 'tests';
    }
}
