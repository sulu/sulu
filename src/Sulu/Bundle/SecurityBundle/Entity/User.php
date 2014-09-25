<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\SecurityBundle\Entity;

use Serializable;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Security\UserInterface;

/**
 * User
 *
 * @ExclusionPolicy("all")
 */
class User extends ApiEntity implements UserInterface, Serializable
{
    /**
     * @var string
     * @Expose
     */
    private $username;

    /**
     * @var string
     * @Expose
     */
    private $password;

    /**
     * @var string
     * @Expose
     */
    private $locale;

    /**
     * @var integer
     * @Expose
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     * @Expose
     */
    private $contact;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Expose
     */
    private $userRoles;

    /**
     * @var string
     */
    private $salt;

    /**
     * @var string
     * @Expose
     */
    private $privateKey;

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
     * @var string
     */
    private $apiKey;

    /**
     * @var boolean
     */
    private $locked;

    /**
     * @var boolean
     */
    private $enabled;

    /**
     * @var \DateTime
     */
    private $lastLogin;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userRoles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userSettings = new \Doctrine\Common\Collections\ArrayCollection();

        $this->apiKey = md5(uniqid());
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     * @return string
     */
    public function getFullName()
    {
        return $this->getContact()->getFullName();
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return User
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
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
     * Set contact
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contact
     * @return User
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
     * Add userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     * @return User
     */
    public function addUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles)
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    /**
     * Remove userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     */
    public function removeUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles)
    {
        $this->userRoles->removeElement($userRoles);
    }

    /**
     * Get userRoles
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUserRoles()
    {
        return $this->userRoles;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set privateKey
     *
     * @param string $privateKey
     * @return User
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get privateKey
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Returns just the default symfony user role, so that the user get recognized as authenticated by symfony
     *
     * @return array The user roles
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * Removes the password of the user
     *
     * @return void
     */
    public function eraseCredentials()
    {
    }

    /**
     * Serializes the user just with the id, as it is enough
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string The string representation of the object or null
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->id
            )
        );
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized The string representation of the object.
     * @return void
     */
    public function unserialize($serialized)
    {
        list ($this->id) = unserialize($serialized);
    }

    /**
     * Add userGroups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups
     * @return User
     */
    public function addUserGroup(\Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups)
    {
        $this->userGroups[] = $userGroups;

        return $this;
    }

    /**
     * Remove userGroups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups
     */
    public function removeUserGroup(\Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups)
    {
        $this->userGroups->removeElement($userGroups);
    }

    /**
     * Get userGroups
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * Add userSettings
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserSetting $userSettings
     * @return User
     */
    public function addUserSetting(\Sulu\Bundle\SecurityBundle\Entity\UserSetting $userSettings)
    {
        $this->userSettings[] = $userSettings;

        return $this;
    }

    /**
     * Remove userSettings
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserSetting $userSettings
     */
    public function removeUserSetting(\Sulu\Bundle\SecurityBundle\Entity\UserSetting $userSettings)
    {
        $this->userSettings->removeElement($userSettings);
    }

    /**
     * Get userSettings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserSettings()
    {
        return $this->userSettings;
    }

    /**
     * Set apiKey
     *
     * @param string $apiKey
     * @return User
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     * @return User
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
    
        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean 
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    
        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    
        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime 
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }
}
