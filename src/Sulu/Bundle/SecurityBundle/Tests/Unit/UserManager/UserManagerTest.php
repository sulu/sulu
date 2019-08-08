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

use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

class UserManagerTest extends TestCase
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function setUp()
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);

        $this->userManager = new UserManager(
            $this->objectManager->reveal(),
            null,
            null,
            null,
            null,
            null,
            $this->userRepository->reveal()
        );
    }

    public function testGetFullNameByUserIdForNonExistingUser()
    {
        $this->assertNull($this->userManager->getFullNameByUserId(0));
    }
}
