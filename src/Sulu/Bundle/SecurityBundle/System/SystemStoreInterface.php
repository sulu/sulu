<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\System;

use Sulu\Component\Security\Authentication\RoleInterface;

interface SystemStoreInterface
{
    public function getSystem(): ?string;

    public function setSystem(string $system): void;

    public function getAnonymousRole(): ?RoleInterface;
}
