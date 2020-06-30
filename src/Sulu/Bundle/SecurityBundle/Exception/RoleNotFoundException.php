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

class RoleNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $roleName;

    public function __construct(string $roleName)
    {
        parent::__construct(sprintf('Role with name %s could not be found.', $roleName));

        $this->roleName = $roleName;
    }

    /**
     * @return string
     */
    public function getRoleName(): string
    {
        return $this->roleName;
    }
}
