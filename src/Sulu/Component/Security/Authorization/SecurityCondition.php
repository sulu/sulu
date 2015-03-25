<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * The class which describes the necessary permissions to access a certain element
 * @package Sulu\Bundle\SecurityBundle\Security
 */
class SecurityCondition
{
    /**
     * The string representation of the security context
     * @var string
     */
    private $securityContext;

    /**
     * The identity of the object which will be accessed, null if not a certain element but an area is accessed
     * @var ObjectIdentityInterface
     */
    private $objectIdentity;

    /**
     * @param string $securityContext The string representation of the security context
     * @param ObjectIdentityInterface $objectIdentity The identity of the object to be accessed
     */
    public function __construct($securityContext, ObjectIdentityInterface $objectIdentity = null)
    {
        $this->securityContext = $securityContext;
        $this->objectIdentity = $objectIdentity;
    }

    /**
     * Returns the string representation of a security context
     * @return string
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * Returns the identity of the object to access or null, if not a certain object is accessed
     * @return ObjectIdentityInterface
     */
    public function getObjectIdentity()
    {
        return $this->objectIdentity;
    }
} 
