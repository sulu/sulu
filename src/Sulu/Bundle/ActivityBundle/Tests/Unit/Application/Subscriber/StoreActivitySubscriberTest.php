<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Application\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Subscriber\StoreActivitySubscriber;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ActivityBundle\Domain\Repository\ActivityRepositoryInterface;

class StoreActivitySubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ActivityRepositoryInterface>
     */
    private $activityRepository;

    public function setUp(): void
    {
        $this->activityRepository = $this->prophesize(ActivityRepositoryInterface::class);
    }

    public function testStoreActivity(): void
    {
        $subscriber = $this->createStoreActivitySubscriber();

        $event = $this->prophesize(DomainEvent::class);
        $activity = $this->prophesize(ActivityInterface::class);

        $this->activityRepository->createFromDomainEvent($event->reveal())
            ->willReturn($activity->reveal());
        $this->activityRepository->addAndCommit($activity->reveal())->shouldBeCalled();

        $subscriber->storeActivity($event->reveal());
    }

    private function createStoreActivitySubscriber(): StoreActivitySubscriber
    {
        return new StoreActivitySubscriber(
            $this->activityRepository->reveal()
        );
    }
}
