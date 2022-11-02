<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Application;

use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;

class SecuredEntity implements SecuredEntityInterface
{
    public function getId(): void
    {
    }

    public function getSecurityContext()
    {
        return 'sulu.security.secured_entity';
    }
}
