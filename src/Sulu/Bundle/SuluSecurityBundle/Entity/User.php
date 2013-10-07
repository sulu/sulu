<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\VirtualProperty;
use Serializable;
use Symfony\Bridge\Doctrine\Tests\Security\User\EntityUserProviderTest;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User
 *
 * @ExclusionPolicy("all")
 */
class User implements UserInterface, Serializable
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
     * Constructor
     */
    public function __construct()
    {
        $this->userRoles = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return array
     * @VirtualProperty
     * @Type("array")
     */
    public function getUserRoles()
    {
        return array_values($this->userRoles->toArray());
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
        $this->setPassword('');
    }

    /**
     * Serializes the user just with the id, as it is enough
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
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
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        list ($this->id) = unserialize($serialized);
    }
}
