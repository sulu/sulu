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

use Sulu\Component\Persistence\Model\TimestampableInterface;

interface CustomUrlRouteInterface extends TimestampableInterface
{
    public function getUuid(): string;

    public function setUuid(string $uuid): static;

    public function getPath(): string;

    public function getCustomUrl(): CustomUrlInterface;

    public function isHistory(): bool;

    public function setHistory(bool $history): static;

    public function getTargetRoute(): ?self;

    public function setTargetRoute(?self $targetRoute): static;
}
