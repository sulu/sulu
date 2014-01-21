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
use Doctrine\Common\DataFixtures\Doctrine;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\UrlType;

class LoadDefaultAccountsAndContacts extends AbstractFixture implements FixtureInterface
{

    /**
     * Load data fixtures with the passed EntityManager
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {

        $emailTypeHome = $this->getReference('email.type.home');
        $emailTypeWork = $this->getReference('email.type.work');

        /*
         * Account 1
         * */

        $emailMassiveart = new Email();
        $emailMassiveart->setEmail('office@massiveart.com');
        $emailMassiveart->setEmailType($emailTypeWork);
        $manager->persist($emailMassiveart);
        $manager->flush();

        $massiveart = new Account();
        $massiveart->setName("Massive Art");
        $massiveart->addEmail($emailMassiveart);
        $massiveart->setCreated(new Datetime());
        $massiveart->setChanged(new Datetime());
        $manager->persist($massiveart);
        $manager->flush();

        /*
         * Contact 1
         * */

        $emailContact1 = new Email();
        $emailContact1->setEmail('michael.zangerle@massiveart.com');
        $emailContact1->setEmailType($emailTypeWork);
        $manager->persist($emailContact1);
        $manager->flush();

        $contact1 = new Contact();
        $contact1->setFirstName("Michael");
        $contact1->setLastName("Zangerle");
        $contact1->setCreated(new Datetime());
        $contact1->setChanged(new Datetime());
        $contact1->addEmail($emailContact1);
        $contact1->setAccount($massiveart);
        $massiveart->addContact($contact1);

        $manager->persist($contact1);
        $manager->persist($massiveart);
        $manager->flush();

        /*
         * Contact 2
         * */

        $emailContact2 = new Email();
        $emailContact2->setEmail('johannes.wachter@massiveart.com');
        $emailContact2->setEmailType($emailTypeWork);
        $manager->persist($emailContact2);
        $manager->flush();

        $contact2 = new Contact();
        $contact2->setFirstName("Johannes");
        $contact2->setLastName("Wachter");
        $contact2->setCreated(new Datetime());
        $contact2->setChanged(new Datetime());
        $contact2->addEmail($emailContact2);
        $contact2->setAccount($massiveart);
        $massiveart->addContact($contact2);

        $manager->persist($contact2);
        $manager->persist($massiveart);
        $manager->flush();

        /*
        * Contact 3
        * */

        $emailContact3 = new Email();
        $emailContact3->setEmail('daniel.rotter@massiveart.com');
        $emailContact3->setEmailType($emailTypeWork);
        $manager->persist($emailContact3);
        $manager->flush();

        $contact3 = new Contact();
        $contact3->setFirstName("Daniel");
        $contact3->setLastName("Rotter");
        $contact3->setCreated(new Datetime());
        $contact3->setChanged(new Datetime());
        $contact3->addEmail($emailContact3);
        $contact3->setAccount($massiveart);
        $massiveart->addContact($contact3);

        $manager->persist($contact3);
        $manager->persist($massiveart);
        $manager->flush();

        /*
        * Contact 4
        * */

        $emailContact4 = new Email();
        $emailContact4->setEmail('elias.hiller@massiveart.com');
        $emailContact4->setEmailType($emailTypeWork);
        $manager->persist($emailContact4);
        $manager->flush();

        $contact4 = new Contact();
        $contact4->setFirstName("Elias");
        $contact4->setLastName("Hiller");
        $contact4->setCreated(new Datetime());
        $contact4->setChanged(new Datetime());
        $contact4->addEmail($emailContact4);
        $contact4->setAccount($massiveart);
        $massiveart->addContact($contact4);

        $manager->persist($contact4);
        $manager->persist($massiveart);
        $manager->flush();

        /*
        * Contact 5
        * */

        $emailContact5 = new Email();
        $emailContact5->setEmail('marcel.moosbrugger@massiveart.com');
        $emailContact5->setEmailType($emailTypeWork);
        $manager->persist($emailContact5);
        $manager->flush();

        $contact5 = new Contact();
        $contact5->setFirstName("Marcel");
        $contact5->setLastName("Moosbrugger");
        $contact5->setCreated(new Datetime());
        $contact5->setChanged(new Datetime());
        $contact5->addEmail($emailContact5);
        $contact5->setAccount($massiveart);
        $massiveart->addContact($contact5);

        $manager->persist($contact5);
        $manager->persist($massiveart);
        $manager->flush();

        /*
        * Contact 6
        * */

        $emailContact6 = new Email();
        $emailContact6->setEmail('oliver.pretz@massiveart.com');
        $emailContact6->setEmailType($emailTypeWork);
        $manager->persist($emailContact6);
        $manager->flush();

        $contact6 = new Contact();
        $contact6->setFirstName("Oliver");
        $contact6->setLastName("Pretz");
        $contact6->setCreated(new Datetime());
        $contact6->setChanged(new Datetime());
        $contact6->addEmail($emailContact6);
        $contact6->setAccount($massiveart);
        $massiveart->addContact($contact6);

        $manager->persist($contact6);
        $manager->persist($massiveart);
        $manager->flush();

        /*
        * Contact 7
        * */

        $emailContact7 = new Email();
        $emailContact7->setEmail('thomas.schedler@massiveart.com');
        $emailContact7->setEmailType($emailTypeWork);
        $manager->persist($emailContact7);
        $manager->flush();

        $contact7 = new Contact();
        $contact7->setFirstName("Thomas");
        $contact7->setLastName("Schedler");
        $contact7->setCreated(new Datetime());
        $contact7->setChanged(new Datetime());
        $contact7->addEmail($emailContact7);
        $contact7->setAccount($massiveart);
        $massiveart->addContact($contact7);

        $manager->persist($contact7);
        $manager->persist($massiveart);
        $manager->flush();

        /*
        * Contact 8
        * */

        $emailContact8 = new Email();
        $emailContact8->setEmail('erfan.ebrahimnia@massiveart.com');
        $emailContact8->setEmailType($emailTypeWork);
        $manager->persist($emailContact8);
        $manager->flush();

        $contact8 = new Contact();
        $contact8->setFirstName("Erfan");
        $contact8->setLastName("Ebrahimnia");
        $contact8->setCreated(new Datetime());
        $contact8->setChanged(new Datetime());
        $contact8->addEmail($emailContact8);
        $contact8->setAccount($massiveart);
        $massiveart->addContact($contact8);

        $manager->persist($contact8);
        $manager->persist($massiveart);
        $manager->flush();


    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 10;
    }
}

