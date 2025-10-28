<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Application\BlockIdGenerator;

final class BlockIdGenerator implements BlockIdGeneratorInterface
{
    public function generateId(): string
    {
        // Use xxh32 hash with uniqid for short, unique IDs
        // uniqid with more_entropy=true provides microsecond precision
        // xxh32 produces 8-character hexadecimal strings
        return \hash('xxh32', \uniqid('', true));
    }
}
