<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactRestoredEvent;
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
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Webmozart\Assert\Assert;

final class ContactTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private ContactRepositoryInterface $contactRepository,
        private DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    /**
     * @param object|ContactInterface $resource
     */
    public function store(object $resource, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($resource, ContactInterface::class);

        $contactTitle = $resource->getTitle();
        $contactBirthday = $resource->getBirthday();

        $data = [
            'id' => $resource->getId(),
            'firstName' => $resource->getFirstName(),
            'middleName' => $resource->getMiddleName(),
            'lastName' => $resource->getLastName(),
            'titleId' => $contactTitle ? $contactTitle->getId() : null,
            'birthday' => $contactBirthday ? $contactBirthday->format('Y-m-d') : null,
            'salutation' => $resource->getSalutation(),
            'formOfAddress' => $resource->getFormOfAddress(),
            'newsletter' => $resource->getNewsletter(),
            'gender' => $resource->getGender(),
            'note' => $resource->getNote(),
            'mainEmail' => $resource->getMainEmail(),
            'mainFax' => $resource->getMainFax(),
            'mainPhone' => $resource->getMainPhone(),
            'mainUrl' => $resource->getMainUrl(),
            'addresses' => [],
            'emails' => [],
            'phones' => [],
            'faxes' => [],
            'urls' => [],
            'locales' => [],
            'bankAccounts' => [],
            'socialMediaProfiles' => [],
            'accountContacts' => [],
            'mediaIds' => [],
            'tagIds' => [],
            'categoryIds' => [],
            'created' => $resource->getCreated()->format('c'),
            'changed' => $resource->getChanged()->format('c'),
            'creatorId' => null,
            'changerId' => null,
        ];

        $creator = $resource->getCreator();
        if ($creator) {
            $data['creatorId'] = $creator->getId();
        }

        $changer = $resource->getChanger();
        if ($changer) {
            $data['changerId'] = $changer->getId();
        }

        $avatar = $resource->getAvatar();
        if ($avatar) {
            $data['avatarId'] = $avatar->getId();
        }

        foreach ($resource->getContactAddresses() as $contactAddress) {
            $address = $contactAddress->getAddress();

            $data['addresses'][] = \array_filter([
                'id' => $contactAddress->getId(),
                'typeId' => $address->getAddressType()->getId(),
                'main' => $contactAddress->getMain(),
                'billingAddress' => $address->getBillingAddress(),
                'deliveryAddress' => $address->getDeliveryAddress(),
                'primaryAddress' => $address->getPrimaryAddress(),
                'title' => $address->getTitle(),
                'street' => $address->getStreet(),
                'number' => $address->getNumber(),
                'addition' => $address->getAddition(),
                'zip' => $address->getZip(),
                'city' => $address->getCity(),
                'state' => $address->getState(),
                'countryCode' => $address->getCountryCode(),
                'postboxCity' => $address->getPostboxCity(),
                'postboxNumber' => $address->getPostboxNumber(),
                'postboxPostcode' => $address->getPostboxPostcode(),
                'latitude' => $address->getLatitude(),
                'longitude' => $address->getLongitude(),
                'note' => $address->getNote(),
            ]);
        }

        foreach ($resource->getEmails() as $email) {
            $data['emails'][] = [
                'typeId' => $email->getEmailType()->getId(),
                'email' => $email->getEmail(),
            ];
        }

        foreach ($resource->getPhones() as $phone) {
            $data['phones'][] = [
                'typeId' => $phone->getPhoneType()->getId(),
                'phone' => $phone->getPhone(),
            ];
        }

        foreach ($resource->getFaxes() as $fax) {
            $data['faxes'][] = [
                'typeId' => $fax->getFaxType()->getId(),
                'fax' => $fax->getFax(),
            ];
        }

        foreach ($resource->getUrls() as $url) {
            $data['urls'][] = [
                'typeId' => $url->getUrlType()->getId(),
                'url' => $url->getUrl(),
            ];
        }

        foreach ($resource->getLocales() as $locale) {
            $data['locales'][] = $locale->getLocale();
        }

        foreach ($resource->getSocialMediaProfiles() as $socialMediaProfile) {
            $data['socialMediaProfiles'][] = [
                'typeId' => $socialMediaProfile->getSocialMediaProfileType()->getId(),
                'username' => $socialMediaProfile->getUsername(),
            ];
        }

        foreach ($resource->getBankAccounts() as $bankAccount) {
            $data['bankAccounts'][] = [
                'id' => $bankAccount->getId(),
                'bankName' => $bankAccount->getBankName(),
                'iban' => $bankAccount->getIban(),
                'bic' => $bankAccount->getBic(),
                'public' => $bankAccount->getPublic(),
            ];
        }

        foreach ($resource->getTags() as $tag) {
            $data['tagIds'][] = $tag->getId();
        }

        foreach ($resource->getCategories() as $category) {
            $data['categoryIds'][] = $category->getId();
        }

        foreach ($resource->getAccountContacts() as $contactContact) {
            $position = $contactContact->getPosition();

            $data['accountContacts'][] = \array_filter([
                'main' => $contactContact->getMain(),
                'accountId' => $contactContact->getAccount()->getId(),
                'positionId' => $position ? $position->getId() : null,
            ]);
        }

        foreach ($resource->getMedias() as $media) {
            $data['mediaIds'][] = $media->getId();
        }

        return $this->trashItemRepository->create(
            ContactInterface::RESOURCE_KEY,
            (string) $data['id'],
            \trim($resource->getFirstName() . ' ' . $resource->getLastName()),
            \array_filter($data),
            null,
            $options,
            ContactAdmin::CONTACT_SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        /** @var ContactInterface $contact */
        $contact = $this->contactRepository->createNew();
        $contact->setFirstName($data['firstName'] ?? null);
        $contact->setMiddleName($data['middleName'] ?? null);
        $contact->setLastName($data['lastName'] ?? null);
        $contact->setBirthday(isset($data['birthday']) ? new \DateTime($data['birthday']) : null);
        $contact->setSalutation($data['salutation'] ?? null);
        $contact->setFormOfAddress($data['formOfAddress'] ?? null);
        $contact->setNewsletter($data['newsletter'] ?? null);
        $contact->setGender($data['gender'] ?? null);
        $contact->setNote($data['note'] ?? null);
        $contact->setMainEmail($data['mainEmail'] ?? null);
        $contact->setMainFax($data['mainFax'] ?? null);
        $contact->setMainUrl($data['mainUrl'] ?? null);
        $contact->setMainPhone($data['mainPhone'] ?? null);

        if ($contact instanceof Contact) {
            if ($data['changed'] ?? null) {
                $contact->setChanged(new \DateTimeImmutable($data['changed']));
            }
            if ($data['created'] ?? null) {
                $contact->setCreated(new \DateTimeImmutable($data['created']));
            }
            $contact->setCreator($this->findEntity(UserInterface::class, $data['creatorId'] ?? null));
            $contact->setChanger($this->findEntity(UserInterface::class, $data['changerId'] ?? null));
        }

        if ($avatar = $this->findEntity(MediaInterface::class, $data['avatarId'] ?? null)) {
            $contact->setAvatar($avatar);
        }

        if ($contactTitle = $this->findEntity(ContactTitle::class, $data['titleId'] ?? null)) {
            $contact->setTitle($contactTitle);
        }

        foreach (($data['addresses'] ?? []) as $addressData) {
            $address = new Address();
            $address->setAddressType($this->getReference(AddressType::class, $addressData['typeId']));
            $address->setBillingAddress($addressData['billingAddress'] ?? false);
            $address->setDeliveryAddress($addressData['deliveryAddress'] ?? false);
            $address->setPrimaryAddress($addressData['primaryAddress'] ?? false);
            $address->setTitle($addressData['title'] ?? null);
            $address->setStreet($addressData['street'] ?? null);
            $address->setNumber($addressData['number'] ?? null);
            $address->setAddition($addressData['addition'] ?? null);
            $address->setZip($addressData['zip'] ?? null);
            $address->setCity($addressData['city'] ?? null);
            $address->setState($addressData['state'] ?? null);
            $address->setCountryCode($addressData['countryCode'] ?? null);
            $address->setPostboxCity($addressData['postboxCity'] ?? null);
            $address->setPostBoxNumber($addressData['postboxNumber'] ?? null);
            $address->setPostboxPostcode($addressData['postboxPostcode'] ?? null);
            $address->setLatitude($addressData['latitude'] ?? null);
            $address->setLongitude($addressData['longitude'] ?? null);
            $address->setNote($addressData['note'] ?? null);

            $contactAddress = new ContactAddress();
            $contactAddress->setMain($addressData['main'] ?? false);
            $contactAddress->setContact($contact);
            $contactAddress->setAddress($address);

            $contact->addContactAddress($contactAddress);
        }

        foreach (($data['emails'] ?? []) as $emailData) {
            $email = new Email();
            $email->addContact($contact);
            $email->setEmail($emailData['email']);
            $email->setEmailType($this->getReference(EmailType::class, $emailData['typeId']));
            $contact->addEmail($email);
        }

        foreach (($data['phones'] ?? []) as $phoneData) {
            $phone = new Phone();
            $phone->addContact($contact);
            $phone->setPhone($phoneData['phone']);
            $phone->setPhoneType($this->getReference(PhoneType::class, $phoneData['typeId']));
            $contact->addPhone($phone);
        }

        foreach (($data['faxes'] ?? []) as $faxData) {
            $fax = new Fax();
            $fax->addContact($contact);
            $fax->setFax($faxData['fax']);
            $fax->setFaxType($this->getReference(FaxType::class, $faxData['typeId']));
            $contact->addFax($fax);
        }

        foreach (($data['urls'] ?? []) as $urlData) {
            $url = new Url();
            $url->addContact($contact);
            $url->setUrl($urlData['url']);
            $url->setUrlType($this->getReference(UrlType::class, $urlData['typeId']));
            $contact->addUrl($url);
        }

        foreach (($data['locales'] ?? []) as $locale) {
            $contactLocale = new ContactLocale();
            $contactLocale->setContact($contact);
            $contactLocale->setLocale($locale);
            $contact->addLocale($contactLocale);
        }

        foreach (($data['socialMediaProfiles'] ?? []) as $socialMediaProfileData) {
            $socialMediaProfile = new SocialMediaProfile();
            $socialMediaProfile->addContact($contact);
            $socialMediaProfile->setUsername($socialMediaProfileData['username']);
            $socialMediaProfile->setSocialMediaProfileType($this->getReference(SocialMediaProfileType::class, $socialMediaProfileData['typeId']));
            $contact->addSocialMediaProfile($socialMediaProfile);
        }

        foreach (($data['bankAccounts'] ?? []) as $bankAccountData) {
            $bankAccount = new BankAccount();
            $bankAccount->addContact($contact);
            $bankAccount->setPublic($bankAccountData['public'] ?? false);
            $bankAccount->setBankName($bankAccountData['bankName'] ?? null);
            $bankAccount->setIban($bankAccountData['iban'] ?? null);
            $bankAccount->setBic($bankAccountData['bic'] ?? null);
            $contact->addBankAccount($bankAccount);
        }

        foreach (($data['tagIds'] ?? []) as $tagId) {
            if ($tag = $this->findEntity(TagInterface::class, $tagId)) {
                $contact->addTag($tag);
            }
        }

        foreach (($data['categoryIds'] ?? []) as $categoryId) {
            if ($category = $this->findEntity(CategoryInterface::class, $categoryId)) {
                $contact->addCategory($category);
            }
        }

        foreach (($data['mediaIds'] ?? []) as $mediaId) {
            if ($media = $this->findEntity(MediaInterface::class, $mediaId)) {
                $contact->addMedia($media);
            }
        }

        foreach (($data['accountContacts'] ?? []) as $contactContactData) {
            if ($account = $this->findEntity(AccountInterface::class, $contactContactData['accountId'])) {
                $contactContact = new AccountContact();
                $contactContact->setAccount($account);
                $contactContact->setContact($contact);
                $contactContact->setMain($contactContactData['main'] ?? false);
                $contactContact->setPosition($this->findEntity(Position::class, $contactContactData['positionId'] ?? null));

                $contact->addAccountContact($contactContact);
            }
        }

        $this->domainEventCollector->collect(new ContactRestoredEvent($contact, $data));

        if (null === $this->contactRepository->findById($id)) {
            $this->doctrineRestoreHelper->persistAndFlushWithId($contact, $id);
        } else {
            $this->entityManager->persist($contact);
            $this->entityManager->flush();
        }

        return $contact;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param mixed|null $id
     *
     * @return T|null
     */
    private function findEntity(string $className, $id)
    {
        if ($id) {
            return $this->entityManager->find($className, $id);
        }

        return null;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     * @param string|int $id
     *
     * @return T
     */
    private function getReference(string $className, $id)
    {
        /** @var T $object */
        $object = $this->entityManager->getReference($className, $id);

        return $object;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            ContactAdmin::CONTACT_EDIT_FORM_VIEW,
            ['id' => 'id']
        );
    }

    public static function getResourceKey(): string
    {
        return ContactInterface::RESOURCE_KEY;
    }
}
