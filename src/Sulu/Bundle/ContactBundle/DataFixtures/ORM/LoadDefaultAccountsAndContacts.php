<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;

class LoadDefaultAccountsAndContacts extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {

        /** @var EmailType $emailTypeWork */
        $emailTypeWork = $this->getReference('email.type.work');

        /*
         * Account 1
         */
        $emailMassiveArt = new Email();
        $emailMassiveArt->setEmail('office@massiveArt.com');
        $emailMassiveArt->setEmailType($emailTypeWork);
        $manager->persist($emailMassiveArt);
        $manager->flush();

        $massiveArt = new Account();
        $massiveArt->setName('Massive Art');
        $massiveArt->addEmail($emailMassiveArt);
        $massiveArt->setCreated(new Datetime());
        $massiveArt->setChanged(new Datetime());
        $manager->persist($massiveArt);
        $manager->flush();

        /*
         * Account 1.1
         */
        $emailMassiveArtAT = new Email();
        $emailMassiveArtAT->setEmail('office@massiveArt.at');
        $emailMassiveArtAT->setEmailType($emailTypeWork);
        $manager->persist($emailMassiveArtAT);
        $manager->flush();

        $massiveArtAT = new Account();
        $massiveArtAT->setName('Massive Art Österreich GmbH');
        $massiveArtAT->addEmail($emailMassiveArtAT);
        $massiveArtAT->setCreated(new Datetime());
        $massiveArtAT->setChanged(new Datetime());
        $massiveArtAT->setParent($massiveArt);
        $manager->persist($massiveArtAT);
        $manager->flush();

        /*
         * Account 1.2
         */
        $emailMassiveArtDE = new Email();
        $emailMassiveArtDE->setEmail('office@massiveArt.de');
        $emailMassiveArtDE->setEmailType($emailTypeWork);
        $manager->persist($emailMassiveArtDE);
        $manager->flush();

        $massiveArtDE = new Account();
        $massiveArtDE->setName('Massive Art Deutschland GmbH');
        $massiveArtDE->addEmail($emailMassiveArtDE);
        $massiveArtDE->setCreated(new Datetime());
        $massiveArtDE->setChanged(new Datetime());
        $massiveArtDE->setParent($massiveArt);
        $manager->persist($massiveArtDE);
        $manager->flush();

        /*
         * Account 2
         */
        $emailLovelySystems = new Email();
        $emailLovelySystems->setEmail('office@lovelysystems.com');
        $emailLovelySystems->setEmailType($emailTypeWork);
        $manager->persist($emailLovelySystems);
        $manager->flush();

        $lovelySystems = new Account();
        $lovelySystems->setName('Lovely Systems');
        $lovelySystems->addEmail($emailLovelySystems);
        $lovelySystems->setCreated(new Datetime());
        $lovelySystems->setChanged(new Datetime());
        $manager->persist($lovelySystems);
        $manager->flush();

        /*
         * Account 3
         */
        $emailZeughaus = new Email();
        $emailZeughaus->setEmail('office@lovelysystems.com');
        $emailZeughaus->setEmailType($emailTypeWork);
        $manager->persist($emailZeughaus);
        $manager->flush();

        $zeughaus = new Account();
        $zeughaus->setName('Lovely Systems');
        $zeughaus->addEmail($emailZeughaus);
        $zeughaus->setCreated(new Datetime());
        $zeughaus->setChanged(new Datetime());
        $manager->persist($zeughaus);
        $manager->flush();


        /*
         * Contact 1
         */
        $emailContact1 = new Email();
        $emailContact1->setEmail('michael.zangerle@massiveArt.com');
        $emailContact1->setEmailType($emailTypeWork);
        $manager->persist($emailContact1);
        $manager->flush();

        $contact1 = new Contact();
        $contact1->setFirstName('Michael');
        $contact1->setLastName('Zangerle');
        $contact1->setCreated(new Datetime());
        $contact1->setChanged(new Datetime());
        $contact1->setTitle('');
        $contact1->setPosition('');
        $contact1->setMiddleName('');
        $contact1->addEmail($emailContact1);
        $contact1->setAccount($massiveArt);
        $massiveArt->addContact($contact1);

        $manager->persist($contact1);
        $manager->persist($massiveArt);
        $manager->flush();

        /*
         * Contact 2
         */
        $emailContact2 = new Email();
        $emailContact2->setEmail('johannes.wachter@massiveArt.com');
        $emailContact2->setEmailType($emailTypeWork);
        $manager->persist($emailContact2);
        $manager->flush();

        $contact2 = new Contact();
        $contact2->setFirstName('Johannes');
        $contact2->setLastName('Wachter');
        $contact2->setCreated(new Datetime());
        $contact2->setChanged(new Datetime());
        $contact2->setTitle('');
        $contact2->setPosition('');
        $contact2->setMiddleName('');
        $contact2->addEmail($emailContact2);
        $contact2->setAccount($massiveArt);
        $massiveArt->addContact($contact2);

        $manager->persist($contact2);
        $manager->persist($massiveArt);
        $manager->flush();

        /*
        * Contact 3
        */
        $emailContact3 = new Email();
        $emailContact3->setEmail('daniel.rotter@massiveArt.com');
        $emailContact3->setEmailType($emailTypeWork);
        $manager->persist($emailContact3);
        $manager->flush();

        $contact3 = new Contact();
        $contact3->setFirstName('Daniel');
        $contact3->setLastName('Rotter');
        $contact3->setCreated(new Datetime());
        $contact3->setChanged(new Datetime());
        $contact3->setTitle('');
        $contact3->setPosition('');
        $contact3->setMiddleName('');
        $contact3->addEmail($emailContact3);
        $contact3->setAccount($massiveArt);
        $massiveArt->addContact($contact3);

        $manager->persist($contact3);
        $manager->persist($massiveArt);
        $manager->flush();

        /*
        * Contact 4
        */
        $emailContact4 = new Email();
        $emailContact4->setEmail('elias.hiller@massiveArt.com');
        $emailContact4->setEmailType($emailTypeWork);
        $manager->persist($emailContact4);
        $manager->flush();

        $contact4 = new Contact();
        $contact4->setFirstName('Elias');
        $contact4->setLastName('Hiller');
        $contact4->setCreated(new Datetime());
        $contact4->setChanged(new Datetime());
        $contact4->setTitle('');
        $contact4->setPosition('');
        $contact4->setMiddleName('');
        $contact4->addEmail($emailContact4);
        $contact4->setAccount($massiveArt);
        $massiveArt->addContact($contact4);

        $manager->persist($contact4);
        $manager->persist($massiveArt);
        $manager->flush();

        /*
        * Contact 5
        */
        $emailContact5 = new Email();
        $emailContact5->setEmail('marcel.moosbrugger@massiveArt.com');
        $emailContact5->setEmailType($emailTypeWork);
        $manager->persist($emailContact5);
        $manager->flush();

        $contact5 = new Contact();
        $contact5->setFirstName('Marcel');
        $contact5->setLastName('Moosbrugger');
        $contact5->setCreated(new Datetime());
        $contact5->setChanged(new Datetime());
        $contact5->setTitle('');
        $contact5->setPosition('');
        $contact5->setMiddleName('');
        $contact5->addEmail($emailContact5);
        $contact5->setAccount($massiveArt);
        $massiveArt->addContact($contact5);

        $manager->persist($contact5);
        $manager->persist($massiveArt);
        $manager->flush();

        /*
        * Contact 6
        */
        $emailContact6 = new Email();
        $emailContact6->setEmail('oliver.pretz@massiveArt.com');
        $emailContact6->setEmailType($emailTypeWork);
        $manager->persist($emailContact6);
        $manager->flush();

        $contact6 = new Contact();
        $contact6->setFirstName('Oliver');
        $contact6->setLastName('Pretz');
        $contact6->setCreated(new Datetime());
        $contact6->setChanged(new Datetime());
        $contact6->setTitle('');
        $contact6->setPosition('');
        $contact6->setMiddleName('');
        $contact6->addEmail($emailContact6);
        $contact6->setAccount($massiveArt);
        $massiveArt->addContact($contact6);

        $manager->persist($contact6);
        $manager->persist($massiveArt);
        $manager->flush();

        /*
        * Contact 7
        */
        $emailContact7 = new Email();
        $emailContact7->setEmail('thomas.schedler@massiveArt.com');
        $emailContact7->setEmailType($emailTypeWork);
        $manager->persist($emailContact7);
        $manager->flush();

        $contact7 = new Contact();
        $contact7->setFirstName('Thomas');
        $contact7->setLastName('Schedler');
        $contact7->setCreated(new Datetime());
        $contact7->setChanged(new Datetime());
        $contact7->setTitle('');
        $contact7->setPosition('');
        $contact7->setMiddleName('');
        $contact7->addEmail($emailContact7);
        $contact7->setAccount($massiveArt);
        $massiveArt->addContact($contact7);

        $manager->persist($contact7);
        $manager->persist($massiveArt);
        $manager->flush();

        /*
        * Contact 8
        */
        $emailContact8 = new Email();
        $emailContact8->setEmail('erfan.ebrahimnia@massiveArt.com');
        $emailContact8->setEmailType($emailTypeWork);
        $manager->persist($emailContact8);
        $manager->flush();

        $contact8 = new Contact();
        $contact8->setFirstName('Erfan');
        $contact8->setLastName('Ebrahimnia');
        $contact8->setCreated(new Datetime());
        $contact8->setChanged(new Datetime());
        $contact8->setTitle('');
        $contact8->setPosition('');
        $contact8->setMiddleName('');
        $contact8->addEmail($emailContact8);
        $contact8->setAccount($massiveArt);
        $massiveArt->addContact($contact8);

        $manager->persist($contact8);
        $manager->persist($massiveArt);
        $manager->flush();

    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 9;
    }
}

