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

namespace Sulu\Content\Domain\Table;

/**
 * Template-facing presentation view for the `table` content type.
 *
 * Wraps the normalized {@see TableData} and exposes convenience accessors so
 * Twig templates stay free of preparation logic.
 */
final readonly class TableView
{
    /**
     * @param list<string>            $head
     * @param list<list<TableCell>>   $rows
     * @param array<string, mixed>    $options
     */
    private function __construct(
        public array $head,
        public array $rows,
        public array $options,
    ) {
    }

    public static function fromData(TableData $data): self
    {
        return new self($data->head, $data->rows, $data->options);
    }

    public function isEmpty(): bool
    {
        return [] === $this->rows && '' === \implode('', $this->head);
    }

    public function hasHead(): bool
    {
        return '' !== \implode('', $this->head);
    }

    public function caption(): ?string
    {
        $caption = $this->options['caption'] ?? null;

        return \is_string($caption) && '' !== $caption ? $caption : null;
    }

    /**
     * CSS alignment class for a column, always with a sensible default.
     */
    public function alignClass(int $columnIndex): string
    {
        $align = $this->options['columns'][$columnIndex]['align'] ?? 'left';

        return match ($align) {
            'right' => 'text-right',
            'center' => 'text-center',
            default => 'text-left',
        };
    }
}
