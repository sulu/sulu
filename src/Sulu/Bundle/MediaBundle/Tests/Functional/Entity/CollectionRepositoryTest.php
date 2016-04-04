<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;

class CollectionRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var array
     */
    private $collectionData = [
        ['1', null, false],
        ['2', 0, false],
        ['3', 1, false],
        ['4', 2, false],
        ['5', 3, false],
        ['6', 3, false],
        ['7', 5, false],
        ['8', 5, false],
        ['9', 2, false],
        ['10', 1, false],
        ['11', 9, false],
        ['12', 9, false],
        ['13', 0, false],
        ['14', 12, false],
        ['15', 12, false],
        ['16', null, true],
        ['17', 15, true],
        ['18', 15, true],
        ['19', 17, true],
        ['20', 17, true],
        ['21', null, false],
    ];

    /**
     * @var Collection[]
     */
    private $collections;

    /**
     * @var CollectionRepository
     */
    private $collectionRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->getEntityManager();

        $defaultCollectionType = new CollectionType();
        $defaultCollectionType->setName('Default Type');
        $defaultCollectionType->setDescription('Default Collection Type');
        $defaultCollectionType->setKey('collection.default');
        $this->em->persist($defaultCollectionType);

        $systemCollectionType = new CollectionType();
        $systemCollectionType->setName('Default Type');
        $systemCollectionType->setDescription('Default Collection Type');
        $systemCollectionType->setKey(SystemCollectionManagerInterface::COLLECTION_TYPE);
        $this->em->persist($systemCollectionType);

        foreach ($this->collectionData as $collection) {
            $this->collections[] = $this->createCollection(
                $collection[0],
                $collection[1],
                $collection[2] ? $systemCollectionType : $defaultCollectionType
            );
        }
        $this->em->flush();

        /** @var CollectionRepository $repository */
        $this->collectionRepository = $this->getContainer()->get('sulu_media.collection_repository');
        $this->collectionRepository->recover();
        $this->em->flush();
    }

    private function createCollection($name, $parent, $collectionType)
    {
        $collection = new Collection();
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle($name);
        $collectionMeta->setLocale('en-gb');
        $collection->setType($collectionType);
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        if ($parent !== null) {
            $collection->setParent($this->collections[$parent]);
            $this->collections[$parent]->addChildren($collection);
        }

        $this->em->persist($collection);
        $this->em->persist($collectionMeta);

        return $collection;
    }

    public function provideTreeData()
    {
        return [
            [0, [0, 1, 12, 15, 20]],
            [15, [0, 15, 16, 17, 20]],
            [20, [0, 15, 20]],
            [1, [0, 1, 2, 9, 12, 15, 20]],
            [16, [0, 15, 16, 17, 20]],
            [3, [0, 1, 2, 3, 4, 5, 8, 9, 12, 15, 20]],
            [13, [0, 1, 12, 13, 14, 15, 20]],
        ];
    }

    /**
     * @dataProvider provideTreeData
     */
    public function testTree($index, $expectedIndexes)
    {
        $expected = [];
        $id = $this->collections[$index]->getId();
        foreach ($expectedIndexes as $item) {
            $expected[] = $this->collections[$item];
        }

        $result = $this->collectionRepository->findTree($id, 'de');

        $this->assertEquals($expected, $result);
    }

    public function testFindCollectionSet()
    {
        $this->assertCount(21, $this->collectionRepository->findCollectionSet(5));
    }

    public function testFindCollectionSetWithoutSystemCollections()
    {
        $this->assertCount(16, $this->collectionRepository->findCollectionSet(5, ['systemCollections' => false]));
    }
}
