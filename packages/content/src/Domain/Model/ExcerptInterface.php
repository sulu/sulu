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

namespace Sulu\Content\Domain\Model;

interface ExcerptInterface
{
    public function getExcerptTitle(): ?string;

    public function getExcerptMore(): ?string;

    public function getExcerptDescription(): ?string;

    /**
     * @return array{id: int}|null
     */
    public function getExcerptImage(): ?array;

    /**
     * @return array{id: int}|null
     */
    public function getExcerptIcon(): ?array;

    /**
     * @return array<string, mixed>
     */
    public function getExcerptData(): array;

    /**
     * @param array<string, mixed> $excerptData
     */
    public function setExcerptData(array $excerptData): void;
}
