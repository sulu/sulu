<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Security\Authentication\RoleInterface;

/**
 * SecurityType.
 *
 * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
 */
class SecurityType
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $id;

    /**
     * @var Collection<int, RoleInterface>
     */
    private $roles;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return SecurityType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Set id.
     *
     * @param int $id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Add roles.
     *
     * @return SecurityType
     */
    public function addRole(RoleInterface $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles.
     *
     * @return void
     */
    public function removeRole(RoleInterface $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles.
     *
     * @return Collection<int, RoleInterface>
     */
    public function getRoles()
    {
        return $this->roles;
    }
}
