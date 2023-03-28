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

declare(strict_types=1);

namespace Sulu\Component\PHPCR\PropertyParser;

class Property
{
    public function __construct(
        private string $path,
        private mixed $value,
    ) {
    }

    /**
     * Returns the entire path of the current property (relative to the node).
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
