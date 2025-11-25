<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;

interface AccountInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'accounts';

    public function setName(string $name): static;

    public function getName(): string;

    public function setExternalId(?string $externalId): static;

    public function getExternalId(): ?string;

    public function setNumber(?string $number): static;

    public function getNumber(): ?string;

    public function setCorporation(?string $corporation): static;

    public function getCorporation(): ?string;

    public function setUid(?string $uid): static;

    public function getUid(): ?string;

    public function setRegisterNumber(?string $registerNumber): static;

    public function getRegisterNumber(): ?string;

    public function setPlaceOfJurisdiction(?string $placeOfJurisdiction): static;

    public function getPlaceOfJurisdiction(): ?string;

    public function setMainEmail(?string $mainEmail = null): static;

    public function getMainEmail(): ?string;

    public function setMainPhone(?string $mainPhone = null): static;

    public function getMainPhone(): ?string;

    public function setMainFax(?string $mainFax = null): static;

    public function setLogo(MediaInterface $logo): static;

    public function getLogo(): ?MediaInterface;

    public function getMainFax(): ?string;

    public function setMainUrl(?string $mainUrl = null): static;

    public function getMainUrl(): ?string;

    public function getId(): int;

    public function getMainContact(): ?ContactInterface;

    public function setMainContact(?ContactInterface $mainContact = null): static;

    public function setLft(int $lft): static;

    public function getLft(): int;

    public function setRgt(int $rgt): static;

    public function getRgt(): int;

    public function setDepth(int $depth): static;

    public function getDepth(): int;

    public function setParent(?self $parent = null): static;

    public function getParent(): ?self;

    public function addUrl(Url $url): static;

    public function removeUrl(Url $url): static;

    public function getNote(): ?string;

    public function setNote(?string $note): static;

    /**
     * @return Collection<int, Url>
     */
    public function getUrls(): Collection;

    public function addPhone(Phone $phone): static;

    public function removePhone(Phone $phone): static;

    /**
     * @return Collection<int, Phone>
     */
    public function getPhones(): Collection;

    public function addEmail(Email $email): static;

    public function removeEmail(Email $emails): static;

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection;

    /**
     * @return Collection<int, AccountInterface>
     */
    public function getChildren(): Collection;

    public function addFax(Fax $fax): static;

    public function removeFax(Fax $fax): static;

    /**
     * @return Collection<int, Fax>
     */
    public function getFaxes(): Collection;

    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile): static;

    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile): static;

    /**
     * @return Collection<int, SocialMediaProfile>
     */
    public function getSocialMediaProfiles(): Collection;

    public function addBankAccount(BankAccount $bankAccount): static;

    public function removeBankAccount(BankAccount $bankAccount): static;

    /**
     * @return Collection<int, BankAccount>
     */
    public function getBankAccounts(): Collection;

    public function addTag(TagInterface $tag): static;

    public function removeTag(TagInterface $tag): static;

    /**
     * @return Collection<int, TagInterface>
     */
    public function getTags(): Collection;

    public function addAccountContact(AccountContact $accountContact): static;

    public function removeAccountContact(AccountContact $accountContact): static;

    /**
     * @return Collection<int, AccountContact>
     */
    public function getAccountContacts(): Collection;

    /**
     * @return Collection<int, AccountAddress>
     */
    public function getAccountAddresses(): Collection;

    public function getMainAddress(): ?Address;

    /**
     * @return ContactInterface[]
     */
    public function getContacts(): array;

    public function addMedia(MediaInterface $media): static;

    public function removeMedia(MediaInterface $media): static;

    /**
     * @return Collection<int, MediaInterface>
     */
    public function getMedias(): Collection;

    public function addAccountAddress(AccountAddress $accountAddress): static;

    public function removeAccountAddress(AccountAddress $accountAddress): static;

    public function addChild(self $child): static;

    public function removeChild(self $child): static;

    public function addCategory(CategoryInterface $category): static;

    public function removeCategory(CategoryInterface $category): static;

    /**
     * @return Collection<int, CategoryInterface>
     */
    public function getCategories(): Collection;
}
