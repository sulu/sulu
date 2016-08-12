<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Exception;

/**
 * Exception is thrown when a Role is created or updated with an already existing name.
 */
class RoleNameAlreadyExistsException extends \Exception
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->message = sprintf('Role "%s" already exists', $name);
        $this->code = 1101;
    }

    /**
     * Returns the non-unique name of the role.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
