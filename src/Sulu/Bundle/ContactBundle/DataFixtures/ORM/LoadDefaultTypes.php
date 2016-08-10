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
        // phone types
        $metadata = $manager->getClassMetaData(PhoneType::class);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $phoneType1 = new PhoneType();
        $phoneType1->setId(1);
        $phoneType1 = $manager->merge($phoneType1);
        $phoneType1->setName('phone.work');

        $phoneType2 = new PhoneType();
        $phoneType2->setId(2);
        $phoneType2 = $manager->merge($phoneType2);
        $phoneType2->setName('phone.home');

        $phoneType3 = new PhoneType();
        $phoneType3->setId(3);
        $phoneType3 = $manager->merge($phoneType3);
        $phoneType3->setName('phone.mobile');

        // email types
        $metadata = $manager->getClassMetaData(EmailType::class);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $emailType1 = new EmailType();
        $emailType1->setId(1);
        $emailType1 = $manager->merge($emailType1);
        $emailType1->setName('email.work');

        $this->addReference('email.type.work', $emailType1);

        $emailType2 = new EmailType();
        $emailType2->setId(2);
        $emailType2 = $manager->merge($emailType2);
        $emailType2->setName('email.home');

        $this->addReference('email.type.home', $emailType2);

        // address types
        $metadata = $manager->getClassMetaData(AddressType::class);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $addressType1 = new AddressType();
        $addressType1->setId(1);
        $addressType1 = $manager->merge($addressType1);
        $addressType1->setName('address.work');

        $addressType2 = new AddressType();
        $addressType2->setId(2);
        $addressType2 = $manager->merge($addressType2);
        $addressType2->setName('address.home');

        // url types
        $metadata = $manager->getClassMetaData(UrlType::class);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $urlType1 = new UrlType();
        $urlType1->setId(1);
        $urlType1 = $manager->merge($urlType1);
        $urlType1->setName('url.work');

        $urlType2 = new UrlType();
        $urlType2->setId(2);
        $urlType2 = $manager->merge($urlType2);
        $urlType2->setName('url.home');

        // fax types
        $metadata = $manager->getClassMetaData(FaxType::class);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $faxType1 = new FaxType();
        $faxType1->setId(1);
        $faxType1 = $manager->merge($faxType1);
        $faxType1->setName('fax.work');

        $faxType2 = new FaxType();
        $faxType2->setId(2);
        $faxType2 = $manager->merge($faxType2);
        $faxType2->setName('fax.home');

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
