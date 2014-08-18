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

use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\ContactBundle\Entity\Account as AccountEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\Activity as ActivityEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactLocale as ContactLocaleEntity;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\TagBundle\Entity\Tag as TagEntity;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Component\Security\UserInterface;
use Hateoas\Configuration\Annotation\Relation;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;

/**
 * The AccountContact class which will be exported to the API
 *
 * @package Sulu\Bundle\ContactBundle\Api
 * @ExclusionPolicy("all")
 */
class AccountContact extends ApiWrapper
{

    /**
     * @var TagManagerInterface
     */
    protected $tagManager;

    /**
     * @param AccountContactEntity $accountContact
     * @param string $locale The locale of this product
     * @param $tagManager
     */
    public function __construct(AccountContactEntity $accountContact, $locale, TagManagerInterface $tagManager)
    {
        $this->entity = $accountContact;
        $this->locale = $locale;
        $this->tagManager = $tagManager;
    }

    /**
     * Set main
     *
     * @param boolean $main
     * @return AccountContact
     */
    public function setMain($main)
    {
        $this->entity->setMain($main);

        return $this;
    }

    /**
     * Get main
     *
     * @return boolean
     * @VirtualProperty
     * @SerializedName("main")
     * @Groups({"fullAccount"})
     */
    public function getMain()
    {
        return $this->entity->getMain();
    }

    /**
     * Get id
     *
     * @return integer
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"fullAccount"})
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set contact
     *
     * @param ContactEntity $contact
     * @return AccountContact
     */
    public function setContact(ContactEntity $contact)
    {
        $this->entity->setContact($contact);

        return $this;
    }

    /**
     * Get contact
     *
     * @return ContactEntity
     * @VirtualProperty
     * @SerializedName("contact")
     * @Groups({"fullAccount"})
     */
    public function getContact()
    {
        $contact = $this->entity->getContact();
        return array(
            'id' => $contact->getId(),
            'fullName' => $contact->getFullName()
        );
    }

    /**
     * Set account
     *
     * @param AccountEntity $account
     * @return AccountContact
     */
    public function setAccount(AccountEntity $account)
    {
        $this->entity->setAccount($account);

        return $this;
    }

    /**
     * Get account
     *
     * @return Account
     * @VirtualProperty
     * @SerializedName("account")
     * @Groups({"fullAccount"})
     */
    public function getAccount()
    {
        $account = $this->entity->getAccount();
        return array(
            'id' => $account->getId(),
            'name' => $account->getName()
        );
    }

    /**
     * Set position
     *
     * @param string $position
     * @return AccountContact
     */
    public function setPosition($position)
    {
        $this->entity->setPosition($position);

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     * @VirtualProperty
     * @SerializedName("position")
     * @Groups({"fullAccount"})
     */
    public function getPosition()
    {
        return $this->entity->getPosition();
    }
}
