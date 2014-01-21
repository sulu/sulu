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

/**
 * UserSetting
 */
class UserSetting
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $key;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $user;

    /**
     * Set value
     *
     * @param string $value
     * @return UserSetting
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set key
     *
     * @param string $key
     * @return UserSetting
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set user
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $user
     * @return UserSetting
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
}
