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
use Sulu\Bundle\MediaBundle\Entity\MediaType;

class LoadMediaTypes extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mediaDocument = new MediaType();
        $mediaDocument->setId(1);

        // force id = 1
        $metadata = $manager->getClassMetaData(get_class($mediaDocument));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $mediaDocument->setName('document');
        $manager->persist($mediaDocument);

        $mediaImage = new MediaType();
        $mediaImage->setId(2);
        $mediaImage->setName('image');
        $manager->persist($mediaImage);

        $mediaVideo = new MediaType();
        $mediaVideo->setId(3);
        $mediaVideo->setName('video');
        $manager->persist($mediaVideo);

        $mediaAudio = new MediaType();
        $mediaAudio->setId(4);
        $mediaAudio->setName('audio');
        $manager->persist($mediaAudio);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
