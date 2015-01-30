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

use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;

/**
 * UserGroup
 */
class UserGroup extends ApiEntity
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $user;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\Group
     */
    private $group;

    /**
     * Set locale
     *
     * @param string $locale
     * @return UserGroup
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param \Sulu\Component\Security\UserInterface $user
     * @return UserGroup
     */
    public function setUser(\Sulu\Bundle\SecurityBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set group
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $group
     * @return UserGroup
     */
    public function setGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }
}
