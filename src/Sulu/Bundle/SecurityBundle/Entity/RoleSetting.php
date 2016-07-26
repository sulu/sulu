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

use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleSettingInterface;

/**
 * RoleSetting.
 */
class RoleSetting implements RoleSettingInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $value;

    /**
     * @var RoleInterface
     */
    private $role;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setRole(RoleInterface $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }
}
