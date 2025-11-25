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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\UserInterface;

class Contact extends ApiEntity implements ContactInterface
{
    use AuditableTrait;

    #[Expose]
    #[Groups(['frontend', 'partialContact', 'fullContact'])]
    protected int $id;

    protected string $firstName = '';

    protected ?string $middleName = null;

    protected string $lastName = '';

    protected ?ContactTitle $title = null;

    protected ?\DateTime $birthday = null;

    /**
     * @var Collection<int, ContactLocale>
     */
    protected $locales;

    #[Groups(['fullContact'])]
    protected ?UserInterface $changer = null;

    #[Groups(['fullContact'])]
    protected ?UserInterface $creator = null;

    protected ?string $note = null;

    /**
     * @var Collection<int, Note>
     *
     * @deprecated
     */
    #[Groups(['fullContact'])]
    protected $notes;

    /**
     * @var Collection<int, Email>
     */
    #[Groups(['fullContact', 'partialContact'])]
    protected $emails;

    /**
     * @var Collection<int, Phone>
     */
    #[Groups(['fullContact'])]
    protected $phones;

    /**
     * @var Collection<int, Fax>
     */
    #[Groups(['fullContact'])]
    protected $faxes;

    /**
     * @var Collection<int, SocialMediaProfile>
     */
    #[Groups(['fullContact'])]
    protected $socialMediaProfiles;

    protected ?int $formOfAddress = null;

    protected ?string $salutation = null;

    /**
     * @var Collection<int, TagInterface>
     */
    #[Accessor(getter: 'getTagNameArray')]
    #[Groups(['fullContact'])]
    #[Type('array')]
    protected $tags;

    /**
     * main account.
     *
     * @var string
     */
    #[Accessor(getter: 'getMainAccount')]
    #[Groups(['fullContact'])]
    protected $account;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[Accessor(getter: 'getAddresses')]
    #[Groups(['fullContact'])]
    protected $addresses;

    /**
     * @var Collection<int, AccountContact>
     */
    #[Exclude]
    protected $accountContacts;

    protected ?bool $newsletter = null;

    protected ?string $gender = null;

    protected ?string $mainEmail = null;

    protected ?string $mainPhone = null;

    protected ?string $mainFax = null;

    protected ?string $mainUrl = null;

    /**
     * @var Collection<int, ContactAddress>
     */
    #[Exclude]
    protected $contactAddresses;

    /**
     * @var Collection<int, MediaInterface>
     */
    #[Groups(['fullContact'])]
    protected $medias;

    /**
     * @var Collection<int, CategoryInterface>
     */
    #[Groups(['fullContact'])]
    protected $categories;

    /**
     * @var Collection<int, Url>
     */
    #[Groups(['fullContact'])]
    protected $urls;

    /**
     * @var Collection<int, BankAccount>
     */
    #[Groups(['fullContact'])]
    protected $bankAccounts;

    protected ?MediaInterface $avatar = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->locales = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->faxes = new ArrayCollection();
        $this->socialMediaProfiles = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->accountContacts = new ArrayCollection();
        $this->contactAddresses = new ArrayCollection();
        $this->bankAccounts = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setMiddleName(?string $middleName): static
    {
        $this->middleName = $middleName;

        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setAvatar(?MediaInterface $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    #[VirtualProperty]
    #[SerializedName('fullName')]
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function setTitle(?ContactTitle $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?ContactTitle
    {
        return $this->title;
    }

    public function setPosition(?Position $position): static
    {
        $mainAccountContact = $this->getMainAccountContact();
        if ($mainAccountContact) {
            $mainAccountContact->setPosition($position);
        }

        return $this;
    }

    #[VirtualProperty]
    #[Groups(['fullContact'])]
    public function getPosition(): ?Position
    {
        $mainAccountContact = $this->getMainAccountContact();
        if ($mainAccountContact) {
            return $mainAccountContact->getPosition();
        }

        return null;
    }

    public function setBirthday(?\DateTime $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }

    public function addLocale(ContactLocale $locale): static
    {
        $this->locales[] = $locale;

        return $this;
    }

    public function removeLocale(ContactLocale $locale): static
    {
        $this->locales->removeElement($locale);

        return $this;
    }

    /**
     * @return Collection<int, ContactLocale>
     */
    public function getLocales(): Collection
    {
        return $this->locales;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function addNote(Note $note): static
    {
        $this->notes[] = $note;

        return $this;
    }

    public function removeNote(Note $note): static
    {
        $this->notes->removeElement($note);

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addEmail(Email $email): static
    {
        $this->emails[] = $email;

        return $this;
    }

    public function removeEmail(Email $email): static
    {
        $this->emails->removeElement($email);

        return $this;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function addPhone(Phone $phone): static
    {
        $this->phones[] = $phone;

        return $this;
    }

    public function removePhone(Phone $phone): static
    {
        $this->phones->removeElement($phone);

        return $this;
    }

    /**
     * @return Collection<int, Phone>
     */
    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function addFax(Fax $fax): static
    {
        $this->faxes[] = $fax;

        return $this;
    }

    public function removeFax(Fax $fax): static
    {
        $this->faxes->removeElement($fax);

        return $this;
    }

    /**
     * @return Collection<int, Fax>
     */
    public function getFaxes(): Collection
    {
        return $this->faxes;
    }

    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile): static
    {
        $this->socialMediaProfiles[] = $socialMediaProfile;

        return $this;
    }

    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile): static
    {
        $this->socialMediaProfiles->removeElement($socialMediaProfile);

        return $this;
    }

    /**
     * @return Collection<int, SocialMediaProfile>
     */
    public function getSocialMediaProfiles(): Collection
    {
        return $this->socialMediaProfiles;
    }

    public function addUrl(Url $url): static
    {
        $this->urls[] = $url;

        return $this;
    }

    public function removeUrl(Url $url): static
    {
        $this->urls->removeElement($url);

        return $this;
    }

    /**
     * @return Collection<int, Url>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function setFormOfAddress(?int $formOfAddress): static
    {
        $this->formOfAddress = $formOfAddress;

        return $this;
    }

    public function getFormOfAddress(): ?int
    {
        return $this->formOfAddress;
    }

    public function setSalutation(?string $salutation): static
    {
        $this->salutation = $salutation;

        return $this;
    }

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    public function addTag(TagInterface $tag): static
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function removeTag(TagInterface $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * @return Collection<int, TagInterface>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @return string[]
     */
    public function getTagNameArray(): array
    {
        $tags = [];

        foreach ($this->getTags() as $tag) {
            $tags[] = $tag->getName();
        }

        return $tags;
    }

    public function addAccountContact(AccountContact $accountContact): static
    {
        $this->accountContacts[] = $accountContact;

        return $this;
    }

    public function removeAccountContact(AccountContact $accountContact): static
    {
        $this->accountContacts->removeElement($accountContact);

        return $this;
    }

    /**
     * @return Collection<int, AccountContact>
     */
    public function getAccountContacts(): Collection
    {
        return $this->accountContacts;
    }

    public function setNewsletter(?bool $newsletter): static
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    public function getNewsletter(): ?bool
    {
        return $this->newsletter;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getMainAccount(): ?AccountInterface
    {
        $mainAccountContact = $this->getMainAccountContact();
        if (null !== $mainAccountContact) {
            return $mainAccountContact->getAccount();
        }

        return null;
    }

    /**
     * Returns main account contact.
     */
    protected function getMainAccountContact(): ?AccountContact
    {
        $accountContacts = $this->getAccountContacts();

        /** @var AccountContact $accountContact */
        foreach ($accountContacts as $accountContact) {
            if ($accountContact->getMain()) {
                return $accountContact;
            }
        }

        return null;
    }

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        $contactAddresses = $this->getContactAddresses();
        $addresses = [];

        /** @var ContactAddress $contactAddress */
        foreach ($contactAddresses as $contactAddress) {
            $address = $contactAddress->getAddress();
            $address->setPrimaryAddress($contactAddress->getMain());
            $addresses[] = $address;
        }

        return $addresses;
    }

    public function setMainEmail(?string $mainEmail): static
    {
        $this->mainEmail = $mainEmail;

        return $this;
    }

    public function getMainEmail(): ?string
    {
        return $this->mainEmail;
    }

    public function setMainPhone(?string $mainPhone): static
    {
        $this->mainPhone = $mainPhone;

        return $this;
    }

    public function getMainPhone(): ?string
    {
        return $this->mainPhone;
    }

    public function setMainFax(?string $mainFax): static
    {
        $this->mainFax = $mainFax;

        return $this;
    }

    public function getMainFax(): ?string
    {
        return $this->mainFax;
    }

    public function setMainUrl(?string $mainUrl): static
    {
        $this->mainUrl = $mainUrl;

        return $this;
    }

    public function getMainUrl(): ?string
    {
        return $this->mainUrl;
    }

    public function addContactAddress(ContactAddress $contactAddress): static
    {
        $this->contactAddresses[] = $contactAddress;

        return $this;
    }

    public function removeContactAddress(ContactAddress $contactAddress): static
    {
        $this->contactAddresses->removeElement($contactAddress);

        return $this;
    }

    /**
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddresses(): Collection
    {
        return $this->contactAddresses;
    }

    public function getMainAddress(): ?Address
    {
        $contactAddresses = $this->getContactAddresses();

        /** @var ContactAddress $contactAddress */
        foreach ($contactAddresses as $contactAddress) {
            if ($contactAddress->getMain()) {
                return $contactAddress->getAddress();
            }
        }

        return null;
    }

    public function addMedia(MediaInterface $media): static
    {
        $this->medias[] = $media;

        return $this;
    }

    public function removeMedia(MediaInterface $media): static
    {
        $this->medias->removeElement($media);

        return $this;
    }

    /**
     * @return Collection<int, MediaInterface>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function getAvatar(): ?MediaInterface
    {
        return $this->avatar;
    }

    public function addCategory(CategoryInterface $category): static
    {
        $this->categories[] = $category;

        return $this;
    }

    public function removeCategory(CategoryInterface $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, CategoryInterface>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addBankAccount(BankAccount $bankAccount): static
    {
        $this->bankAccounts[] = $bankAccount;

        return $this;
    }

    public function removeBankAccount(BankAccount $bankAccounts): static
    {
        $this->bankAccounts->removeElement($bankAccounts);

        return $this;
    }

    /**
     * @return Collection<int, BankAccount>
     */
    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'firstName' => $this->getFirstName(),
            'middleName' => $this->getMiddleName(),
            'lastName' => $this->getLastName(),
            'title' => $this->getTitle(),
            'position' => $this->getPosition(),
            'birthday' => $this->getBirthday(),
            'created' => $this->getCreated(),
            'changed' => $this->getChanged(),
        ];
    }
}
