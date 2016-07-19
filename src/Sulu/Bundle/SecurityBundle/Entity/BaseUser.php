<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Serializable;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * User.
 *
 * @ExclusionPolicy("all")
 */
abstract class BaseUser extends ApiEntity implements UserInterface, Serializable
{
    /**
     * @var string
     * @Expose
     */
    protected $username;

    /**
     * @var string
     * @Expose
     */
    protected $email;

    /**
     * @var string
     * @Expose
     */
    protected $password;

    /**
     * @var string
     * @Expose
     * @Groups({"frontend", "fullUser"})
     */
    protected $locale;

    /**
     * @var int
     * @Expose
     * @Groups({"frontend", "fullUser"})
     */
    protected $id;

    /**
     * @var string
     */
    protected $salt;

    /**
     * @var string
     * @Expose
     */
    protected $privateKey;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var bool
     * @Expose
     */
    protected $locked = false;

    /**
     * @var bool
     * @Expose
     */
    protected $enabled = true;

    /**
     * @var \DateTime
     */
    protected $lastLogin;

    /**
     * @var string
     */
    protected $confirmationKey;

    /**
     * @var string
     */
    protected $passwordResetToken;

    /**
     * @var \DateTime
     */
    private $passwordResetTokenExpiresAt;

    /**
     * @var int
     */
    private $passwordResetTokenEmailsSent;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiKey = md5(uniqid());
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
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
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->getContact()->getFullName();
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return self
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
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
     * Set salt.
     *
     * @param string $salt
     *
     * @return self
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set privateKey.
     *
     * @param string $privateKey
     *
     * @return self
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get privateKey.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Returns just the default symfony user role, so that the user get recognized as authenticated by symfony.
     *
     * @return array The user roles
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * Removes the password of the user.
     */
    public function eraseCredentials()
    {
    }

    /**
     * Serializes the user just with the id, as it is enough.
     *
     * @link http://php.net/manual/en/serializable.serialize.php
     *
     * @return string The string representation of the object or null
     */
    public function serialize()
    {
        return serialize(
            [
                $this->id,
            ]
        );
    }

    /**
     * Constructs the object.
     *
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized The string representation of the object
     */
    public function unserialize($serialized)
    {
        list($this->id) = unserialize($serialized);
    }

    /**
     * Set apiKey.
     *
     * @param string $apiKey
     *
     * @return self
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set locked.
     *
     * @param bool $locked
     *
     * @return self
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set lastLogin.
     *
     * @param \DateTime $lastLogin
     *
     * @return self
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin.
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set confirmationKey.
     *
     * @param string $confirmationKey
     *
     * @return self
     */
    public function setConfirmationKey($confirmationKey)
    {
        $this->confirmationKey = $confirmationKey;

        return $this;
    }

    /**
     * Get confirmationKey.
     *
     * @return string
     */
    public function getConfirmationKey()
    {
        return $this->confirmationKey;
    }

    /**
     * Set passwordResetToken.
     *
     * @param string $passwordResetToken
     *
     * @return self
     */
    public function setPasswordResetToken($passwordResetToken)
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    /**
     * Get passwordResetToken.
     *
     * @return string
     */
    public function getPasswordResetToken()
    {
        return $this->passwordResetToken;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return BaseUser
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set tokenExpiresAt.
     *
     * @param \DateTime $passwordResetTokenExpiresAt
     *
     * @return BaseUser
     */
    public function setPasswordResetTokenExpiresAt($passwordResetTokenExpiresAt)
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;

        return $this;
    }

    /**
     * Get passwordResetTokenExpiresAt.
     *
     * @return \DateTime
     */
    public function getPasswordResetTokenExpiresAt()
    {
        return $this->passwordResetTokenExpiresAt;
    }

    /**
     * Set passwordResetTokenEmailsSent.
     *
     * @param int $passwordResetTokenEmailsSent
     *
     * @return BaseUser
     */
    public function setPasswordResetTokenEmailsSent($passwordResetTokenEmailsSent)
    {
        $this->passwordResetTokenEmailsSent = $passwordResetTokenEmailsSent;

        return $this;
    }

    /**
     * Get passwordResetTokenEmailsSent.
     *
     * @return int
     */
    public function getPasswordResetTokenEmailsSent()
    {
        return $this->passwordResetTokenEmailsSent;
    }
}
