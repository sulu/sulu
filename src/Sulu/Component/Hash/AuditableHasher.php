<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash;

use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Hashes objects by serializing and hashing them using the internal PHP functions.
 */
class AuditableHasher implements HasherInterface
{
    public function hash($object)
    {
        if ($object instanceof AuditableInterface) {
            return \md5(
                ($object->getChanger() ? $object->getChanger()->getId() : '')
                . ($object->getChanged() ? $object->getChanged()->getTimestamp() : '')
            );
        }

        throw new \InvalidArgumentException(
            \sprintf(
                'The AuditableHasher only supports objects implementing the AuditableInterface, "%s" given.',
                \get_class($object)
            )
        );
    }
}
