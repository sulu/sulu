<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Unit\Trash;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactLocale;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\ContactBundle\Trash\ContactTrashItemHandler;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItem;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class ContactTrashItemHandlerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<TrashItemRepositoryInterface>
     */
    private $trashItemRepository;

    /**
     * @var ObjectProphecy<ContactRepositoryInterface>
     */
    private $contactRepository;

    /**
     * @var ObjectProphecy<DoctrineRestoreHelperInterface>
     */
    private $doctrineRestoreHelper;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var ContactTrashItemHandler
     */
    private $contactTrashItemHandler;

    public function setUp(): void
    {
        $this->trashItemRepository = $this->prophesize(TrashItemRepositoryInterface::class);
        $this->contactRepository = $this->prophesize(ContactRepositoryInterface::class);
        $this->doctrineRestoreHelper = $this->prophesize(DoctrineRestoreHelperInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        // we don't want expect calls on model classes instead return a real trashItem which data should be checked
        $this->trashItemRepository->create(Argument::cetera())
            ->will(function($args) {
                $trashItem = new TrashItem();
                $trashItem->setResourceKey($args[0]);
                $trashItem->setResourceId($args[1]);
                $trashItem->setResourceTitle($args[2]);
                $trashItem->setRestoreData($args[3]);
                $trashItem->setRestoreType($args[4]);
                $trashItem->setRestoreOptions($args[5]);
                $trashItem->setResourceSecurityContext($args[6]);
                $trashItem->setResourceSecurityObjectType($args[7]);
                $trashItem->setResourceSecurityObjectId($args[8]);

                return $trashItem;
            });

        // we don't want expect calls on model classes instead return a real account entity data should be checked
        $this->contactRepository->createNew(Argument::cetera())
            ->will(function() {
                return new Contact();
            });

        $this->doctrineRestoreHelper->persistAndFlushWithId(Argument::cetera())
            ->will(static function($args): void {
                /** @var AccountInterface $contact */
                $contact = $args[0];

                static::setPrivateProperty($contact, 'id', $args[1]);
            });

        $this->entityManager->getReference(Argument::cetera())
            ->will(static function($args) {
                /** @var class-string $className */
                $className = $args[0];

                $object = new $className();
                static::setPrivateProperty($object, 'id', $args[1]);

                return $object;
            });

        $this->contactTrashItemHandler = new ContactTrashItemHandler(
            $this->trashItemRepository->reveal(),
            $this->contactRepository->reveal(),
            $this->doctrineRestoreHelper->reveal(),
            $this->entityManager->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testStoreMinimal(): void
    {
        $contact = $this->getMinimalAccount();

        $trashItem = $this->contactTrashItemHandler->store($contact);

        $this->assertSame('1', $trashItem->getResourceId());
        $this->assertSame('Minimal Contact', $trashItem->getResourceTitle());
        $this->assertSame('contacts', $trashItem->getResourceKey());
        $this->assertSame('sulu.contact.people', $trashItem->getResourceSecurityContext());
        $this->assertNull($trashItem->getResourceSecurityObjectId());
        $this->assertNull($trashItem->getResourceSecurityObjectType());
        $this->assertSame($this->getMinimalAccountData(), $trashItem->getRestoreData());
    }

    public function testStoreComplex(): void
    {
        $contact = $this->getComplexContact();

        $trashItem = $this->contactTrashItemHandler->store($contact);

        $this->assertSame('1', $trashItem->getResourceId());
        $this->assertSame('Complex Contact', $trashItem->getResourceTitle());
        $this->assertSame('contacts', $trashItem->getResourceKey());
        $this->assertSame('sulu.contact.people', $trashItem->getResourceSecurityContext());
        $this->assertNull($trashItem->getResourceSecurityObjectId());
        $this->assertNull($trashItem->getResourceSecurityObjectType());
        $this->assertSame($this->getComplexAccountData(), $trashItem->getRestoreData());
    }

    public function testRestoreMinimal(): void
    {
        $contactData = $this->getMinimalAccountData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($contactData);

        $this->contactRepository->findById(1)
            ->willReturn(null)
            ->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();

        $contact = $this->contactTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame(1, $contact->getId());
        $this->assertSame('Minimal', $contact->getFirstName());
        $this->assertSame('Contact', $contact->getLastName());
        $this->assertSame('2020-11-05T12:15:00+01:00', $contact->getCreated()->format('c'));
        $this->assertSame('2020-12-10T14:15:00+01:00', $contact->getChanged()->format('c'));
    }

    public function testRestoreSameIdExists(): void
    {
        $contactData = $this->getMinimalAccountData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($contactData);

        $existAccount = new Account();
        $existAccount->setId(1);
        $this->contactRepository->findById(Argument::cetera())
            ->willReturn($existAccount)
            ->shouldBeCalled();
        $this->entityManager->persist(Argument::cetera())
            ->shouldBeCalled()
            ->will(static function($args): void {
                /** @var AccountInterface $contact */
                $contact = $args[0];

                static::setPrivateProperty($contact, 'id', 2);
            });
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();
        $this->entityManager->flush()
            ->shouldBeCalled();

        $contact = $this->contactTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame(2, $contact->getId());
        $this->assertSame('Minimal', $contact->getFirstName());
        $this->assertSame('Contact', $contact->getLastName());
        $this->assertSame('2020-11-05T12:15:00+01:00', $contact->getCreated()->format('c'));
        $this->assertSame('2020-12-10T14:15:00+01:00', $contact->getChanged()->format('c'));
    }

    public function testRestoreComplex(): void
    {
        $contactData = $this->getComplexAccountData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($contactData);

        $this->contactRepository->findById(1)
            ->willReturn(null)
            ->shouldBeCalled();
        $this->entityManager->find(Argument::cetera())
            ->shouldBeCalled()
            ->will(static function($args) {
                /** @var class-string $className */
                $className = $args[0];
                $className = static::resolveInterfaceToClass($className);

                $object = new $className();
                static::setPrivateProperty($object, 'id', $args[1]);

                return $object;
            });
        $this->doctrineRestoreHelper->persistAndFlushWithId(Argument::cetera())
            ->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();

        $contact = $this->contactTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Contact::class, $contact);
        $trashItem = $this->contactTrashItemHandler->store($contact);
        $this->assertSame($contactData, $trashItem->getRestoreData());
    }

    private function getMinimalAccount(): ContactInterface
    {
        $contact = new Contact();
        static::setPrivateProperty($contact, 'id', 1);
        $contact->setFirstName('Minimal');
        $contact->setLastName('Contact');
        $contact->setCreated(new \DateTime('2020-11-05T12:15:00+01:00'));
        $contact->setChanged(new \DateTime('2020-12-10T14:15:00+01:00'));

        return $contact;
    }

    /**
     * @return mixed[]
     */
    private function getMinimalAccountData(): array
    {
        return [
            'id' => 1,
            'firstName' => 'Minimal',
            'lastName' => 'Contact',
            'created' => '2020-11-05T12:15:00+01:00',
            'changed' => '2020-12-10T14:15:00+01:00',
        ];
    }

    /**
     * @param class-string $className
     *
     * @return class-string
     */
    private static function resolveInterfaceToClass(string $className): string
    {
        $mapping = [
            AccountInterface::class => Account::class,
            ContactInterface::class => Contact::class,
            MediaInterface::class => Media::class,
            UserInterface::class => User::class,
            TagInterface::class => Tag::class,
            CategoryInterface::class => Category::class,
        ];

        return $mapping[$className] ?? $className;
    }

    private function getComplexContact(): ContactInterface
    {
        $contact = new Contact();
        static::setPrivateProperty($contact, 'id', 1);
        $contact->setFirstName('Complex');
        $contact->setMiddleName('"Captain"');
        $contact->setLastName('Contact');
        $contact->setBirthday(new \DateTime('1991-10-17'));
        $contact->setSalutation('Mr.');
        $contact->setFormOfAddress(1);
        $contact->setNewsletter(true);
        $contact->setGender('1');
        $contact->setMainEmail('email1@example.org');
        $contact->setMainUrl('example.org');
        $contact->setMainPhone('+43 12345 6789');
        $contact->setMainFax('+43 12345 1234 1');
        $contact->setNote('123456');
        $contact->setCreated(new \DateTime('2020-11-05T12:15:00+01:00'));
        $contact->setChanged(new \DateTime('2020-12-10T14:15:00+01:00'));

        $creator = new User();
        static::setPrivateProperty($creator, 'id', 21);
        $contact->setCreator($creator);

        $changer = new User();
        static::setPrivateProperty($changer, 'id', 22);
        $contact->setChanger($changer);

        $contactTitle = new ContactTitle();
        static::setPrivateProperty($contactTitle, 'id', 4);
        $contact->setTitle($contactTitle);

        $avatar = new Media();
        static::setPrivateProperty($avatar, 'id', 5);
        $contact->setAvatar($avatar);

        $media1 = new Media();
        static::setPrivateProperty($media1, 'id', 6);
        $contact->addMedia($media1);

        $media2 = new Media();
        static::setPrivateProperty($media2, 'id', 7);
        $contact->addMedia($media2);

        $bankAccount1 = new BankAccount();
        $bankAccount1->setIban('AT026000000001349870');
        $bankAccount1->setBic('OPSKATWW');
        $bankAccount1->setBankName('Bank A');
        $bankAccount1->setPublic(true);
        $contact->addBankAccount($bankAccount1);

        $bankAccount2 = new BankAccount();
        $bankAccount2->setIban('AT021420020010147558');
        $contact->addBankAccount($bankAccount2);

        $emailType1 = new EmailType();
        $emailType1->setId(21);
        $emailType1->setName('sulu_contact.work');
        $email1 = new Email();
        $email1->setEmailType($emailType1);
        $email1->setEmail('email1@example.org');
        $contact->addEmail($email1);

        $emailType2 = new EmailType();
        $emailType2->setId(22);
        $emailType2->setName('sulu_contact.private');
        $email2 = new Email();
        $email2->setEmailType($emailType2);
        $email2->setEmail('email2@example.com');
        $contact->addEmail($email2);

        $urlType1 = new UrlType();
        $urlType1->setId(31);
        $urlType1->setName('sulu_contact.work');
        $url1 = new Url();
        $url1->setUrlType($urlType1);
        $url1->setUrl('example.org');
        $contact->addUrl($url1);

        $urlType2 = new UrlType();
        $urlType2->setId(32);
        $urlType2->setName('sulu_contact.private');
        $url2 = new Url();
        $url2->setUrlType($urlType2);
        $url2->setUrl('example.com');
        $contact->addUrl($url2);

        $phoneType1 = new PhoneType();
        $phoneType1->setId(41);
        $phoneType1->setName('sulu_contact.work');
        $phone1 = new Phone();
        $phone1->setPhoneType($phoneType1);
        $phone1->setPhone('+43 12345 6789');
        $contact->addPhone($phone1);

        $phoneType2 = new PhoneType();
        $phoneType2->setId(42);
        $phoneType2->setName('sulu_contact.private');
        $phone2 = new Phone();
        $phone2->setPhoneType($phoneType2);
        $phone2->setPhone('+43 12345 1234');
        $contact->addPhone($phone2);

        $faxType1 = new FaxType();
        $faxType1->setId(51);
        $faxType1->setName('sulu_contact.work');
        $fax1 = new Fax();
        $fax1->setFaxType($faxType1);
        $fax1->setFax('+43 12345 1234 1');
        $contact->addFax($fax1);

        $faxType2 = new FaxType();
        $faxType2->setId(52);
        $faxType2->setName('sulu_contact.private');
        $fax2 = new Fax();
        $fax2->setFaxType($faxType2);
        $fax2->setFax('+43 12345 1234 2');
        $contact->addFax($fax2);

        $contactLocale1 = new ContactLocale();
        $contactLocale1->setLocale('en');
        $contactLocale1->setContact($contact);
        $contact->addLocale($contactLocale1);

        $contactLocale2 = new ContactLocale();
        $contactLocale2->setLocale('de');
        $contactLocale2->setContact($contact);
        $contact->addLocale($contactLocale2);

        $socialMediaProfileType1 = new SocialMediaProfileType();
        $socialMediaProfileType1->setId(61);
        $socialMediaProfileType1->setName('sulu_contact.work');
        $socialMediaProfile1 = new SocialMediaProfile();
        $socialMediaProfile1->setSocialMediaProfileType($socialMediaProfileType1);
        $socialMediaProfile1->setUsername('sulu.hikaru');
        $contact->addSocialMediaProfile($socialMediaProfile1);

        $socialMediaProfileType2 = new SocialMediaProfileType();
        $socialMediaProfileType2->setId(62);
        $socialMediaProfileType2->setName('sulu_contact.private');
        $socialMediaProfile2 = new SocialMediaProfile();
        $socialMediaProfile2->setSocialMediaProfileType($socialMediaProfileType2);
        $socialMediaProfile2->setUsername('hikaru123');
        $contact->addSocialMediaProfile($socialMediaProfile2);

        $addressType1 = new AddressType();
        $addressType1->setId(51);
        $addressType1->setName('sulu_contact.work');
        $address1 = new Address();
        $address1->setTitle('Address Title 1');
        $address1->setBillingAddress(true);
        $address1->setPrimaryAddress(true);
        $address1->setDeliveryAddress(true);
        $address1->setStreet('Street');
        $address1->setNumber('123 A');
        $address1->setAddition('Top 30');
        $address1->setZip('6850');
        $address1->setCity('Dornbirn');
        $address1->setState('Vorarlberg');
        $address1->setCountryCode('AT');
        $address1->setPostboxCity('Bregenz');
        $address1->setPostboxPostcode('6900');
        $address1->setPostboxNumber('5a');
        $address1->setLatitude(9.752970);
        $address1->setLongitude(47.401428);
        $address1->setNote('Address Note 1');
        $address1->setAddressType($addressType1);
        $contactAddress1 = new ContactAddress();
        $contactAddress1->setAddress($address1);
        $contactAddress1->setContact($contact);
        $contactAddress1->setMain(true);
        $contact->addContactAddress($contactAddress1);

        $addressType2 = new AddressType();
        $addressType2->setId(52);
        $addressType2->setName('sulu_contact.private');
        $address2 = new Address();
        $address2->setAddressType($addressType2);
        $contactAddress2 = new ContactAddress();
        $contactAddress2->setAddress($address2);
        $contactAddress2->setContact($contact);
        $contact->addContactAddress($contactAddress2);

        $tag1 = new Tag();
        static::setPrivateProperty($tag1, 'id', 111);
        $contact->addTag($tag1);

        $tag2 = new Tag();
        static::setPrivateProperty($tag2, 'id', 112);
        $contact->addTag($tag2);

        $category1 = new Category();
        static::setPrivateProperty($category1, 'id', 121);
        $contact->addCategory($category1);

        $category2 = new Category();
        static::setPrivateProperty($category2, 'id', 122);
        $contact->addCategory($category2);

        $account1 = new Account();
        $account1->setId(10);
        $position1 = new Position();
        $position1->setPosition('CTO');
        $accountContact1 = new AccountContact();
        $accountContact1->setPosition($position1);
        $accountContact1->setAccount($account1);
        $accountContact1->setContact($contact);
        $accountContact1->setMain(true);
        $contact->addAccountContact($accountContact1);

        $account2 = new Account();
        $account2->setId(11);
        $position2 = new Position();
        $position2->setPosition('CEO');
        $accountContact2 = new AccountContact();
        $accountContact2->setPosition($position2);
        $accountContact2->setAccount($account2);
        $accountContact2->setContact($contact);
        $contact->addAccountContact($accountContact2);

        $account3 = new Account();
        $account3->setId(12);
        $accountContact3 = new AccountContact();
        $accountContact3->setAccount($account3);
        $accountContact3->setContact($contact);
        $contact->addAccountContact($accountContact3);

        return $contact;
    }

    /**
     * @return mixed[]
     */
    private function getComplexAccountData(): array
    {
        return [
            'id' => 1,
            'firstName' => 'Complex',
            'middleName' => '"Captain"',
            'lastName' => 'Contact',
            'titleId' => 4,
            'birthday' => '1991-10-17',
            'salutation' => 'Mr.',
            'formOfAddress' => 1,
            'newsletter' => true,
            'gender' => '1',
            'note' => '123456',
            'mainEmail' => 'email1@example.org',
            'mainFax' => '+43 12345 1234 1',
            'mainPhone' => '+43 12345 6789',
            'mainUrl' => 'example.org',
            'addresses' => [
                [
                    'typeId' => 51,
                    'main' => true,
                    'billingAddress' => true,
                    'deliveryAddress' => true,
                    'primaryAddress' => true,
                    'title' => 'Address Title 1',
                    'street' => 'Street',
                    'number' => '123 A',
                    'addition' => 'Top 30',
                    'zip' => '6850',
                    'city' => 'Dornbirn',
                    'state' => 'Vorarlberg',
                    'countryCode' => 'AT',
                    'postboxCity' => 'Bregenz',
                    'postboxNumber' => '5a',
                    'postboxPostcode' => '6900',
                    'latitude' => 9.752970,
                    'longitude' => 47.401428,
                    'note' => 'Address Note 1',
                ],
                [
                    'typeId' => 52,
                ],
            ],
            'emails' => [
                [
                    'typeId' => 21,
                    'email' => 'email1@example.org',
                ],
                [
                    'typeId' => 22,
                    'email' => 'email2@example.com',
                ],
            ],
            'phones' => [
                [
                    'typeId' => 41,
                    'phone' => '+43 12345 6789',
                ],
                [
                    'typeId' => 42,
                    'phone' => '+43 12345 1234',
                ],
            ],
            'faxes' => [
                [
                    'typeId' => 51,
                    'fax' => '+43 12345 1234 1',
                ],
                [
                    'typeId' => 52,
                    'fax' => '+43 12345 1234 2',
                ],
            ],
            'urls' => [
                [
                    'typeId' => 31,
                    'url' => 'example.org',
                ],
                [
                    'typeId' => 32,
                    'url' => 'example.com',
                ],
            ],
            'locales' => ['en', 'de'],
            'bankAccounts' => [
                [
                    'id' => null,
                    'bankName' => 'Bank A',
                    'iban' => 'AT026000000001349870',
                    'bic' => 'OPSKATWW',
                    'public' => true,
                ],
                [
                    'id' => null,
                    'bankName' => null,
                    'iban' => 'AT021420020010147558',
                    'bic' => null,
                    'public' => false,
                ],
            ],
            'socialMediaProfiles' => [
                [
                    'typeId' => 61,
                    'username' => 'sulu.hikaru',
                ],
                [
                    'typeId' => 62,
                    'username' => 'hikaru123',
                ],
            ],
            'accountContacts' => [
                [
                    'main' => true,
                    'accountId' => 10,
                ],
                [
                    'accountId' => 11,
                ],
                [
                    'accountId' => 12,
                ],
            ],
            'mediaIds' => [
                6,
                7,
            ],
            'tagIds' => [
                111,
                112,
            ],
            'categoryIds' => [
                121,
                122,
            ],
            'created' => '2020-11-05T12:15:00+01:00',
            'changed' => '2020-12-10T14:15:00+01:00',
            'creatorId' => 21,
            'changerId' => 22,
            'avatarId' => 5,
        ];
    }
}
