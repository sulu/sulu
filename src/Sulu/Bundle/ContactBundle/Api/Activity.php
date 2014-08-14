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
use Sulu\Bundle\ContactBundle\Entity\BankAccount as BankAccountEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
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
class Activity extends ApiWrapper
{
    /**
     * @var TagManagerInterface
     */
    protected $tagManager;

    /**
     * @param ActivityEntity $activity
     * @param string $locale The locale of this product
     * @param $tagManager
     */
    public function __construct(ActivityEntity $activity, $locale, TagManagerInterface $tagManager)
    {
        $this->entity = $activity;
        $this->locale = $locale;
        $this->tagManager = $tagManager;
    }

    /**
     * Returns the id of the product
     *
     * @return int
     * @VirtualProperty
     * @SerializedName("id")
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Activity
     */
    public function setSubject($subject)
    {
        $this->entity->setSubject($subject);

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     * @SerializedName("subject")
     */
    public function getSubject()
    {
        return $this->entity->getSubject();
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Activity
     */
    public function setNote($note)
    {
        $this->entity->setNote($note);

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     * @SerializedName("note")
     */
    public function getNote()
    {
        return $this->entity->getNote();
    }

    /**
     * Set dueDate
     *
     * @param \DateTime $dueDate
     * @return Activity
     */
    public function setDueDate($dueDate)
    {
        $this->entity->setDueDate($dueDate);

        return $this;
    }

    /**
     * Get dueDate
     *
     * @return \DateTime
     * @SerializedName("dueDate")
     */
    public function getDueDate()
    {
        return $this->entity->getDueDate();
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Activity
     */
    public function setStartDate($startDate)
    {
        $this->entity->setStartDate($startDate);

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     * @SerializedName("startDate")
     */
    public function getStartDate()
    {
        return $this->entity->getStartDate();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Activity
     */
    public function setCreated($created)
    {
        $this->entity->setCreated($created);

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     * @SerializedName("created")
     */
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Activity
     */
    public function setChanged($changed)
    {
        $this->entity->setChanged($changed);

        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime
     * @SerializedName("changed")
     */
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Set activityStatus
     *
     * @param ActivityStatusEntity $activityStatus
     * @return Activity
     */
    public function setActivityStatus(ActivityStatusEntity $activityStatus = null)
    {
        $this->entity->setActivityStatus($activityStatus);

        return $this;
    }

    /**
     * Get activityStatus
     *
     * @return ActivityStatusEntity
     * @SerializedName("activityStatus")
     */
    public function getActivityStatus()
    {
        return $this->entity->getActivityStatus();
    }

    /**
     * Set activityPriority
     *
     * @param ActivityPriorityEntity $activityPriority
     * @return Activity
     */
    public function setActivityPriority(ActivityPriorityEntity $activityPriority = null)
    {
        $this->entity->setActivityPriority($activityPriority);

        return $this;
    }

    /**
     * Get activityPriority
     *
     * @return ActivityPriorityEntity
     * @SerializedName("activityPriority")
     */
    public function getActivityPriority()
    {
        return $this->entity->getActivityPriority();
    }

    /**
     * Set activityType
     *
     * @param ActivityTypeEntity $activityType
     * @return Activity
     */
    public function setActivityType(ActivityTypeEntity $activityType = null)
    {
        $this->entity->setActivityType($activityType);

        return $this;
    }

    /**
     * Get activityType
     *
     * @return ActivityTypeEntity
     * @SerializedName("activityType")
     */
    public function getActivityType()
    {
        return $this->entity->getActivityType();
    }

    /**
     * Set contact
     *
     * @param ContactEntity $contact
     * @return Activity
     */
    public function setContact(ContactEntity $contact = null)
    {
        $this->entity->setContact($contact);

        return $this;
    }

    /**
     * Get contact
     *
     * @return ContactEntity
     * @SerializedName("contact")
     */
    public function getContact()
    {

        $contact = $this->entity->getContact();
        return new Contact($contact, $this->locale, $this->tagManager);
    }

    /**
     * Set account
     *
     * @param AccountEntity $account
     * @return Activity
     */
    public function setAccount(AccountEntity $account = null)
    {
        $this->entity->setAccount($account);

        return $this;
    }

    /**
     * Get account
     *
     * @return AccountEntity
     * @SerializedName("account")
     */
    public function getAccount()
    {
        $account = $this->entity->getAccount();
        return new Account($account, $this->locale, $this->tagManager);
    }

    /**
     * Set assignedContact
     *
     * @param ContactEntity $assignedContact
     * @return Activity
     */
    public function setAssignedContact(ContactEntity $assignedContact)
    {
        $this->entity->setAssignedContact($assignedContact);

        return $this;
    }

    /**
     * Get assignedContact
     *
     * @return ContactEntity
     * @SerializedName("assignedContact")
     */
    public function getAssignedContact()
    {
        $contact = $this->entity->getAssignedContact();
        return new Contact($contact, $this->locale, $this->tagManager);
    }
}
