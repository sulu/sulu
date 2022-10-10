<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionModifiedEvent;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

class CollectionManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CollectionRepository>
     */
    private $collectionRepository;

    /**
     * @var ObjectProphecy<MediaRepository>
     */
    private $mediaRepository;

    /**
     * @var ObjectProphecy<FormatManagerInterface>
     */
    private $formatManager;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    private $userRepository;

    /**
     * @var ObjectProphecy<EntityManager>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var ObjectProphecy<TrashManagerInterface>
     */
    private $trashManager;

    /**
     * @var CollectionManagerInterface
     */
    private $collectionManager;

    public function setUp(): void
    {
        $this->collectionRepository = $this->prophesize(CollectionRepository::class);
        $this->mediaRepository = $this->prophesize(MediaRepository::class);
        $this->formatManager = $this->prophesize(FormatManagerInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManager::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->trashManager = $this->prophesize(TrashManagerInterface::class);

        $this->collectionManager = new CollectionManager(
            $this->collectionRepository->reveal(),
            $this->mediaRepository->reveal(),
            $this->formatManager->reveal(),
            $this->userRepository->reveal(),
            $this->entityManager->reveal(),
            $this->domainEventCollector->reveal(),
            null,
            $this->trashManager->reveal(),
            'sulu-50x50',
            ['view' => 64]
        );
    }

    private function createEntity($id, $locale, $parent = null)
    {
        $entity = $this->prophesize(Collection::class);
        $entityMeta = $this->prophesize(CollectionMeta::class);
        $entityMeta->getTitle()->willReturn($id . '');
        $entityMeta->getLocale()->willReturn($locale);
        $entity->getMeta()->willReturn(new ArrayCollection([$entityMeta->reveal()]));
        $collectionType = $this->prophesize(CollectionType::class);
        $collectionType->getId()->willReturn(1);
        $entity->getType()->willReturn($collectionType->reveal());
        $entity->getId()->willReturn($id);

        if (null !== $parent) {
            $parentEntity = $this->prophesize(Collection::class);
            $parentEntity->getId()->willReturn($parent);
            $entity->getParent()->willReturn($parentEntity->reveal());
        } else {
            $entity->getParent()->willReturn(null);
        }

        return $entity->reveal();
    }

    public function testGetTreeWithSystemCollections(): void
    {
        $this->collectionRepository->findCollectionSet(
            0,
            ['offset' => 10, 'limit' => 10, 'search' => 'test', 'locale' => 'de', 'systemCollections' => true],
            null,
            ['test'],
            Argument::any(),
            Argument::any()
        )->willReturn(new \ArrayIterator([]))->shouldBeCalled();
        $this->collectionRepository->countCollections(
            0,
            ['search' => 'test', 'locale' => 'de', 'systemCollections' => true],
            null
        )->willReturn()->shouldBeCalled();

        $tree = $this->collectionManager->getTree('de', 10, 10, 'test', 0, ['test']);
    }

    public function testGetTreeWithoutSystemCollections(): void
    {
        $this->collectionRepository->findCollectionSet(
            0,
            ['offset' => 10, 'limit' => 10, 'search' => 'test', 'locale' => 'de', 'systemCollections' => false],
            null,
            ['test'],
            Argument::any(),
            Argument::any()
        )->willReturn(new \ArrayIterator([]))->shouldBeCalled();
        $this->collectionRepository->countCollections(
            0,
            ['search' => 'test', 'locale' => 'de', 'systemCollections' => false],
            null
        )->willReturn(0)->shouldBeCalled();

        $this->collectionManager->getTree('de', 10, 10, 'test', 0, ['test'], false);
    }

    public function testGetTreeById(): void
    {
        $entities = [
            $this->createEntity(1, 'de'),
            $this->createEntity(2, 'de', 1),
            $this->createEntity(3, 'de', 1),
            $this->createEntity(4, 'de', 3),
            $this->createEntity(5, 'de', 3),
            $this->createEntity(6, 'de'),
        ];

        $this->collectionRepository->findTree(5, 'de')->willReturn($entities);
        $this->mediaRepository->findMedia(Argument::cetera())->willReturn([]);

        $result = $this->collectionManager->getTreeById(5, 'de');

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals(6, $result[1]->getId());
        $this->assertNull($result[1]->getChildren());

        $result = $result[0]->getChildren();
        $this->assertCount(2, $result);
        $this->assertEquals(2, $result[0]->getId());
        $this->assertEquals(3, $result[1]->getId());
        $this->assertNull($result[0]->getChildren());

        $result = $result[1]->getChildren();
        $this->assertCount(2, $result);
        $this->assertEquals(4, $result[0]->getId());
        $this->assertEquals(5, $result[1]->getId());
        $this->assertNull($result[0]->getChildren());
        $this->assertNull($result[1]->getChildren());
    }

    /**
     * This is e.g. needed for createing SystemCollections during installation.
     */
    public function testSaveWithoutUserId(): void
    {
        $collectionEntity = $this->createEntity(1, 'de');
        $this->collectionRepository->findCollectionById(1)->willReturn($collectionEntity);
        $this->collectionRepository->countMedia($collectionEntity)->willReturn(0);
        $this->collectionRepository->countSubCollections($collectionEntity)->willReturn(0);
        $this->mediaRepository->findMedia(Argument::cetera())->willReturn([]);

        $this->entityManager->persist($collectionEntity)->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::type(CollectionModifiedEvent::class))->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();

        $this->collectionManager->save(
            [
                'id' => 1,
                'locale' => 'de',
            ],
            null
        );
    }
}
