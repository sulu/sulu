<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Exception;

use Sulu\Bundle\SecurityBundle\Security\Exception\SecurityException;

/**
 * Indicates that the user is not valid in the system.
 */
class UserNotInSystemException extends SecurityException
{
    /**
     * @var string
     */
    private $system;

    /**
     * @var string
     */
    private $identifier;

    public function __construct($system, $identifier)
    {
        parent::__construct(
            'User with identifier "' . $identifier . '" does not exists in system "' . $system . '"',
            1009
        );

        $this->system = $system;
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getSystem()
    {
        return $this->system;
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), ['system' => $this->system, 'identifier' => $this->identifier]);
    }
}
