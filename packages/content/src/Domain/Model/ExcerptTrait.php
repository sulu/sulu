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
        $excerpt = $this->excerptData['excerpt'] ?? null;
        if (!\is_array($excerpt)) {
            return null;
        }

        $value = $excerpt['title'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setExcerptTitle(?string $excerptTitle): void
    {
        if (!isset($this->excerptData['excerpt']) || !\is_array($this->excerptData['excerpt'])) {
            $this->excerptData['excerpt'] = [];
        }
        $this->excerptData['excerpt']['title'] = $excerptTitle;
    }

    public function getExcerptDescription(): ?string
    {
        $excerpt = $this->excerptData['excerpt'] ?? null;
        if (!\is_array($excerpt)) {
            return null;
        }

        $value = $excerpt['description'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setExcerptDescription(?string $excerptDescription): void
    {
        if (!isset($this->excerptData['excerpt']) || !\is_array($this->excerptData['excerpt'])) {
            $this->excerptData['excerpt'] = [];
        }
        $this->excerptData['excerpt']['description'] = $excerptDescription;
    }

    public function getExcerptMore(): ?string
    {
        $excerpt = $this->excerptData['excerpt'] ?? null;
        if (!\is_array($excerpt)) {
            return null;
        }

        $value = $excerpt['more'] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function setExcerptMore(?string $excerptMore): void
    {
        if (!isset($this->excerptData['excerpt']) || !\is_array($this->excerptData['excerpt'])) {
            $this->excerptData['excerpt'] = [];
        }
        $this->excerptData['excerpt']['more'] = $excerptMore;
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptImage(): ?array
    {
        $excerpt = $this->excerptData['excerpt'] ?? null;
        if (!\is_array($excerpt)) {
            return null;
        }

        $value = $excerpt['image'] ?? null;

        /** @var array{id: int}|null */
        return \is_array($value) ? $value : null;
    }

    /**
     * @param array{id?: int}|null $excerptImage
     */
    public function setExcerptImage(?array $excerptImage): void
    {
        if (!isset($this->excerptData['excerpt']) || !\is_array($this->excerptData['excerpt'])) {
            $this->excerptData['excerpt'] = [];
        }
        $this->excerptData['excerpt']['image'] = $excerptImage;
    }

    /**
     * @return array{id: int}|null
     */
    public function getExcerptIcon(): ?array
    {
        $excerpt = $this->excerptData['excerpt'] ?? null;
        if (!\is_array($excerpt)) {
            return null;
        }

        $value = $excerpt['icon'] ?? null;

        /** @var array{id: int}|null */
        return \is_array($value) ? $value : null;
    }

    /**
     * @param array{id?: int}|null $excerptIcon
     */
    public function setExcerptIcon(?array $excerptIcon): void
    {
        if (!isset($this->excerptData['excerpt']) || !\is_array($this->excerptData['excerpt'])) {
            $this->excerptData['excerpt'] = [];
        }
        $this->excerptData['excerpt']['icon'] = $excerptIcon;
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
