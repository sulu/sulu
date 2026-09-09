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
 * Central data contract for the `table` content type.
 *
 * The persisted JSON is intentionally forward-compatible:
 *  - `version`: schema version, allows future migrations of stored values.
 *  - `head`: the header row as a list of plain strings.
 *  - `body`: the data rows as a rectangular matrix of {@see TableCell} objects.
 *  - `options`: open extension bag for future global table settings
 *    (e.g. caption, striped, per-column alignment under `options.columns`).
 *
 * Backward compatibility: version 1 stored body cells as plain strings. Such
 * values are transparently upgraded to unformatted {@see TableCell} objects by
 * {@see TableCell::fromRaw()}, so no explicit migration branch is required.
 */
final readonly class TableData
{
    /**
     * Current schema version of the stored JSON. Bump this when the structure
     * changes in a non-backward-compatible way and add a migration in fromArray().
     *
     * v2: body cells are objects ({text, bold, italic, underline}) instead of
     * plain strings.
     */
    public const VERSION = 2;

    /**
     * @param list<string>          $head
     * @param list<list<TableCell>> $rows
     * @param array<string, mixed>  $options
     */
    private function __construct(
        public int $version,
        public array $head,
        public array $rows,
        public array $options,
    ) {
    }

    public static function fromArray(mixed $raw): self
    {
        if (!\is_array($raw)) {
            return new self(self::VERSION, [], [], []);
        }

        $options = \is_array($raw['options'] ?? null) ? $raw['options'] : [];

        $rawHead = \is_array($raw['head'] ?? null) ? \array_values($raw['head']) : [];
        $rawBody = \is_array($raw['body'] ?? null) ? \array_values($raw['body']) : [];

        $head = \array_map(self::toHeadCell(...), $rawHead);
        $rows = \array_map(
            static fn (mixed $row): array => \is_array($row)
                ? \array_map(TableCell::fromRaw(...), \array_values($row))
                : [],
            $rawBody,
        );

        $columns = \max(\count($head), 0, ...\array_map('\count', $rows));

        if (0 === $columns) {
            return new self(self::VERSION, [], [], $options);
        }

        $head = \array_pad($head, $columns, '');
        $rows = \array_values(\array_map(
            static fn (array $row): array => \array_pad($row, $columns, TableCell::blank()),
            $rows,
        ));

        // Drop completely empty rows to keep the stored data clean.
        $rows = \array_values(\array_filter(
            $rows,
            static fn (array $row): bool => self::rowHasContent($row),
        ));

        return new self(self::VERSION, $head, $rows, $options);
    }

    public function isEmpty(): bool
    {
        return [] === $this->rows && '' === \implode('', $this->head);
    }

    /**
     * @return array{version: int, head: list<string>, body: list<list<array{text: string, bold: bool, italic: bool, underline: bool}>>, options?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $data = [
            'version' => $this->version,
            'head' => $this->head,
            'body' => \array_map(
                static fn (array $row): array => \array_map(
                    static fn (TableCell $cell): array => $cell->toArray(),
                    $row,
                ),
                $this->rows,
            ),
        ];

        if ([] !== $this->options) {
            $data['options'] = $this->options;
        }

        return $data;
    }

    /**
     * @param list<TableCell> $row
     */
    private static function rowHasContent(array $row): bool
    {
        foreach ($row as $cell) {
            if (!$cell->isBlank()) {
                return true;
            }
        }

        return false;
    }

    private static function toHeadCell(mixed $cell): string
    {
        return \is_scalar($cell) ? (string) $cell : '';
    }
}
