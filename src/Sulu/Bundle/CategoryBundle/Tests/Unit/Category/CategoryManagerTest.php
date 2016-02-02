<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Category;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Category\CategoryManager;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    public function setUp()
    {
        $this->categoryRepository = $this->getMockForAbstractClass(
            'Sulu\Bundle\CategoryBundle\Category\CategoryRepositoryInterface',
            [],
            '',
            false
        );

        $this->userRepository = $this->getMockForAbstractClass(
            'Sulu\Component\Security\Authentication\UserRepositoryInterface',
            [],
            '',
            false
        );

        $this->em = $this->getMockForAbstractClass(
            'Doctrine\Common\Persistence\ObjectManager',
            [],
            '',
            false
        );

        $this->eventDispatcher = $this->getMockForAbstractClass(
            'Symfony\Component\EventDispatcher\EventDispatcherInterface',
            [],
            '',
            false
        );

        $this->categoryManager = new CategoryManager(
            $this->categoryRepository,
            $this->userRepository,
            $this->em,
            $this->eventDispatcher
        );
    }

    public function testGetApiObject()
    {
        $entity = new CategoryEntity();
        $wrapper = $this->categoryManager->getApiObject($entity, 'en');

        $this->assertTrue($wrapper instanceof CategoryWrapper);

        $wrapper = $this->categoryManager->getApiObject(null, 'de');

        $this->assertEquals(null, $wrapper);
    }

    public function testGetApiObjects()
    {
        $entities = [
            new CategoryEntity(),
            null,
            new CategoryEntity(),
            new CategoryEntity(),
            null,
        ];

        $wrappers = $this->categoryManager->getApiObjects($entities, 'en');

        $this->assertTrue($wrappers[0] instanceof CategoryWrapper);
        $this->assertTrue($wrappers[2] instanceof CategoryWrapper);
        $this->assertTrue($wrappers[3] instanceof CategoryWrapper);
        $this->assertEquals(null, $wrappers[1]);
        $this->assertEquals(null, $wrappers[4]);
    }
}
