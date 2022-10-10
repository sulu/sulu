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
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
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
use Sulu\Bundle\ContactBundle\Trash\AccountTrashItemHandler;
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

class AccountTrashItemHandlerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<TrashItemRepositoryInterface>
     */
    private $trashItemRepository;

    /**
     * @var ObjectProphecy<AccountRepositoryInterface>
     */
    private $accountRepository;

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
     * @var AccountTrashItemHandler
     */
    private $accountTrashItemHandler;

    public function setUp(): void
    {
        $this->trashItemRepository = $this->prophesize(TrashItemRepositoryInterface::class);
        $this->accountRepository = $this->prophesize(AccountRepositoryInterface::class);
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
        $this->accountRepository->createNew(Argument::cetera())
            ->will(function() {
                return new Account();
            });

        $this->doctrineRestoreHelper->persistAndFlushWithId(Argument::cetera())
            ->will(static function($args): void {
                /** @var AccountInterface $account */
                $account = $args[0];

                static::setPrivateProperty($account, 'id', $args[1]);
            });

        $this->entityManager->getReference(Argument::cetera())
            ->will(static function($args) {
                /** @var class-string $className */
                $className = $args[0];

                $object = new $className();
                static::setPrivateProperty($object, 'id', $args[1]);

                return $object;
            });

        $this->accountTrashItemHandler = new AccountTrashItemHandler(
            $this->trashItemRepository->reveal(),
            $this->accountRepository->reveal(),
            $this->doctrineRestoreHelper->reveal(),
            $this->entityManager->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testStoreMinimal(): void
    {
        $account = $this->getMinimalAccount();

        $trashItem = $this->accountTrashItemHandler->store($account);

        $this->assertSame('1', $trashItem->getResourceId());
        $this->assertSame('Minimal Company', $trashItem->getResourceTitle());
        $this->assertSame('accounts', $trashItem->getResourceKey());
        $this->assertSame('sulu.contact.organizations', $trashItem->getResourceSecurityContext());
        $this->assertNull($trashItem->getResourceSecurityObjectId());
        $this->assertNull($trashItem->getResourceSecurityObjectType());
        $this->assertSame($this->getMinimalAccountData(), $trashItem->getRestoreData());
    }

    public function testStoreComplex(): void
    {
        $account = $this->getComplexAccount();

        $trashItem = $this->accountTrashItemHandler->store($account);

        $this->assertSame('1', $trashItem->getResourceId());
        $this->assertSame('Complex Company', $trashItem->getResourceTitle());
        $this->assertSame('accounts', $trashItem->getResourceKey());
        $this->assertSame('sulu.contact.organizations', $trashItem->getResourceSecurityContext());
        $this->assertNull($trashItem->getResourceSecurityObjectId());
        $this->assertNull($trashItem->getResourceSecurityObjectType());
        $this->assertSame($this->getComplexAccountData(), $trashItem->getRestoreData());
    }

    public function testRestoreMinimal(): void
    {
        $accountData = $this->getMinimalAccountData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($accountData);

        $this->accountRepository->findById(1)
            ->willReturn(null)
            ->shouldBeCalled();
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();

        $account = $this->accountTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame(1, $account->getId());
        $this->assertSame('Minimal Company', $account->getName());
        $this->assertSame('2020-11-05T12:15:00+01:00', $account->getCreated()->format('c'));
        $this->assertSame('2020-12-10T14:15:00+01:00', $account->getChanged()->format('c'));
    }

    public function testRestoreSameIdExists(): void
    {
        $accountData = $this->getMinimalAccountData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($accountData);

        $existAccount = new Account();
        $existAccount->setId(1);
        $this->accountRepository->findById(Argument::cetera())
            ->willReturn($existAccount)
            ->shouldBeCalled();
        $this->entityManager->persist(Argument::cetera())
            ->shouldBeCalled()
            ->will(static function($args): void {
                /** @var AccountInterface $account */
                $account = $args[0];

                static::setPrivateProperty($account, 'id', 2);
            });
        $this->domainEventCollector->collect(Argument::that(static function(DomainEvent $event) {
            static::assertSame('restored', $event->getEventType());

            return true;
        }))
            ->shouldBeCalledOnce();
        $this->entityManager->flush()
            ->shouldBeCalled();

        $account = $this->accountTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame(2, $account->getId());
        $this->assertSame('Minimal Company', $account->getName());
        $this->assertSame('2020-11-05T12:15:00+01:00', $account->getCreated()->format('c'));
        $this->assertSame('2020-12-10T14:15:00+01:00', $account->getChanged()->format('c'));
    }

    public function testRestoreComplex(): void
    {
        $accountData = $this->getComplexAccountData();

        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        $trashItem->setRestoreData($accountData);

        $this->accountRepository->findById(1)
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

        $account = $this->accountTrashItemHandler->restore($trashItem, []);

        $this->assertInstanceOf(Account::class, $account);
        $trashItem = $this->accountTrashItemHandler->store($account);
        $this->assertSame($accountData, $trashItem->getRestoreData());
    }

    private function getMinimalAccount(): AccountInterface
    {
        $account = new Account();
        $account->setId(1);
        $account->setName('Minimal Company');
        $account->setCreated(new \DateTime('2020-11-05T12:15:00+01:00'));
        $account->setChanged(new \DateTime('2020-12-10T14:15:00+01:00'));

        return $account;
    }

    /**
     * @return mixed[]
     */
    private function getMinimalAccountData(): array
    {
        return [
            'id' => 1,
            'name' => 'Minimal Company',
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
            TagInterface::class => Tag::class,
            UserInterface::class => User::class,
            CategoryInterface::class => Category::class,
        ];

        return $mapping[$className] ?? $className;
    }

    private function getComplexAccount(): AccountInterface
    {
        $account = new Account();
        $account->setId(1);
        $account->setName('Complex Company');
        $account->setNumber('000001');
        $account->setUid('UID-123');
        $account->setMainEmail('email1@example.org');
        $account->setMainUrl('example.org');
        $account->setMainPhone('+43 12345 6789');
        $account->setMainFax('+43 12345 1234 1');
        $account->setPlaceOfJurisdiction('Place of Jurisdiction');
        $account->setRegisterNumber('123456');
        $account->setNote('123456');
        $account->setCreated(new \DateTime('2020-11-05T12:15:00+01:00'));
        $account->setChanged(new \DateTime('2020-12-10T14:15:00+01:00'));

        $creator = new User();
        static::setPrivateProperty($creator, 'id', 21);
        $account->setCreator($creator);

        $changer = new User();
        static::setPrivateProperty($changer, 'id', 22);
        $account->setChanger($changer);

        $parent = new Account();
        $parent->setId(2);
        $parent->setName('Parent Company');
        $account->setParent($parent);

        $logo = new Media();
        static::setPrivateProperty($logo, 'id', 5);
        $account->setLogo($logo);

        $media1 = new Media();
        static::setPrivateProperty($media1, 'id', 6);
        $account->addMedia($media1);

        $media2 = new Media();
        static::setPrivateProperty($media2, 'id', 7);
        $account->addMedia($media2);

        $bankAccount1 = new BankAccount();
        $bankAccount1->setIban('AT026000000001349870');
        $bankAccount1->setBic('OPSKATWW');
        $bankAccount1->setBankName('Bank A');
        $bankAccount1->setPublic(true);
        $account->addBankAccount($bankAccount1);

        $bankAccount2 = new BankAccount();
        $bankAccount2->setIban('AT021420020010147558');
        $account->addBankAccount($bankAccount2);

        $emailType1 = new EmailType();
        $emailType1->setId(21);
        $emailType1->setName('sulu_contact.work');
        $email1 = new Email();
        $email1->setEmailType($emailType1);
        $email1->setEmail('email1@example.org');
        $account->addEmail($email1);

        $emailType2 = new EmailType();
        $emailType2->setId(22);
        $emailType2->setName('sulu_contact.private');
        $email2 = new Email();
        $email2->setEmailType($emailType2);
        $email2->setEmail('email2@example.com');
        $account->addEmail($email2);

        $urlType1 = new UrlType();
        $urlType1->setId(31);
        $urlType1->setName('sulu_contact.work');
        $url1 = new Url();
        $url1->setUrlType($urlType1);
        $url1->setUrl('example.org');
        $account->addUrl($url1);

        $urlType2 = new UrlType();
        $urlType2->setId(32);
        $urlType2->setName('sulu_contact.private');
        $url2 = new Url();
        $url2->setUrlType($urlType2);
        $url2->setUrl('example.com');
        $account->addUrl($url2);

        $phoneType1 = new PhoneType();
        $phoneType1->setId(41);
        $phoneType1->setName('sulu_contact.work');
        $phone1 = new Phone();
        $phone1->setPhoneType($phoneType1);
        $phone1->setPhone('+43 12345 6789');
        $account->addPhone($phone1);

        $phoneType2 = new PhoneType();
        $phoneType2->setId(42);
        $phoneType2->setName('sulu_contact.private');
        $phone2 = new Phone();
        $phone2->setPhoneType($phoneType2);
        $phone2->setPhone('+43 12345 1234');
        $account->addPhone($phone2);

        $faxType1 = new FaxType();
        $faxType1->setId(51);
        $faxType1->setName('sulu_contact.work');
        $fax1 = new Fax();
        $fax1->setFaxType($faxType1);
        $fax1->setFax('+43 12345 1234 1');
        $account->addFax($fax1);

        $faxType2 = new FaxType();
        $faxType2->setId(52);
        $faxType2->setName('sulu_contact.private');
        $fax2 = new Fax();
        $fax2->setFaxType($faxType2);
        $fax2->setFax('+43 12345 1234 2');
        $account->addFax($fax2);

        $socialMediaProfileType1 = new SocialMediaProfileType();
        $socialMediaProfileType1->setId(61);
        $socialMediaProfileType1->setName('sulu_contact.work');
        $socialMediaProfile1 = new SocialMediaProfile();
        $socialMediaProfile1->setSocialMediaProfileType($socialMediaProfileType1);
        $socialMediaProfile1->setUsername('sulu.hikaru');
        $account->addSocialMediaProfile($socialMediaProfile1);

        $socialMediaProfileType2 = new SocialMediaProfileType();
        $socialMediaProfileType2->setId(62);
        $socialMediaProfileType2->setName('sulu_contact.private');
        $socialMediaProfile2 = new SocialMediaProfile();
        $socialMediaProfile2->setSocialMediaProfileType($socialMediaProfileType2);
        $socialMediaProfile2->setUsername('hikaru123');
        $account->addSocialMediaProfile($socialMediaProfile2);

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
        $accountAddress1 = new AccountAddress();
        $accountAddress1->setAddress($address1);
        $accountAddress1->setAccount($account);
        $accountAddress1->setMain(true);
        $account->addAccountAddress($accountAddress1);

        $addressType2 = new AddressType();
        $addressType2->setId(52);
        $addressType2->setName('sulu_contact.private');
        $address2 = new Address();
        $address2->setAddressType($addressType2);
        $accountAddress2 = new AccountAddress();
        $accountAddress2->setAddress($address2);
        $accountAddress2->setAccount($account);
        $account->addAccountAddress($accountAddress2);

        $tag1 = new Tag();
        static::setPrivateProperty($tag1, 'id', 111);
        $account->addTag($tag1);

        $tag2 = new Tag();
        static::setPrivateProperty($tag2, 'id', 112);
        $account->addTag($tag2);

        $category1 = new Category();
        static::setPrivateProperty($category1, 'id', 121);
        $account->addCategory($category1);

        $category2 = new Category();
        static::setPrivateProperty($category2, 'id', 122);
        $account->addCategory($category2);

        $contact1 = new Contact();
        static::setPrivateProperty($contact1, 'id', 10);
        $position1 = new Position();
        $position1->setPosition('CTO');
        $accountContact1 = new AccountContact();
        $accountContact1->setPosition($position1);
        $accountContact1->setAccount($account);
        $accountContact1->setContact($contact1);
        $accountContact1->setMain(true);
        $account->addAccountContact($accountContact1);
        $account->setMainContact($contact1);

        $contact2 = new Contact();
        static::setPrivateProperty($contact2, 'id', 11);
        $position2 = new Position();
        $position2->setPosition('CEO');
        $accountContact2 = new AccountContact();
        $accountContact2->setPosition($position2);
        $accountContact2->setAccount($account);
        $accountContact2->setContact($contact2);
        $account->addAccountContact($accountContact2);

        $contact3 = new Contact();
        static::setPrivateProperty($contact3, 'id', 12);
        $accountContact3 = new AccountContact();
        $accountContact3->setPosition(null);
        $accountContact3->setAccount($account);
        $accountContact3->setContact($contact3);
        $account->addAccountContact($accountContact3);

        return $account;
    }

    /**
     * @return mixed[]
     */
    private function getComplexAccountData(): array
    {
        return [
            'id' => 1,
            'name' => 'Complex Company',
            'uid' => 'UID-123',
            'number' => '000001',
            'registerNumber' => '123456',
            'placeOfJurisdiction' => 'Place of Jurisdiction',
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
                    'contactId' => 10,
                ],
                [
                    'contactId' => 11,
                ],
                [
                    'contactId' => 12,
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
            'parentId' => 2,
            'logoId' => 5,
            'mainContactId' => 10,
        ];
    }
}
