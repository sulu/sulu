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

class HiddenBlockVisitor implements BlockVisitorInterface
{
    public function visit(array $block): ?array
    {
        $blockPropertyTypeSettings = $block['settings'] ?? [];

        return \is_array($blockPropertyTypeSettings) && !empty($blockPropertyTypeSettings['hidden'])
            ? null
            : $block;
    }
}
