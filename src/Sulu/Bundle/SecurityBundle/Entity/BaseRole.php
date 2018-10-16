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

use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Component\Security\Core\Role\Role;

/**
 * BaseRole.
 */
abstract class BaseRole extends Role implements RoleInterface
{
    use AuditableTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $system;

    /**
     * @var int
     */
    private $id;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
