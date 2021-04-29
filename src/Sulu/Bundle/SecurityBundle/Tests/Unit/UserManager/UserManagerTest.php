<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\UserManager;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\EventLogBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

class UserManagerTest extends TestCase
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ObjectProphecy|UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var ObjectProphecy|DomainEventCollectorInterface
     */
    private $eventCollector;

    public function setUp(): void
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->eventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->userManager = new UserManager(
            $this->objectManager->reveal(),
            null,
            null,
            null,
            null,
            null,
            $this->userRepository->reveal(),
            $this->eventCollector->reveal()
        );
    }

    public function testGetFullNameByUserIdForNonExistingUser()
    {
        $this->assertNull($this->userManager->getFullNameByUserId(0));
    }
}
