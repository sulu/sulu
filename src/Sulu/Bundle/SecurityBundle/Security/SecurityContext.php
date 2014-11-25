<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

/**
 * Container class for a string, which described the security context
 * @package Sulu\Bundle\SecurityBundle\Security
 */
class SecurityContext
{
    /**
     * The string representation of the security context
     * @var string
     */
    private $securityContext;

    /**
     * @param string $securityContext The string representation of the security context
     */
    public function __construct($securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Returns the string representation of a security context
     * @return string
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }
} 
