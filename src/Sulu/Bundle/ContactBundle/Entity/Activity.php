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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;



/**
 * Activity
 */
class Activity
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $due;

    /**
     * @var integer
     */
    private $priority;

    /**
     * @var \DateTime
     */
    private $reminder;

    /**
     * @var boolean
     */
    private $notification;

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
     * @var \Sulu\Bundle\ContactBundle\Entity\ActivityStatus
     *
     */
    private $activityStatus;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     * @Exclude
     */
    private $contact;


    /**
     * Set name
     *
     * @param string $name
     * @return Activity
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
     * Set due
     *
     * @param \DateTime $due
     * @return Activity
     */
    public function setDue($due)
    {
        $this->due = $due;

        return $this;
    }

    /**
     * Get due
     *
     * @return \DateTime
     */
    public function getDue()
    {
        return $this->due;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return Activity
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set reminder
     *
     * @param \DateTime $reminder
     * @return Activity
     */
    public function setReminder($reminder)
    {
        $this->reminder = $reminder;

        return $this;
    }

    /**
     * Get reminder
     *
     * @return \DateTime
     */
    public function getReminder()
    {
        return $this->reminder;
    }

    /**
     * Set notification
     *
     * @param boolean $notification
     * @return Activity
     */
    public function setNotification($notification)
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * Get notification
     *
     * @return boolean
     */
    public function getNotification()
    {
        return $this->notification;
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
     * Set activityStatus
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\ActivityStatus $activityStatus
     * @return Activity
     */
    public function setActivityStatus(\Sulu\Bundle\ContactBundle\Entity\ActivityStatus $activityStatus)
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
     * Set contact
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contact
     * @return Activity
     */
    public function setContact(\Sulu\Bundle\ContactBundle\Entity\Contact $contact)
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
}