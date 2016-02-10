<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the interface for checking a given object against the hash in a request.
 */
interface RequestHashCheckerInterface
{
    /**
     * Returns true if the request contains the correct hash for the given object.
     *
     * @param Request $request
     * @param object $object
     *
     * @return bool
     */
    public function checkHash(Request $request, $object);
}
