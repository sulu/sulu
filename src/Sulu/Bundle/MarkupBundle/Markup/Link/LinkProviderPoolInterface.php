<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

interface LinkProviderPoolInterface
{
    public function getProvider(string $name): LinkProviderInterface;

    public function hasProvider(string $name): bool;

    /**
     * @return array<string, LinkConfiguration>
     */
    public function getConfiguration(): array;
}
