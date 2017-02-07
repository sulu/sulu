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
        // set id manually
        $metadata = $manager->getClassMetaData(CollectionType::class);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // create or update collectiontype with id 1
        $defaultCollectionType = new CollectionType();
        $defaultCollectionType->setId(1);
        $defaultCollectionType = $manager->merge($defaultCollectionType);
        $defaultCollectionType->setKey('collection.default');
        $defaultCollectionType->setName('Default');

        // create or update collectiontype with id 2
        $systemCollectionType = new CollectionType();
        $systemCollectionType->setId(2);
        $systemCollectionType = $manager->merge($systemCollectionType);
        $systemCollectionType->setKey(SystemCollectionManagerInterface::COLLECTION_TYPE);
        $systemCollectionType->setName('System Collections');

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }
}
