<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

/**
 * The class which describes the necessary permissions to access a certain element.
 */
class SecurityCondition
{
    /**
     * The string representation of the security context.
     *
     * @var string
     */
    private $securityContext;

    /**
     * The type of the object which will be accessed, null if not a certain element but an area is accessed.
     *
     * @var string
     */
    private $objectType;

    /**
     * The id of the object which will be accessed, null if not a certain element but an area is accessed.
     *
     * @var mixed
     */
    private $objectId;

    /**
     * The locale in which the object or context will be accessed.
     *
     * @var string
     */
    private $locale;

    /**
     * The security system for which the permissions should be checked.
     *
     * @var string
     */
    private $system;

    public function __construct($securityContext, $locale = null, $objectType = null, $objectId = null, $system = null)
    {
        $this->securityContext = $securityContext;
        $this->locale = $locale;
        $this->objectType = $objectType;
        $this->objectId = $objectId;
        $this->system = $system;
    }

    /**
     * Returns the string representation of a security context.
     *
     * @return string
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * Returns the type of the object.
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Returns the id of the object.
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Returns the locale in which the security has to be checked.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getSystem()
    {
        return $this->system;
    }
}
