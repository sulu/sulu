<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\UrlType;

class LoadDefaultTypes extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $phoneType1 = new PhoneType();
        $phoneType1->setId(1);

        // force id = 1
        $metadata = $manager->getClassMetaData(get_class($phoneType1));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $phoneType1->setName('phone.work');
        $manager->persist($phoneType1);

        $phoneType2 = new PhoneType();
        $phoneType2->setId(2);
        $phoneType2->setName('phone.home');
        $manager->persist($phoneType2);

        $phoneType3 = new PhoneType();
        $phoneType3->setId(3);
        $phoneType3->setName('phone.mobile');
        $manager->persist($phoneType3);

        $emailType1 = new EmailType();
        $emailType1->setId(1);

        // force id = 1
        $metadata = $manager->getClassMetaData(get_class($emailType1));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $emailType1->setName('email.work');
        $manager->persist($emailType1);

        $this->addReference('email.type.work', $emailType1);

        $emailType2 = new EmailType();
        $emailType2->setId(2);
        $emailType2->setName('email.home');
        $manager->persist($emailType2);

        $this->addReference('email.type.home', $emailType2);

        $addressType1 = new AddressType();
        $addressType1->setId(1);

        // force id = 1
        $metadata = $manager->getClassMetaData(get_class($addressType1));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $addressType1->setName('address.work');
        $manager->persist($addressType1);

        $addressType2 = new AddressType();
        $addressType2->setId(2);
        $addressType2->setName('address.home');
        $manager->persist($addressType2);

        $urlType1 = new UrlType();
        $urlType1->setId(1);

        // force id = 1
        $metadata = $manager->getClassMetaData(get_class($urlType1));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $urlType1->setName('url.work');
        $manager->persist($urlType1);

        $urlType2 = new UrlType();
        $urlType2->setId(2);
        $urlType2->setName('url.home');
        $manager->persist($urlType2);

        $manager->flush();

        $faxType1 = new FaxType();
        $faxType1->setId(1);

        // force id = 1
        $metadata = $manager->getClassMetaData(get_class($faxType1));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $faxType1->setName('fax.work');
        $manager->persist($faxType1);

        $faxType2 = new FaxType();
        $faxType2->setId(2);
        $faxType2->setName('fax.home');
        $manager->persist($faxType2);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
