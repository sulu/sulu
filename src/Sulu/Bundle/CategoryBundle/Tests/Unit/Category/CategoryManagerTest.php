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
use Sulu\Bundle\CategoryBundle\Category\CategoryManager;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\KeywordManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var CategoryMetaRepositoryInterface
     */
    protected $categoryMetaRepository;

    /**
     * @var CategoryTranslationRepositoryInterface
     */
    protected $categoryTranslateRepository;

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
        $this->categoryMetaRepository = $this->prophesize(CategoryMetaRepositoryInterface::class);
        $this->categoryTranslateRepository = $this->prophesize(CategoryTranslationRepositoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->keywordManager = $this->prophesize(KeywordManagerInterface::class);

        $this->categoryManager = new CategoryManager(
            $this->categoryRepository->reveal(),
            $this->categoryMetaRepository->reveal(),
            $this->categoryTranslateRepository->reveal(),
            $this->userRepository->reveal(),
            $this->keywordManager->reveal(),
            $this->entityManager->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    public function testGetApiObject()
    {
        $entity = $this->prophesize(CategoryInterface::class);
        $wrapper = $this->categoryManager->getApiObject($entity->reveal(), 'en');
        $this->assertTrue($wrapper instanceof \Sulu\Bundle\CategoryBundle\Api\Category);

        $wrapper2 = $this->categoryManager->getApiObject($wrapper, 'en');
        $this->assertSame($wrapper->getEntity(), $wrapper2->getEntity());

        $wrapper = $this->categoryManager->getApiObject(null, 'de');
        $this->assertEquals(null, $wrapper);
    }

    public function testGetApiObjects()
    {
        $wrapperEntity = $this->prophesize(\Sulu\Bundle\CategoryBundle\Api\Category::class);
        $wrapperEntity->getEntity()->willReturn($this->prophesize(CategoryInterface::class)->reveal());

        $entities = [
            $this->prophesize(CategoryInterface::class)->reveal(),
            null,
            $this->prophesize(CategoryInterface::class)->reveal(),
            $wrapperEntity->reveal(),
            null,
        ];

        $wrappers = $this->categoryManager->getApiObjects($entities, 'en');

        $this->assertTrue($wrappers[0] instanceof \Sulu\Bundle\CategoryBundle\Api\Category);
        $this->assertEquals(null, $wrappers[1]);
        $this->assertTrue($wrappers[2] instanceof \Sulu\Bundle\CategoryBundle\Api\Category);
        $this->assertTrue($wrappers[3] instanceof \Sulu\Bundle\CategoryBundle\Api\Category);
        $this->assertEquals(null, $wrappers[4]);
    }

    public function testDelete()
    {
        $id = 1;

        $translation = $this->prophesize(CategoryTranslationInterface::class);
        $keyword1 = $this->prophesize(KeywordInterface::class);
        $keyword2 = $this->prophesize(KeywordInterface::class);
        $category = $this->prophesize(CategoryInterface::class);
        $translation->getKeywords()->willReturn([$keyword1->reveal(), $keyword2->reveal()]);
        $category->getTranslations()->willReturn([$translation->reveal()]);

        $this->categoryRepository->findCategoryById($id)->willReturn($category->reveal());
        $this->keywordManager->delete($keyword1->reveal(), $category->reveal())->shouldBeCalledTimes(1);
        $this->keywordManager->delete($keyword2->reveal(), $category->reveal())->shouldBeCalledTimes(1);

        $this->categoryManager->delete($id);
    }
}
