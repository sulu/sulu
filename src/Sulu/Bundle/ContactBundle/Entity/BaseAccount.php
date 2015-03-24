<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Persistence\Model\AuditableInterface;

abstract class BaseAccount extends ApiEntity implements AuditableInterface, AccountInterface
{
    const ENABLED = 0;
    const DISABLED = 1;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     * @Exclude
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     * @Exclude
     */
    private $creator;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $corporation;

    /**
     * @var integer
     */
    private $disabled = self::ENABLED;

    /**
     * @var string
     */
    private $uid;

    /**
     * @var string
     */
    private $registerNumber;

    /**
     * @var string
     */
    private $placeOfJurisdiction;

    /**
     * @var string
     */
    private $mainEmail;

    /**
     * @var string
     */
    private $mainPhone;

    /**
     * @var string
     */
    private $mainFax;

    /**
     * @var string
     */
    private $mainUrl;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $mainContact;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\TermsOfPayment
     */
    private $termsOfPayment;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\TermsOfDelivery
     */
    private $termsOfDelivery;

    /**
     * Set name
     *
     * @param string $name
     * @return BaseAccount
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set externalId
     *
     * @param string $externalId
     * @return BaseAccount
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * Get externalId
     *
     * @return string 
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set number
     *
     * @param string $number
     * @return BaseAccount
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return string 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set corporation
     *
     * @param string $corporation
     * @return BaseAccount
     */
    public function setCorporation($corporation)
    {
        $this->corporation = $corporation;

        return $this;
    }

    /**
     * Get corporation
     *
     * @return string 
     */
    public function getCorporation()
    {
        return $this->corporation;
    }

    /**
     * Set disabled
     *
     * @param integer $disabled
     * @return BaseAccount
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return integer 
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set uid
     *
     * @param string $uid
     * @return BaseAccount
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid
     *
     * @return string 
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set registerNumber
     *
     * @param string $registerNumber
     * @return BaseAccount
     */
    public function setRegisterNumber($registerNumber)
    {
        $this->registerNumber = $registerNumber;

        return $this;
    }

    /**
     * Get registerNumber
     *
     * @return string 
     */
    public function getRegisterNumber()
    {
        return $this->registerNumber;
    }

    /**
     * Set placeOfJurisdiction
     *
     * @param string $placeOfJurisdiction
     * @return BaseAccount
     */
    public function setPlaceOfJurisdiction($placeOfJurisdiction)
    {
        $this->placeOfJurisdiction = $placeOfJurisdiction;

        return $this;
    }

    /**
     * Get placeOfJurisdiction
     *
     * @return string 
     */
    public function getPlaceOfJurisdiction()
    {
        return $this->placeOfJurisdiction;
    }

    /**
     * Set mainEmail
     *
     * @param string $mainEmail
     * @return BaseAccount
     */
    public function setMainEmail($mainEmail)
    {
        $this->mainEmail = $mainEmail;

        return $this;
    }

    /**
     * Get mainEmail
     *
     * @return string 
     */
    public function getMainEmail()
    {
        return $this->mainEmail;
    }

    /**
     * Set mainPhone
     *
     * @param string $mainPhone
     * @return BaseAccount
     */
    public function setMainPhone($mainPhone)
    {
        $this->mainPhone = $mainPhone;

        return $this;
    }

    /**
     * Get mainPhone
     *
     * @return string 
     */
    public function getMainPhone()
    {
        return $this->mainPhone;
    }

    /**
     * Set mainFax
     *
     * @param string $mainFax
     * @return BaseAccount
     */
    public function setMainFax($mainFax)
    {
        $this->mainFax = $mainFax;

        return $this;
    }

    /**
     * Get mainFax
     *
     * @return string 
     */
    public function getMainFax()
    {
        return $this->mainFax;
    }

    /**
     * Set mainUrl
     *
     * @param string $mainUrl
     * @return BaseAccount
     */
    public function setMainUrl($mainUrl)
    {
        $this->mainUrl = $mainUrl;

        return $this;
    }

    /**
     * Get mainUrl
     *
     * @return string 
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param \DateTime $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * @return mixed
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * @param mixed $changer
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;
    }

    /**
     * @return mixed
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param mixed $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return Contact
     */
    public function getMainContact()
    {
        return $this->mainContact;
    }

    /**
     * @param Contact $mainContact
     */
    public function setMainContact($mainContact)
    {
        $this->mainContact = $mainContact;
    }

    /**
     * @return TermsOfPayment
     */
    public function getTermsOfPayment()
    {
        return $this->termsOfPayment;
    }

    /**
     * @param TermsOfPayment $termsOfPayment
     */
    public function setTermsOfPayment($termsOfPayment)
    {
        $this->termsOfPayment = $termsOfPayment;
    }

    /**
     * @return TermsOfDelivery
     */
    public function getTermsOfDelivery()
    {
        return $this->termsOfDelivery;
    }

    /**
     * @param TermsOfDelivery $termsOfDelivery
     */
    public function setTermsOfDelivery($termsOfDelivery)
    {
        $this->termsOfDelivery = $termsOfDelivery;
    }
}
