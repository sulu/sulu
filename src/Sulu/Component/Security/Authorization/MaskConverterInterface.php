<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

/**
 * The interface for mask converters.
 */
interface MaskConverterInterface
{
    /**
     * Converts a permissions array to a bit field.
     *
     * @param array $permissionsData
     *
     * @return int
     */
    public function convertPermissionsToNumber($permissionsData);

    /**
     * Converts the given permissions from the numerical to the array representation.
     *
     * @param int $permissions
     *
     * @return array
     */
    public function convertPermissionsToArray($permissions);
}
