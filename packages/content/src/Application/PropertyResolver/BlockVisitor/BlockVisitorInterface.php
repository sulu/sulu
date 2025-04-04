<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\PropertyResolver\BlockVisitor;

interface BlockVisitorInterface
{
    /**
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>|null return the modified block, if null returned the block is excluded from resolving and rendering
     */
    public function visit(array $block): ?array;
}
