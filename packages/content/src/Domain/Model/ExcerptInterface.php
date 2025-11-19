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

    public function setExcerptTitle(?string $excerptTitle): void;

    public function getExcerptMore(): ?string;

    public function setExcerptMore(?string $excerptMore): void;

    public function getExcerptDescription(): ?string;

    public function setExcerptDescription(?string $excerptDescription): void;

    /**
     * @return array{id: int}|null
     */
    public function getExcerptImage(): ?array;

    /**
     * @param array{id?: int}|null $excerptImage
     */
    public function setExcerptImage(?array $excerptImage): void;

    /**
     * @return array{id: int}|null
     */
    public function getExcerptIcon(): ?array;

    /**
     * @param array{id?: int}|null $excerptIcon
     */
    public function setExcerptIcon(?array $excerptIcon): void;
}
