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

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * UserGroup.
 */
class UserGroup extends ApiEntity
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $id;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var Group
     */
    private $group;

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return UserGroup
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
     * Get Locales as array.
     *
     * @return array
     * @VirtualProperty
     * @SerializedName("locales")
     */
    public function getLocales()
    {
        return json_decode($this->locale);
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
     * Set user.
     *
     * @param UserInterface $user
     *
     * @return UserGroup
     */
    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set group.
     *
     * @param Group $group
     *
     * @return UserGroup
     */
    public function setGroup(Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }
}
