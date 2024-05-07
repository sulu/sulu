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

namespace Sulu\Bundle\PageBundle\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Extension\AbstractExtension;

class TestExtension extends AbstractExtension
{
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode): void
    {
    }

    public function load(NodeInterface $node, $webspaceKey, $languageCode): void
    {
    }
}
