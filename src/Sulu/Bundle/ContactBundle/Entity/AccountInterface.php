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

/**
 * interface for accounts.
 */
interface AccountInterface extends AuditableInterface
{
    public function setName(string $name): self;

    public function getName(): string;

    public function setExternalId(string $externalId): self;

    public function getExternalId(): ?string;

    public function setNumber(string $number): self;

    public function getNumber(): ?string;

    public function setCorporation(?string $corporation): self;

    public function getCorporation(): ?string;

    public function setUid(string $uid): self;

    public function getUid(): ?string;

    public function setRegisterNumber(string $registerNumber): self;

    public function getRegisterNumber(): ?string;

    public function setPlaceOfJurisdiction(string $placeOfJurisdiction): self;

    public function getPlaceOfJurisdiction(): ?string;

    public function setMainEmail(?string $mainEmail = null): self;

    public function getMainEmail(): ?string;

    public function setMainPhone(?string $mainPhone = null): self;

    public function getMainPhone(): ?string;

    public function setMainFax(?string $mainFax = null): self;

    public function setLogo(MediaInterface $logo): self;

    public function getLogo(): ?MediaInterface;

    public function getMainFax(): ?string;

    public function setMainUrl(?string $mainUrl = null): self;

    public function getMainUrl(): ?string;

    public function getId(): int;

    public function getMainContact(): ?ContactInterface;

    public function setMainContact(?ContactInterface $mainContact = null): self;

    public function setLft(int $lft): self;

    public function getLft(): int;

    public function setRgt(int $rgt): self;

    public function getRgt(): int;

    public function setDepth(int $depth): self;

    public function getDepth(): int;

    public function setParent(?self $parent = null): self;

    public function getParent(): ?self;

    public function addUrl(Url $url): self;

    public function removeUrl(Url $url): self;

    /**
     * @return Collection|Url[]
     */
    public function getUrls(): Collection;

    public function addPhone(Phone $phone): self;

    public function removePhone(Phone $phone): self;

    /**
     * @return Collection|Phone[]
     */
    public function getPhones(): Collection;

    public function addEmail(Email $email): self;

    public function removeEmail(Email $emails): self;

    /**
     * @return Collection|Email[]
     */
    public function getEmails(): Collection;

    public function addNote(Note $note): self;

    public function removeNote(Note $note): self;

    /**
     * @return Collection|Note[]
     */
    public function getNotes(): Collection;

    /**
     * @return Collection|AccountInterface[]
     */
    public function getChildren(): Collection;

    public function addFax(Fax $fax): self;

    public function removeFax(Fax $fax): self;

    /**
     * @return Collection|Fax[]
     */
    public function getFaxes(): Collection;

    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile): self;

    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile): self;

    /**
     * @return Collection|SocialMediaProfile[]
     */
    public function getSocialMediaProfiles(): Collection;

    public function addBankAccount(BankAccount $bankAccount): self;

    public function removeBankAccount(BankAccount $bankAccount): self;

    /**
     * @return Collection|BankAccount[]
     */
    public function getBankAccounts(): Collection;

    public function addTag(TagInterface $tag): self;

    public function removeTag(TagInterface $tag): self;

    /**
     * @return Collection|TagInterface[]
     */
    public function getTags(): Collection;

    public function addAccountContact(AccountContact $accountContact): self;

    public function removeAccountContact(AccountContact $accountContact): self;

    /**
     * @return Collection|AccountContact[]
     */
    public function getAccountContacts(): Collection;

    /**
     * @return Collection|AccountAddress[]
     */
    public function getAccountAddresses(): Collection;

    public function getMainAddress(): ?Address;

    /**
     * @return ContactInterface[]
     */
    public function getContacts(): array;

    public function addMedia(MediaInterface $media): self;

    public function removeMedia(MediaInterface $media): self;

    /**
     * @return Collection|MediaInterface[]
     */
    public function getMedias(): Collection;

    public function addAccountAddress(AccountAddress $accountAddress): self;

    public function removeAccountAddress(AccountAddress $accountAddress): self;

    public function addChild(self $child): self;

    public function removeChild(self $child): self;

    public function addCategory(CategoryInterface $category): self;

    public function removeCategory(CategoryInterface $category): self;

    /**
     * @return Collection|CategoryInterface[]
     */
    public function getCategories(): Collection;
}
