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

use Doctrine\Entity;
use Sulu\Bundle\ContactBundle\Entity\BankAccount as BankAccountEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Component\Rest\ApiWrapper;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;

/**
 * The BankAccount class which will be exported to the API
 *
 * @package Sulu\Bundle\ContactBundle\Api
 * @ExclusionPolicy("all")
 */
class BankAccount extends ApiWrapper
{
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
