<?php

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Activity
 */
class Activity extends ApiEntity
{
    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $note;

    /**
     * @var \DateTime
     */
    private $dueDate;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\ActivityStatus
     */
    private $activityStatus;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\ActivityPriority
     */
    private $activityPriority;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\ActivityType
     */
    private $activityType;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $contact;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Account
     */
    private $account;

    /**
     * @var \Sulu\Component\Security\UserInterface
     * @Exclude
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\UserInterface
     * @Exclude
     */
    private $creator;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $assignedContact;

    /**
     * Set subject
     *
     * @param string $subject
     * @return Activity
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Activity
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set dueDate
     *
     * @param \DateTime $dueDate
     * @return Activity
     */
    public function setDueDate($dueDate)
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    /**
     * Get dueDate
     *
     * @return \DateTime
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Activity
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Activity
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Activity
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
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
     * Set activityStatus
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ActivityStatus $activityStatus
     * @return Activity
     */
    public function setActivityStatus(\Sulu\Bundle\ContactBundle\Entity\ActivityStatus $activityStatus = null)
    {
        $this->activityStatus = $activityStatus;

        return $this;
    }

    /**
     * Get activityStatus
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\ActivityStatus
     */
    public function getActivityStatus()
    {
        return $this->activityStatus;
    }

    /**
     * Set activityPriority
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ActivityPriority $activityPriority
     * @return Activity
     */
    public function setActivityPriority(\Sulu\Bundle\ContactBundle\Entity\ActivityPriority $activityPriority = null)
    {
        $this->activityPriority = $activityPriority;

        return $this;
    }

    /**
     * Get activityPriority
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\ActivityPriority
     */
    public function getActivityPriority()
    {
        return $this->activityPriority;
    }

    /**
     * Set activityType
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ActivityType $activityType
     * @return Activity
     */
    public function setActivityType(\Sulu\Bundle\ContactBundle\Entity\ActivityType $activityType = null)
    {
        $this->activityType = $activityType;

        return $this;
    }

    /**
     * Get activityType
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\ActivityType
     */
    public function getActivityType()
    {
        return $this->activityType;
    }

    /**
     * Set contact
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contact
     * @return Activity
     */
    public function setContact(\Sulu\Bundle\ContactBundle\Entity\Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set account
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Account $account
     * @return Activity
     */
    public function setAccount(\Sulu\Bundle\ContactBundle\Entity\Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return Activity
     */
    public function setChanger(\Sulu\Component\Security\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return Activity
     */
    public function setCreator(\Sulu\Component\Security\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set assignedContact
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $assignedContact
     * @return Activity
     */
    public function setAssignedContact(\Sulu\Bundle\ContactBundle\Entity\Contact $assignedContact)
    {
        $this->assignedContact = $assignedContact;

        return $this;
    }

    /**
     * Get assignedContact
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    public function getAssignedContact()
    {
        return $this->assignedContact;
    }
}
