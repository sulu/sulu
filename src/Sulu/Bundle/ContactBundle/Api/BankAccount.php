<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Api;

use Sulu\Bundle\ContactBundle\Entity\Account as AccountEntity;
use Doctrine\Entity;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress as AccountAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory as AccountCategoryEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\Activity as ActivityEntity;
use Sulu\Bundle\ContactBundle\Entity\ActivityPriority as ActivityPriorityEntity;
use Sulu\Bundle\ContactBundle\Entity\ActivityStatus as ActivityStatusEntity;
use Sulu\Bundle\ContactBundle\Entity\ActivityType as ActivityTypeEntity;
use Sulu\Bundle\ContactBundle\Entity\AddressType as AddressTypeEntity;
use Sulu\Bundle\ContactBundle\Entity\BankAccount as BankAccountEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery as TermsOfDeliveryEntity;
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment as TermsOfPaymentEntity;
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\TagBundle\Entity\Tag as TagEntity;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\UserInterface;
use Hateoas\Configuration\Annotation\Relation;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;

/**
 * The UrlType class which will be exported to the API
 *
 * @package Sulu\Bundle\ContactBundle\Api
 * @ExclusionPolicy("all")
 */
class BankAccount extends ApiWrapper
{
    /**
     * @var TagManagerInterface
     */
    protected $tagManager;

    /**
     * @param \Sulu\Bundle\ContactBundle\Entity\BankAccount $account
     */
    public function __construct(BankAccountEntity $account)
    {
        $this->entity = $account;
    }

    /**
     * Returns the id of the product
     *
     * @return int
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"fullAccount"})
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set bic
     *
     * @param string $bic
     * @return BankAccount
     */
    public function setBic($bic)
    {
        $this->entity->setBic($bic);

        return $this;
    }

    /**
     * Get bic
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("bic")
     * @Groups({"fullAccount"})
     */
    public function getBic()
    {
        return $this->entity->getBic();
    }

    /**
     * Set iban
     *
     * @param string $iban
     * @return BankAccount
     */
    public function setIban($iban)
    {
        $this->setIban($iban);

        return $this;
    }

    /**
     * Get iban
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("iban")
     * @Groups({"fullAccount"})
     */
    public function getIban()
    {
        return $this->entity->getIban();
    }

    /**
     * Set public
     *
     * @param boolean $public
     * @return BankAccount
     */
    public function setPublic($public)
    {
        $this->entity->setPublic($public);

        return $this;
    }

    /**
     * Get public
     *
     * @return boolean
     * @VirtualProperty
     * @SerializedName("public")
     * @Groups({"fullAccount"})
     */
    public function getPublic()
    {
        return $this->entity->getPublic();
    }

    /**
     * Set bankName
     *
     * @param string $bankName
     * @return BankAccount
     */
    public function setBankName($bankName)
    {
        $this->entity->setBankName($bankName);

        return $this;
    }

    /**
     * Get bankName
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("bankName")
     * @Groups({"fullAccount"})
     */
    public function getBankName()
    {
        return $this->entity->getBankName();
    }
}
