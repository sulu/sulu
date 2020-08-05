<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

interface DescendantProviderInterface
{
    /**
     * Return the children for access control inherit update.
     *
     * @param string|int $id
     *
     * @return string[]|int[]
     */
    public function findDescendantIdsById($id);

    /**
     * Will return true if the access control type is supported by this provider.
     */
    public function supportsDescendantType(string $type): bool;
}
