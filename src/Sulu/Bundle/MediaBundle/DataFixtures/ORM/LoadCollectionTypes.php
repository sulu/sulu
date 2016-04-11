<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;

class LoadCollectionTypes extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // force id = 1
        $metadata = $manager->getClassMetaData(CollectionType::class);
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $defaultCollectionType = $this->createCollectionType(1, 'collection.default', 'Default');
        $manager->persist($defaultCollectionType);

        $systemCollectionType = $this->createCollectionType(
            2,
            SystemCollectionManagerInterface::COLLECTION_TYPE,
            'System Collections'
        );
        $manager->persist($systemCollectionType);

        $manager->flush();
    }

    /**
     * Create a collection type with given parameter.
     *
     * @param int $id
     * @param string $key
     * @param string $name
     *
     * @return CollectionType
     */
    private function createCollectionType($id, $key, $name)
    {
        $collectionType = new CollectionType();
        $collectionType->setId($id);
        $collectionType->setKey($key);
        $collectionType->setName($name);

        return $collectionType;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }
}
