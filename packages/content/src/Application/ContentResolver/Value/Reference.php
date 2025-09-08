<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentResolver\Value;

class Reference
{
    public function __construct(
        private string|int $resourceId,
        private string $resourceKey,
        private string $path = '',
    ) {
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string|int
    {
        return $this->resourceId;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
