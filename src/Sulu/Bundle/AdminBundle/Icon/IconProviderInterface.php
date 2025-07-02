<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Icon;

/**
 * @experimental This is an experimental feature and may change in future releases.
 */
interface IconProviderInterface
{
    /**
     * @return array<array{id: string, content: string}>
     */
    public function getIcons(string $path): array;
}
