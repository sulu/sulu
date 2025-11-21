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

trait ExcerptTrait
{
    /**
     * @var array<string, mixed>
     */
    private array $excerptData = [];

    public function getExcerptTitle(): ?string
    {
        $value = $this->excerptData['title'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function getExcerptDescription(): ?string
    {
        $value = $this->excerptData['description'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function getExcerptMore(): ?string
    {
        $value = $this->excerptData['more'] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptImage(): ?array
    {
        $value = $this->excerptData['image'] ?? null;

        /** @var array{id: int}|null */
        return \is_array($value) ? $value : null;
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptIcon(): ?array
    {
        $value = $this->excerptData['icon'] ?? null;

        /** @var array{id: int}|null */
        return \is_array($value) ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExcerptData(): array
    {
        return $this->excerptData;
    }

    /**
     * @param array<string, mixed> $excerptData
     */
    public function setExcerptData(array $excerptData): void
    {
        $this->excerptData = $excerptData;
    }
}
