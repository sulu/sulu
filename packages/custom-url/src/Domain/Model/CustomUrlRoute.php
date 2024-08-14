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

namespace Sulu\CustomUrl\Domain\Model;

use Sulu\Component\Persistence\Model\TimestampableTrait;
use Symfony\Component\Uid\Uuid;

class CustomUrlRoute implements CustomUrlRouteInterface
{
    use TimestampableTrait;

    private string $uuid;

    public function __construct(
        private CustomUrlInterface $customUrl,
        private string $path
    ) {
        $this->uuid = Uuid::v7()->__toString();
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCustomUrl(): CustomUrlInterface
    {
        return $this->customUrl;
    }
}
