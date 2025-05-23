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
use Sulu\Bundle\ContactBundle\Domain\Event\AccountRestoredEvent;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
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

final class AccountTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    public function __construct(
        private TrashItemRepositoryInterface $trashItemRepository,
        private AccountRepositoryInterface $accountRepository,
        private DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
    }

    /**
     * @param object|AccountInterface $resource
     */
    public function store(object $resource, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($resource, AccountInterface::class);

        $data = [
            'id' => $resource->getId(),
            'name' => $resource->getName(),
            'uid' => $resource->getUid(),
            'number' => $resource->getNumber(),
            'externalId' => $resource->getExternalId(),
            'corporation' => $resource->getCorporation(),
            'registerNumber' => $resource->getRegisterNumber(),
            'placeOfJurisdiction' => $resource->getPlaceOfJurisdiction(),
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

        $parent = $resource->getParent();
        if ($parent) {
            $data['parentId'] = $parent->getId();
        }

        $creator = $resource->getCreator();
        if ($creator) {
            $data['creatorId'] = $creator->getId();
        }

        $changer = $resource->getChanger();
        if ($changer) {
            $data['changerId'] = $changer->getId();
        }

        $logo = $resource->getLogo();
        if ($logo) {
            $data['logoId'] = $logo->getId();
        }

        $mainContact = $resource->getMainContact();
        if ($mainContact) {
            $data['mainContactId'] = $mainContact->getId();
        }

        foreach ($resource->getAccountAddresses() as $accountAddress) {
            $address = $accountAddress->getAddress();

            $data['addresses'][] = \array_filter([
                'id' => $accountAddress->getId(),
                'typeId' => $address->getAddressType()->getId(),
                'main' => $accountAddress->getMain(),
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

        foreach ($resource->getAccountContacts() as $accountContact) {
            $position = $accountContact->getPosition();

            $data['accountContacts'][] = \array_filter([
                'main' => $accountContact->getMain(),
                'contactId' => $accountContact->getContact()->getId(),
                'positionId' => $position ? $position->getId() : null,
            ]);
        }

        foreach ($resource->getMedias() as $media) {
            $data['mediaIds'][] = $media->getId();
        }

        return $this->trashItemRepository->create(
            AccountInterface::RESOURCE_KEY,
            (string) $data['id'],
            $data['name'],
            \array_filter($data),
            null,
            $options,
            ContactAdmin::ACCOUNT_SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = (int) $trashItem->getResourceId();
        $data = $trashItem->getRestoreData();

        /** @var AccountInterface $account */
        $account = $this->accountRepository->createNew();
        $account->setName($data['name']);
        $account->setUid($data['uid'] ?? null);
        $account->setNumber($data['number'] ?? null);
        $account->setExternalId($data['externalId'] ?? null);
        $account->setCorporation($data['corporation'] ?? null);
        $account->setRegisterNumber($data['registerNumber'] ?? null);
        $account->setPlaceOfJurisdiction($data['placeOfJurisdiction'] ?? null);
        $account->setNote($data['note'] ?? null);
        $account->setMainEmail($data['mainEmail'] ?? null);
        $account->setMainFax($data['mainFax'] ?? null);
        $account->setMainUrl($data['mainUrl'] ?? null);
        $account->setMainPhone($data['mainPhone'] ?? null);

        if ($account instanceof Account) {
            if (\is_string($data['changed'] ?? null)) {
                $account->setChanged(new \DateTimeImmutable($data['changed']));
            }
            if (\is_string($data['created'] ?? null)) {
                $account->setCreated(new \DateTimeImmutable($data['created']));
            }
            $account->setCreator($this->findEntity(UserInterface::class, $data['creatorId'] ?? null));
            $account->setChanger($this->findEntity(UserInterface::class, $data['changerId'] ?? null));
        }

        if ($logo = $this->findEntity(MediaInterface::class, $data['logoId'] ?? null)) {
            $account->setLogo($logo);
        }

        if ($parent = $this->findEntity(AccountInterface::class, $data['parentId'] ?? null)) {
            $account->setParent($parent);
        }

        if ($mainContact = $this->findEntity(ContactInterface::class, $data['mainContactId'] ?? null)) {
            $account->setMainContact($mainContact);
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

            $accountAddress = new AccountAddress();
            $accountAddress->setMain($addressData['main'] ?? false);
            $accountAddress->setAccount($account);
            $accountAddress->setAddress($address);

            $account->addAccountAddress($accountAddress);
        }

        foreach (($data['emails'] ?? []) as $emailData) {
            $email = new Email();
            $email->addAccount($account);
            $email->setEmail($emailData['email']);
            $email->setEmailType($this->getReference(EmailType::class, $emailData['typeId']));
            $account->addEmail($email);
        }

        foreach (($data['phones'] ?? []) as $phoneData) {
            $phone = new Phone();
            $phone->addAccount($account);
            $phone->setPhone($phoneData['phone']);
            $phone->setPhoneType($this->getReference(PhoneType::class, $phoneData['typeId']));
            $account->addPhone($phone);
        }

        foreach (($data['faxes'] ?? []) as $faxData) {
            $fax = new Fax();
            $fax->addAccount($account);
            $fax->setFax($faxData['fax']);
            $fax->setFaxType($this->getReference(FaxType::class, $faxData['typeId']));
            $account->addFax($fax);
        }

        foreach (($data['urls'] ?? []) as $urlData) {
            $url = new Url();
            $url->addAccount($account);
            $url->setUrl($urlData['url']);
            $url->setUrlType($this->getReference(UrlType::class, $urlData['typeId']));
            $account->addUrl($url);
        }

        foreach (($data['socialMediaProfiles'] ?? []) as $socialMediaProfileData) {
            $socialMediaProfile = new SocialMediaProfile();
            $socialMediaProfile->addAccount($account);
            $socialMediaProfile->setUsername($socialMediaProfileData['username']);
            $socialMediaProfile->setSocialMediaProfileType($this->getReference(SocialMediaProfileType::class, $socialMediaProfileData['typeId']));
            $account->addSocialMediaProfile($socialMediaProfile);
        }

        foreach (($data['bankAccounts'] ?? []) as $bankAccountData) {
            $bankAccount = new BankAccount();
            $bankAccount->addAccount($account);
            $bankAccount->setPublic($bankAccountData['public'] ?? false);
            $bankAccount->setBankName($bankAccountData['bankName'] ?? null);
            $bankAccount->setIban($bankAccountData['iban'] ?? null);
            $bankAccount->setBic($bankAccountData['bic'] ?? null);
            $account->addBankAccount($bankAccount);
        }

        foreach (($data['tagIds'] ?? []) as $tagId) {
            if ($tag = $this->findEntity(TagInterface::class, $tagId)) {
                $account->addTag($tag);
            }
        }

        foreach (($data['categoryIds'] ?? []) as $categoryId) {
            if ($category = $this->findEntity(CategoryInterface::class, $categoryId)) {
                $account->addCategory($category);
            }
        }

        foreach (($data['mediaIds'] ?? []) as $mediaId) {
            if ($media = $this->findEntity(MediaInterface::class, $mediaId)) {
                $account->addMedia($media);
            }
        }

        foreach (($data['accountContacts'] ?? []) as $accountContactData) {
            if ($contact = $this->findEntity(ContactInterface::class, $accountContactData['contactId'])) {
                $accountContact = new AccountContact();
                $accountContact->setAccount($account);
                $accountContact->setContact($contact);
                $accountContact->setMain($accountContactData['main'] ?? false);
                $accountContact->setPosition($this->findEntity(Position::class, $accountContactData['positionId'] ?? null));

                $account->addAccountContact($accountContact);
            }
        }

        $this->domainEventCollector->collect(new AccountRestoredEvent($account, $data));

        if (null === $this->accountRepository->findById($id)) {
            $this->doctrineRestoreHelper->persistAndFlushWithId($account, $id);
        } else {
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        }

        return $account;
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
            ContactAdmin::ACCOUNT_EDIT_FORM_VIEW,
            ['id' => 'id']
        );
    }

    public static function getResourceKey(): string
    {
        return AccountInterface::RESOURCE_KEY;
    }
}
