<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Entity\Contact;

/**
 * User.
 *
 * @ExclusionPolicy("all")
 */
class User extends BaseUser
{
    /**
     * @var Contact
     * @Expose
     */
    private $contact;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Expose
     */
    private $userRoles;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Expose
     */
    private $userGroups;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userSettings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->userSettings = new ArrayCollection();
    }

    /**
     * Add userRoles.
     *
     * @param UserRole $userRoles
     *
     * @return User
     */
    public function addUserRole(UserRole $userRoles)
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    /**
     * Remove userRoles.
     *
     * @param UserRole $userRoles
     */
    public function removeUserRole(UserRole $userRoles)
    {
        $this->userRoles->removeElement($userRoles);
    }

    /**
     * Get userRoles.
     *
     * @return ArrayCollection
     */
    public function getUserRoles()
    {
        return $this->userRoles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        $roles = parent::getRoles();

        foreach ($this->getUserRoles() as $userRole) {
            /** @var UserRole $userRole */
            $roles[] = $userRole->getRole()->getIdentifier();
        }

        return $roles;
    }

    /**
     * Add userGroups.
     *
     * @param UserGroup $userGroups
     *
     * @return User
     */
    public function addUserGroup(UserGroup $userGroups)
    {
        $this->userGroups[] = $userGroups;

        return $this;
    }

    /**
     * Remove userGroups.
     *
     * @param UserGroup $userGroups
     */
    public function removeUserGroup(UserGroup $userGroups)
    {
        $this->userGroups->removeElement($userGroups);
    }

    /**
     * Get userGroups.
     *
     * @return ArrayCollection
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * Add userSettings.
     *
     * @param UserSetting $userSettings
     *
     * @return User
     */
    public function addUserSetting(UserSetting $userSettings)
    {
        $this->userSettings[] = $userSettings;

        return $this;
    }

    /**
     * Remove userSettings.
     *
     * @param UserSetting $userSettings
     */
    public function removeUserSetting(UserSetting $userSettings)
    {
        $this->userSettings->removeElement($userSettings);
    }

    /**
     * Get userSettings.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserSettings()
    {
        return $this->userSettings;
    }

    /**
     * Set contact.
     *
     * @param Contact $contact
     *
     * @return User
     */
    public function setContact(Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->getContact()->getFullName();
    }
}
