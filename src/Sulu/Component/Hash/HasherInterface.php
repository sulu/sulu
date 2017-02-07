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

/**
 * Defines the interface for a class, being responsible for hashing given objects.
 */
interface HasherInterface
{
    /**
     * Hashes the given object to a string.
     *
     * @param object $object
     *
     * @return string
     */
    public function hash($object);
}
