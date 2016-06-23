<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Component\Contact\Model\ContactInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

abstract class BaseAccount extends ApiEntity implements AuditableInterface, AccountInterface
{
    /**
     * @var int
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
     * @var UserInterface
     * @Exclude
     */
    private $changer;

    /**
     * @var UserInterface
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
     * @var ContactInterface
     */
    private $mainContact;

    /**
     * @var MediaInterface
     */
    protected $logo;

    /**
     * setId.
     *
     * @param int $id
     *
     * @return BaseAccount
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return BaseAccount
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set externalId.
     *
     * @param string $externalId
     *
     * @return BaseAccount
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * Get externalId.
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set number.
     *
     * @param string $number
     *
     * @return BaseAccount
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set corporation.
     *
     * @param string $corporation
     *
     * @return BaseAccount
     */
    public function setCorporation($corporation)
    {
        $this->corporation = $corporation;

        return $this;
    }

    /**
     * Get corporation.
     *
     * @return string
     */
    public function getCorporation()
    {
        return $this->corporation;
    }

    /**
     * Set uid.
     *
     * @param string $uid
     *
     * @return BaseAccount
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set registerNumber.
     *
     * @param string $registerNumber
     *
     * @return BaseAccount
     */
    public function setRegisterNumber($registerNumber)
    {
        $this->registerNumber = $registerNumber;

        return $this;
    }

    /**
     * Get registerNumber.
     *
     * @return string
     */
    public function getRegisterNumber()
    {
        return $this->registerNumber;
    }

    /**
     * Set placeOfJurisdiction.
     *
     * @param string $placeOfJurisdiction
     *
     * @return BaseAccount
     */
    public function setPlaceOfJurisdiction($placeOfJurisdiction)
    {
        $this->placeOfJurisdiction = $placeOfJurisdiction;

        return $this;
    }

    /**
     * Get placeOfJurisdiction.
     *
     * @return string
     */
    public function getPlaceOfJurisdiction()
    {
        return $this->placeOfJurisdiction;
    }

    /**
     * Set mainEmail.
     *
     * @param string $mainEmail
     *
     * @return BaseAccount
     */
    public function setMainEmail($mainEmail)
    {
        $this->mainEmail = $mainEmail;

        return $this;
    }

    /**
     * Get mainEmail.
     *
     * @return string
     */
    public function getMainEmail()
    {
        return $this->mainEmail;
    }

    /**
     * Set mainPhone.
     *
     * @param string $mainPhone
     *
     * @return BaseAccount
     */
    public function setMainPhone($mainPhone)
    {
        $this->mainPhone = $mainPhone;

        return $this;
    }

    /**
     * Get mainPhone.
     *
     * @return string
     */
    public function getMainPhone()
    {
        return $this->mainPhone;
    }

    /**
     * Set mainFax.
     *
     * @param string $mainFax
     *
     * @return BaseAccount
     */
    public function setMainFax($mainFax)
    {
        $this->mainFax = $mainFax;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Get mainFax.
     *
     * @return string
     */
    public function getMainFax()
    {
        return $this->mainFax;
    }

    /**
     * Set mainUrl.
     *
     * @param string $mainUrl
     *
     * @return BaseAccount
     */
    public function setMainUrl($mainUrl)
    {
        $this->mainUrl = $mainUrl;

        return $this;
    }

    /**
     * Get mainUrl.
     *
     * @return string
     */
    public function getMainUrl()
    {
        return $this->mainUrl;
    }

    /**
     * getCreated.
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * setCreated.
     *
     * @param DateTime $created
     *
     * @return BaseAccount
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * getChanged.
     *
     * @return DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * setChanged.
     *
     * @param DateTime $changed
     *
     * @return $this
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * getChanger.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * setChanger.
     *
     * @param UserInterface $changer
     *
     * @return BaseAccount
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;
    }

    /**
     * getCreator.
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * setCreator.
     *
     * @param UserInterface $creator
     *
     * @return BaseAccount
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * getMainContact.
     *
     * @return Contact
     */
    public function getMainContact()
    {
        return $this->mainContact;
    }

    /**
     * setMainContact.
     *
     * @param Contact $mainContact
     *
     * @return BaseAccount
     */
    public function setMainContact($mainContact)
    {
        $this->mainContact = $mainContact;

        return $this;
    }
}
