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
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

#[ExclusionPolicy('all')]
class Address
{
    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $street = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $number = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $addition = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $zip = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $city = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $state = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private int $id;

    #[Groups(['fullAccount', 'fullContact'])]
    #[Expose]
    private AddressType $addressType;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $countryCode = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?bool $primaryAddress = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?bool $deliveryAddress = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?bool $billingAddress = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $postboxNumber = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $postboxPostcode = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $postboxCity = null;

    /**
     * @var Collection<int, ContactAddress>
     */
    private Collection $contactAddresses;

    /**
     * @var Collection<int, AccountAddress>
     */
    private Collection $accountAddresses;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $note = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?string $title = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?float $latitude = null;

    #[Groups(['fullAccount', 'partialAccount', 'fullContact', 'partialContact'])]
    #[Expose]
    private ?float $longitude = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accountAddresses = new ArrayCollection();
        $this->contactAddresses = new ArrayCollection();
    }

    public function setStreet(?string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setAddition(?string $addition): static
    {
        $this->addition = $addition;

        return $this;
    }

    public function getAddition(): ?string
    {
        return $this->addition;
    }

    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setAddressType(AddressType $addressType): static
    {
        $this->addressType = $addressType;

        return $this;
    }

    public function getAddressType(): AddressType
    {
        return $this->addressType;
    }

    public function setCountryCode(?string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getCountry(): ?Country
    {
        if (!$this->countryCode) {
            return null;
        }

        return new Country($this->countryCode);
    }

    public function setPrimaryAddress(?bool $primaryAddress): static
    {
        $this->primaryAddress = $primaryAddress;

        return $this;
    }

    public function getPrimaryAddress(): ?bool
    {
        return $this->primaryAddress;
    }

    public function setDeliveryAddress(?bool $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getDeliveryAddress(): ?bool
    {
        return $this->deliveryAddress;
    }

    public function setBillingAddress(?bool $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingAddress(): ?bool
    {
        return $this->billingAddress;
    }

    public function setPostboxNumber(?string $postboxNumber): static
    {
        $this->postboxNumber = $postboxNumber;

        return $this;
    }

    public function getPostboxNumber(): ?string
    {
        return $this->postboxNumber;
    }

    public function setPostboxPostcode(?string $postboxPostcode): static
    {
        $this->postboxPostcode = $postboxPostcode;

        return $this;
    }

    public function getPostboxPostcode(): ?string
    {
        return $this->postboxPostcode;
    }

    public function setPostboxCity(?string $postboxCity): static
    {
        $this->postboxCity = $postboxCity;

        return $this;
    }

    public function getPostboxCity(): ?string
    {
        return $this->postboxCity;
    }

    public function addContactAddress(ContactAddress $contactAddresses): static
    {
        $this->contactAddresses[] = $contactAddresses;

        return $this;
    }

    public function removeContactAddress(ContactAddress $contactAddresses): static
    {
        $this->contactAddresses->removeElement($contactAddresses);

        return $this;
    }

    /**
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddresses(): Collection
    {
        return $this->contactAddresses;
    }

    public function addAccountAddress(AccountAddress $accountAddresses): static
    {
        $this->accountAddresses[] = $accountAddresses;

        return $this;
    }

    public function removeAccountAddress(AccountAddress $accountAddresses): static
    {
        $this->accountAddresses->removeElement($accountAddresses);

        return $this;
    }

    /**
     * @return Collection<int, AccountAddress>
     */
    public function getAccountAddresses(): Collection
    {
        return $this->accountAddresses;
    }

    public function hasRelations(): bool
    {
        if (!$this->getContactAddresses()->isEmpty()
            || !$this->getAccountAddresses()->isEmpty()
        ) {
            return true;
        }

        return false;
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

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }
}
