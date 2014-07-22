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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * UserRole
 * @ExclusionPolicy("all");
 */
class UserRole extends ApiEntity
{
    /**
     * @var integer
     * @Expose
     */
    private $id;

    /**
     * @var string
     * @Expose
     */
    private $locale;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $user;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\RoleInterface
     * @Expose
     */
    private $role;

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
     * Set locale
     *
     * @param string $locale
     * @return UserRole
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
     * Get Locales as array
     * @return array
     * @VirtualProperty
     * @SerializedName("locales")
     */
    public function getLocales()
    {
        return json_decode($this->locale);
    }

    /**
     * Set user
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $user
     * @return UserRole
     */
    public function setUser(\Sulu\Bundle\SecurityBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set role
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\RoleInterface $role
     * @return UserRole
     */
    public function setRole(\Sulu\Bundle\SecurityBundle\Entity\RoleInterface $role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }
}
