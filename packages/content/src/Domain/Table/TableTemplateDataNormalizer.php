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
 * Normalizes `table` property values inside template data at their metadata path.
 *
 * Top-level properties (e.g. `specs`) are normalized directly. Slash-separated
 * paths (e.g. `contentBlocks/table`) target a field inside each block entry or
 * a nested object — never the container itself.
 */
final class TableTemplateDataNormalizer
{
    /**
     * @param array<string, mixed> $templateData
     * @param list<string>         $propertyPaths
     *
     * @return array<string, mixed>
     */
    public function normalize(array $templateData, array $propertyPaths): array
    {
        foreach ($propertyPaths as $path) {
            $this->normalizeAtPath($templateData, $path);
        }

        return $templateData;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function normalizeAtPath(array &$data, string $path): void
    {
        if (!\str_contains($path, '/')) {
            if (\array_key_exists($path, $data)) {
                $data[$path] = TableData::fromArray($data[$path])->toArray();
            }

            return;
        }

        [$container, $field] = \explode('/', $path, 2);

        if (!\array_key_exists($container, $data) || !\is_array($data[$container])) {
            return;
        }

        if ($this->isList($data[$container])) {
            foreach ($data[$container] as &$item) {
                if (!\is_array($item) || !\array_key_exists($field, $item)) {
                    continue;
                }

                $item[$field] = TableData::fromArray($item[$field])->toArray();
            }
            unset($item);

            return;
        }

        if (\array_key_exists($field, $data[$container])) {
            $data[$container][$field] = TableData::fromArray($data[$container][$field])->toArray();
        }
    }

    /**
     * @param array<mixed> $value
     */
    private function isList(array $value): bool
    {
        return [] === $value || \array_is_list($value);
    }
}
