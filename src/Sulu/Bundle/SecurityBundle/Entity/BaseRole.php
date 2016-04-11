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
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Role\Role;

/**
 * BaseRole.
 */
abstract class BaseRole extends Role implements RoleInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $system;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var int
     */
    private $id;

    /**
     * @var UserInterface
     */
    private $changer;

    /**
     * @var UserInterface
     */
    private $creator;

    /**
     * @var SecurityType
     */
    private $securityType;

    public function __construct()
    {
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return BaseRole
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
     * {@inheritdoc}
     */
    public function getRole()
    {
        return 'ROLE_SULU_' . strtoupper($this->name);
    }

    /**
     * Set system.
     *
     * @param string $system
     *
     * @return BaseRole
     */
    public function setSystem($system)
    {
        $this->system = $system;

        return $this;
    }

    /**
     * Get system.
     *
     * @return string
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
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
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return BaseRole
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return BaseRole
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set securityType.
     *
     * @param SecurityType $securityType
     *
     * @return BaseRole
     */
    public function setSecurityType(SecurityType $securityType = null)
    {
        $this->securityType = $securityType;

        return $this;
    }

    /**
     * Get securityType.
     *
     * @return SecurityType
     */
    public function getSecurityType()
    {
        return $this->securityType;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'ROLE_SULU_' . strtoupper($this->getName());
    }
}
