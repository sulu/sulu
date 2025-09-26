<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Category;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Category\CategoryManager;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\KeywordManagerInterface;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryMovedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRemovedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Exception\RemoveCategoryDependantResourcesFoundException;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CategoryRepositoryInterface>
     */
    protected $categoryRepository;

    /**
     * @var ObjectProphecy<CategoryMetaRepositoryInterface>
     */
    protected $categoryMetaRepository;

    /**
     * @var ObjectProphecy<CategoryTranslationRepositoryInterface>
     */
    protected $categoryTranslateRepository;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    protected $userRepository;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    protected $entityManager;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    protected $eventDispatcher;

    /**
     * @var ObjectProphecy<KeywordManagerInterface>
     */
    private $keywordManager;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    public function setUp(): void
    {
        $this->categoryRepository = $this->prophesize(CategoryRepositoryInterface::class);
        $this->categoryMetaRepository = $this->prophesize(CategoryMetaRepositoryInterface::class);
        $this->categoryTranslateRepository = $this->prophesize(CategoryTranslationRepositoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->eventDispatcher->dispatch(Argument::any(), Argument::any())
            ->willReturnArgument(0);
        $this->keywordManager = $this->prophesize(KeywordManagerInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->categoryManager = new CategoryManager(
            $this->categoryRepository->reveal(),
            $this->categoryMetaRepository->reveal(),
            $this->categoryTranslateRepository->reveal(),
            $this->userRepository->reveal(),
            $this->keywordManager->reveal(),
            $this->entityManager->reveal(),
            $this->eventDispatcher->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testGetApiObject(): void
    {
        $entity = $this->prophesize(CategoryInterface::class);
        $wrapper = $this->categoryManager->getApiObject($entity->reveal(), 'en');
        $this->assertTrue($wrapper instanceof Category);

        $wrapper2 = $this->categoryManager->getApiObject($wrapper, 'en');
        $this->assertSame($wrapper->getEntity(), $wrapper2->getEntity());

        $wrapper = $this->categoryManager->getApiObject(null, 'de');
        $this->assertEquals(null, $wrapper);
    }

    public function testGetApiObjects(): void
    {
        $wrapperEntity = $this->prophesize(Category::class);
        $wrapperEntity->getEntity()->willReturn($this->prophesize(CategoryInterface::class)->reveal());

        $entities = [
            $this->prophesize(CategoryInterface::class)->reveal(),
            null,
            $this->prophesize(CategoryInterface::class)->reveal(),
            $wrapperEntity->reveal(),
            null,
        ];

        $wrappers = $this->categoryManager->getApiObjects($entities, 'en');

        $this->assertTrue($wrappers[0] instanceof Category);
        $this->assertEquals(null, $wrappers[1]);
        $this->assertTrue($wrappers[2] instanceof Category);
        $this->assertTrue($wrappers[3] instanceof Category);
        $this->assertEquals(null, $wrappers[4]);
    }

    public function testDeleteWithoutChildren(): void
    {
        $id = 1;

        $keyword1 = $this->prophesize(KeywordInterface::class);
        $keyword2 = $this->prophesize(KeywordInterface::class);

        $translation = $this->prophesize(CategoryTranslationInterface::class);
        $translation->getTranslation()->willReturn('category-translation');
        $translation->getLocale()->willReturn('de');
        $translation->getKeywords()->willReturn([$keyword1->reveal(), $keyword2->reveal()]);

        $category = $this->prophesize(CategoryInterface::class);
        $category->getDefaultLocale()->willReturn('de');
        $category->getTranslations()->willReturn([$translation->reveal()]);
        $category->findTranslationByLocale('de')->willReturn($translation->reveal());

        $this->categoryRepository->findCategoryById($id)->willReturn($category->reveal());
        $this->categoryRepository->findDescendantCategoryResources($id)->shouldBeCalled()->willReturn([]);
        $this->keywordManager->delete($keyword1->reveal(), $category->reveal())->shouldBeCalledTimes(1);
        $this->keywordManager->delete($keyword2->reveal(), $category->reveal())->shouldBeCalledTimes(1);

        $this->domainEventCollector->collect(Argument::type(CategoryRemovedEvent::class))->shouldBeCalled();

        $this->categoryManager->delete($id);
    }

    public function testDeleteWithChildren(): void
    {
        $id = 1;

        $keyword1 = $this->prophesize(KeywordInterface::class);
        $keyword2 = $this->prophesize(KeywordInterface::class);

        $translation = $this->prophesize(CategoryTranslationInterface::class);
        $translation->getTranslation()->willReturn('category-translation');
        $translation->getKeywords()->willReturn([$keyword1->reveal(), $keyword2->reveal()]);

        $category = $this->prophesize(CategoryInterface::class);
        $category->getDefaultLocale()->willReturn('de');
        $category->getTranslations()->willReturn([$translation->reveal()]);
        $category->findTranslationByLocale('de')->willReturn($translation->reveal());

        $this->categoryRepository->findCategoryById($id)->willReturn($category->reveal());
        $this->categoryRepository->findDescendantCategoryResources($id)->willReturn([
            ['id' => 2, 'resourceKey' => 'categories', 'depth' => 2],
            ['id' => 3, 'resourceKey' => 'categories', 'depth' => 3],
        ]);
        $this->keywordManager->delete(Argument::any())->shouldNotBeCalled();
        $this->keywordManager->delete(Argument::any())->shouldNotBeCalled();

        $this->domainEventCollector->collect(Argument::any())->shouldNotBeCalled();

        $this->expectException(RemoveCategoryDependantResourcesFoundException::class);

        $this->categoryManager->delete($id);
    }

    public function testDeleteForceDeleteChildren(): void
    {
        $id = 1;

        $keyword1 = $this->prophesize(KeywordInterface::class);
        $keyword2 = $this->prophesize(KeywordInterface::class);

        $translation = $this->prophesize(CategoryTranslationInterface::class);
        $translation->getTranslation()->willReturn('category-translation');
        $translation->getLocale()->willReturn('de');
        $translation->getKeywords()->willReturn([$keyword1->reveal(), $keyword2->reveal()]);

        $category = $this->prophesize(CategoryInterface::class);
        $category->getDefaultLocale()->willReturn('de');
        $category->getTranslations()->willReturn([$translation->reveal()]);
        $category->findTranslationByLocale('de')->willReturn($translation->reveal());

        $this->categoryRepository->findCategoryById($id)->willReturn($category->reveal());
        $this->categoryRepository->findDescendantCategoryResources($id)->shouldNotBeCalled();
        $this->keywordManager->delete($keyword1->reveal(), $category->reveal())->shouldBeCalledTimes(1);
        $this->keywordManager->delete($keyword2->reveal(), $category->reveal())->shouldBeCalledTimes(1);

        $this->domainEventCollector->collect(Argument::type(CategoryRemovedEvent::class))->shouldBeCalled();

        $this->categoryManager->delete($id, true);
    }

    public function testMove($id = 1, $parentId = 2): void
    {
        $category = $this->prophesize(CategoryInterface::class);
        $newParentCategory = $this->prophesize(CategoryInterface::class);

        $this->categoryRepository->findCategoryById($id)->willReturn($category->reveal());
        $this->categoryRepository->findCategoryById($parentId)->willReturn($newParentCategory->reveal());

        $category->getParent()->willReturn(null)->shouldBeCalled();
        $category->setParent($newParentCategory->reveal())->shouldBeCalled();

        $this->domainEventCollector->collect(Argument::type(CategoryMovedEvent::class))->shouldBeCalled();

        $result = $this->categoryManager->move($id, $parentId);
        $this->assertEquals($category->reveal(), $result);
    }

    public function testMoveToRoot($id = 1, $parentId = null): void
    {
        $category = $this->prophesize(CategoryInterface::class);
        $previousParentCategory = $this->prophesize(CategoryInterface::class);

        $this->categoryRepository->findCategoryById($id)->willReturn($category->reveal());
        $this->categoryRepository->findCategoryById($parentId)->shouldNotBeCalled();

        $category->getParent()->willReturn($previousParentCategory->reveal())->shouldBeCalled();
        $category->setParent(null)->shouldBeCalled();

        $this->domainEventCollector->collect(Argument::type(CategoryMovedEvent::class))->shouldBeCalled();

        $result = $this->categoryManager->move($id, $parentId);
        $this->assertEquals($category->reveal(), $result);
    }
}
