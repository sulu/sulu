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
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Category\CategoryManager;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Category\KeywordManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Entity\Keyword;
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
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    /**
     * @var KeywordManagerInterface
     */
    private $keywordManager;

    public function setUp()
    {
        $this->categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->keywordManager = $this->prophesize(KeywordManagerInterface::class);

        $this->categoryManager = new CategoryManager(
            $this->categoryRepository->reveal(),
            $this->userRepository->reveal(),
            $this->keywordManager->reveal(),
            $this->entityManager->reveal(),
            $this->eventDispatcher->reveal()
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

    public function testDelete()
    {
        $id = 1;

        $translation = $this->prophesize(CategoryTranslation::class);
        $keyword1 = $this->prophesize(Keyword::class);
        $keyword2 = $this->prophesize(Keyword::class);
        $category = $this->prophesize(Category::class);
        $translation->getKeywords()->willReturn([$keyword1->reveal(), $keyword2->reveal()]);
        $category->getTranslations()->willReturn([$translation->reveal()]);

        $this->categoryRepository->findCategoryById($id)->willReturn($category->reveal());
        $this->keywordManager->delete($keyword1->reveal(), $category->reveal())->shouldBeCalledTimes(1);
        $this->keywordManager->delete($keyword2->reveal(), $category->reveal())->shouldBeCalledTimes(1);

        $this->categoryManager->delete($id);
    }
}
