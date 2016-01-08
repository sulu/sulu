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
        ['1', null],
        ['2', 0],
        ['3', 1],
        ['4', 2],
        ['5', 3],
        ['6', 3],
        ['7', 5],
        ['8', 5],
        ['9', 2],
        ['10', 1],
        ['11', 9],
        ['12', 9],
        ['13', 0],
        ['14', 12],
        ['15', 12],
        ['16', null],
        ['17', 15],
        ['18', 15],
        ['19', 17],
        ['20', 17],
        ['21', null],
    ];

    /**
     * @var Collection[]
     */
    private $collections;

    protected function setUp()
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->db('ORM')->getOm();

        $collectionType = new CollectionType();
        $collectionType->setName('Default Type');
        $collectionType->setDescription('Default Collection Type');
        $this->em->persist($collectionType);

        foreach ($this->collectionData as $collection) {
            $this->collections[] = $this->createCollection($collection[0], $collection[1], $collectionType);
        }
        $this->em->flush();

        /** @var CollectionRepository $repository */
        $repository = $this->getContainer()->get('sulu_media.collection_repository');
        $repository->recover();
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

        /** @var CollectionRepository $repository */
        $repository = $this->getContainer()->get('sulu_media.collection_repository');

        $result = $repository->findTree($id, 'de');

        $this->assertEquals($expected, $result);
    }
}
