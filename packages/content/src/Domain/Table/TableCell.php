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
 * A single body cell of the `table` content type.
 *
 * Besides the textual content a cell carries simple inline formatting flags
 * (bold/italic/underline) so each cell can be styled individually. The stored
 * JSON for a cell is an object:
 *
 *  { "text": "Foo", "bold": true, "italic": false, "underline": false }
 *
 * Legacy values where a cell was a plain string are still accepted and treated
 * as unformatted text, keeping the persisted data forward- and backward
 * compatible.
 */
final readonly class TableCell
{
    public function __construct(
        public string $text,
        public bool $bold = false,
        public bool $italic = false,
        public bool $underline = false,
    ) {
    }

    public static function fromRaw(mixed $raw): self
    {
        if (\is_array($raw)) {
            return new self(
                self::toText($raw['text'] ?? ''),
                (bool) ($raw['bold'] ?? false),
                (bool) ($raw['italic'] ?? false),
                (bool) ($raw['underline'] ?? false),
            );
        }

        return new self(self::toText($raw));
    }

    public static function blank(): self
    {
        return new self('');
    }

    public function isBlank(): bool
    {
        return '' === $this->text;
    }

    /**
     * @return array{text: string, bold: bool, italic: bool, underline: bool}
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'bold' => $this->bold,
            'italic' => $this->italic,
            'underline' => $this->underline,
        ];
    }

    /**
     * Space separated CSS classes for the active formatting, ready to drop into
     * a template. Returns an empty string when the cell has no formatting.
     */
    public function classes(): string
    {
        $classes = [];

        if ($this->bold) {
            $classes[] = 'font-bold';
        }

        if ($this->italic) {
            $classes[] = 'italic';
        }

        if ($this->underline) {
            $classes[] = 'underline';
        }

        return \implode(' ', $classes);
    }

    private static function toText(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }
}
