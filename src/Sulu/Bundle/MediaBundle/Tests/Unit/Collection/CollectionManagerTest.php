<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Collection;

use Doctrine\ORM\EntityManager;
use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;

class CollectionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CollectionManagerInterface
     */
    private $collectionManager;

    public function setUp()
    {
        $this->collectionRepository = $this->prophesize(CollectionRepository::class);
        $this->mediaRepository = $this->prophesize(MediaRepository::class);
        $this->formatManager = $this->prophesize(FormatManagerInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->entityManager = $this->prophesize(EntityManager::class);

        $this->collectionManager = new CollectionManager(
            $this->collectionRepository->reveal(),
            $this->mediaRepository->reveal(),
            $this->formatManager->reveal(),
            $this->userRepository->reveal(),
            $this->entityManager->reveal(),
            null,
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
        $entity->getMeta()->willReturn([$entityMeta->reveal()]);
        $entity->getId()->willReturn($id);

        if ($parent !== null) {
            $parentEntity = $this->prophesize(Collection::class);
            $parentEntity->getId()->willReturn($parent);
            $entity->getParent()->willReturn($parentEntity->reveal());
        } else {
            $entity->getParent()->willReturn(null);
        }

        return $entity->reveal();
    }

    public function testGetTreeWithSystemCollections()
    {
        $this->collectionRepository->findCollectionSet(
            0,
            ['offset' => 10, 'limit' => 10, 'search' => 'test', 'locale' => 'de', 'systemCollections' => true],
            null,
            ['test'],
            Argument::any(),
            Argument::any()
        )->willReturn(new \ArrayIterator([]));
        $this->collectionRepository->count(
            0,
            ['search' => 'test', 'locale' => 'de', 'systemCollections' => true],
            null
        )->willReturn(0);

        $this->collectionManager->getTree('de', 10, 10, 'test', 0, ['test']);
    }

    public function testGetTreeWithoutSystemCollections()
    {
        $this->collectionRepository->findCollectionSet(
            0,
            ['offset' => 10, 'limit' => 10, 'search' => 'test', 'locale' => 'de', 'systemCollections' => false],
            null,
            ['test'],
            Argument::any(),
            Argument::any()
        )->willReturn(new \ArrayIterator([]));
        $this->collectionRepository->count(
            0,
            ['search' => 'test', 'locale' => 'de', 'systemCollections' => false],
            null
        )->willReturn(0);

        $this->collectionManager->getTree('de', 10, 10, 'test', 0, ['test'], false);
    }

    public function testGetTreeById()
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

        $result = $this->collectionManager->getTreeById(5, 'de');

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->getId());
        $this->assertEquals(6, $result[1]->getId());
        $this->assertCount(0, $result[1]->getChildren());

        $result = $result[0]->getChildren();
        $this->assertCount(2, $result);
        $this->assertEquals(2, $result[0]->getId());
        $this->assertEquals(3, $result[1]->getId());
        $this->assertCount(0, $result[0]->getChildren());

        $result = $result[1]->getChildren();
        $this->assertCount(2, $result);
        $this->assertEquals(4, $result[0]->getId());
        $this->assertEquals(5, $result[1]->getId());
        $this->assertCount(0, $result[0]->getChildren());
        $this->assertCount(0, $result[1]->getChildren());
    }
}
