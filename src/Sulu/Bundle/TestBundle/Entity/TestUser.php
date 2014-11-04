<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Entity;

use Sulu\Component\Security\UserInterface;
use Symfony\Component\Security\Core\Role\Role;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;

/**
 * TestUser
 */
class TestUser implements UserInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var TestContact
     */
    private $contact;

    /**
     * Set username
     *
     * @param string $username
     * @return TestUser
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
     * @return TestUser
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
     * @param $locale
     * @return TestUser
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
     * Get fullname
     *
     * @VirtualProperty
     * @SerializedName("fullName")
     * @return string
     */
    public function getFullName()
    {
        return $this->getContact()->getFullName();
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
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {

    }

    /**
     * Set contact
     *
     * @param \Sulu\Bundle\TestBundle\Entity\TestContact $contact
     * @return TestUser
     */
    public function setContact(\Sulu\Bundle\TestBundle\Entity\TestContact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Sulu\Bundle\TestBundle\Entity\TestContact
     */
    public function getContact()
    {
        return $this->contact;
    }
}
