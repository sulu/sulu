<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\Contact\Model\ContactInterface;

/**
 * User.
 *
 * @ExclusionPolicy("all")
 */
class User extends BaseUser
{
    /**
     * @var ContactInterface
     * @Expose
     */
    protected $contact;

    /**
     * @var Collection
     * @Expose
     */
    protected $userRoles;

    /**
     * @var Collection
     * @Expose
     */
    protected $userGroups;

    /**
     * @var Collection
     */
    protected $userSettings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->userRoles = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->userSettings = new ArrayCollection();
    }

    /**
     * Add userRoles.
     *
     * @param UserRole $userRoles
     *
     * @return self
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
     * @return self
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
     * @return self
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
     * @return Collection
     */
    public function getUserSettings()
    {
        return $this->userSettings;
    }

    /**
     * Set contact.
     *
     * @param ContactInterface $contact
     *
     * @return self
     */
    public function setContact(ContactInterface $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return ContactInterface
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
        return null !== $this->getContact() ?
            $this->getContact()->getFullName() : $this->getUsername();
    }
}
