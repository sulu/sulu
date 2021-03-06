<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

interface PermissionInheritanceInterface
{
    /**
     * @return string|int
     */
    public function getId();

    /**
     * @return string|int|null
     */
    public function getParentId();
}
