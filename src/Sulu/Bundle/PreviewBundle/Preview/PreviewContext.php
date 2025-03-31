<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

final class PreviewContext
{
    public function __construct(
        private int|string|null $id = null,
        private ?string $locale = null
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }
}
