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
    private ?string $excerptTitle = null;

    private ?string $excerptDescription = null;

    private ?string $excerptMore = null;

    private ?int $excerptImageId = null;

    private ?int $excerptIconId = null;

    public function getExcerptTitle(): ?string
    {
        return $this->excerptTitle;
    }

    public function setExcerptTitle(?string $excerptTitle): void
    {
        $this->excerptTitle = $excerptTitle;
    }

    public function getExcerptDescription(): ?string
    {
        return $this->excerptDescription;
    }

    public function setExcerptDescription(?string $excerptDescription): void
    {
        $this->excerptDescription = $excerptDescription;
    }

    public function getExcerptMore(): ?string
    {
        return $this->excerptMore;
    }

    public function setExcerptMore(?string $excerptMore): void
    {
        $this->excerptMore = $excerptMore;
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptImage(): ?array
    {
        if (!$this->excerptImageId) {
            return null;
        }

        return [
            'id' => $this->excerptImageId,
        ];
    }

    /**
     * @param array{id?: int}|null $excerptImage
     */
    public function setExcerptImage(?array $excerptImage): void
    {
        $this->excerptImageId = $excerptImage['id'] ?? null;
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptIcon(): ?array
    {
        if (!$this->excerptIconId) {
            return null;
        }

        return [
            'id' => $this->excerptIconId,
        ];
    }

    /**
     * @param array{id?: int}|null $excerptIcon
     */
    public function setExcerptIcon(?array $excerptIcon): void
    {
        $this->excerptIconId = $excerptIcon['id'] ?? null;
    }
}
