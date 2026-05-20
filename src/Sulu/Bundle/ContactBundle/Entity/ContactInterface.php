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

interface ContactInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'contacts';

    public function getId(): int;

    public function setFirstName(string $firstName): static;

    public function getFirstName(): string;

    public function setMiddleName(?string $middleName): static;

    public function getMiddleName(): ?string;

    public function setLastName(string $lastName): static;

    public function getLastName(): string;

    public function setTitle(?ContactTitle $title): static;

    public function getTitle(): ?ContactTitle;

    public function setPosition(?Position $position): static;

    public function getPosition(): ?Position;

    public function setBirthday(?\DateTime $birthday): static;

    public function getBirthday(): ?\DateTime;

    public function addLocale(ContactLocale $locale): static;

    public function removeLocale(ContactLocale $locale): static;

    /**
     * @return Collection<int, ContactLocale>
     */
    public function getLocales(): Collection;

    public function addNote(Note $note): static;

    public function removeNote(Note $note): static;

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection;

    public function addEmail(Email $email): static;

    public function removeEmail(Email $email): static;

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection;

    public function addPhone(Phone $phone): static;

    public function removePhone(Phone $phone): static;

    /**
     * @return Collection<int, Phone>
     */
    public function getPhones(): Collection;

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

    public function addUrl(Url $url): static;

    public function removeUrl(Url $url): static;

    /**
     * @return Collection<int, Url>
     */
    public function getUrls(): Collection;

    public function setFormOfAddress(?int $formOfAddress): static;

    public function getFormOfAddress(): ?int;

    public function addTag(TagInterface $tag): static;

    public function removeTag(TagInterface $tag): static;

    /**
     * @return Collection<int, TagInterface>
     */
    public function getTags(): Collection;

    /**
     * @return int[]
     */
    public function getTagNameArray(): array;

    public function setSalutation(?string $salutation): static;

    public function getSalutation(): ?string;

    public function addAccountContact(AccountContact $accountContact): static;

    public function removeAccountContact(AccountContact $accountContact): static;

    /**
     * @return Collection<int, AccountContact>
     */
    public function getAccountContacts(): Collection;

    public function setNewsletter(?bool $newsletter): static;

    public function getNewsletter(): ?bool;

    public function setGender(?string $gender): static;

    public function getGender(): ?string;

    public function getMainAccount(): ?AccountInterface;

    public function setMainEmail(?string $mainEmail): static;

    public function getMainEmail(): ?string;

    public function setMainPhone(?string $mainPhone): static;

    public function getMainPhone(): ?string;

    public function setMainFax(?string $mainFax): static;

    public function getMainFax(): ?string;

    public function setMainUrl(?string $mainUrl): static;

    public function getMainUrl(): ?string;

    public function addContactAddress(ContactAddress $contactAddress): static;

    public function removeContactAddress(ContactAddress $contactAddress): static;

    /**
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddresses(): Collection;

    /**
     * @return Address[]
     */
    public function getAddresses(): array;

    public function getMainAddress(): ?Address;

    public function addMedia(MediaInterface $media): static;

    public function removeMedia(MediaInterface $media): static;

    /**
     * @return Collection<int, MediaInterface>
     */
    public function getMedias(): Collection;

    public function getAvatar(): ?MediaInterface;

    public function setAvatar(?MediaInterface $avatar): static;

    public function addCategory(CategoryInterface $category): static;

    public function removeCategory(CategoryInterface $category): static;

    /**
     * @return Collection<int, CategoryInterface>
     */
    public function getCategories(): Collection;

    public function addBankAccount(BankAccount $bankAccount): static;

    public function removeBankAccount(BankAccount $bankAccount): static;

    /**
     * @return Collection<int, BankAccount>
     */
    public function getBankAccounts(): Collection;

    public function setNote(?string $note): static;

    public function getNote(): ?string;
}
