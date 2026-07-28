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

namespace Sulu\Content\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Walks the templateData JSON recursively. Whenever a value is a non-empty list of
 * strings and every string resolves to a tag name in `ta_tags`, the array is replaced
 * with the corresponding ids. Anything else is left untouched. Already-migrated rows
 * (integer arrays) are no-ops.
 */
abstract class AbstractTagNameToIdMigration extends AbstractMigration
{
    private const TAGS_TABLE = 'ta_tags';

    /**
     * @var array<string, int> name => id
     */
    private array $tagMap = [];

    abstract protected function getTable(): string;

    public function getDescription(): string
    {
        return \sprintf('Convert tag-name values in %s to tag IDs', $this->getTable());
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TAGS_TABLE) || !$schema->hasTable($this->getTable())) {
            return;
        }

        $this->tagMap = $this->buildTagMap();
        if ([] === $this->tagMap) {
            return;
        }

        $this->convertRows();
    }

    public function down(Schema $schema): void
    {
        // This migration is not reversible: the original tag names cannot be
        // restored from the ids. Intentionally a no-op instead of throwing.
    }

    /**
     * @return array<string, int>
     */
    private function buildTagMap(): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'name')
            ->from(self::TAGS_TABLE)
            ->executeQuery()
            ->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $id = $row['id'];
            $name = $row['name'];
            if (\is_string($name) && (\is_int($id) || \is_string($id))) {
                $map[$name] = (int) $id;
            }
        }

        return $map;
    }

    private function convertRows(): void
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'templateData AS template_data')
            ->from($this->getTable())
            ->executeQuery()
            ->iterateAssociative();

        foreach ($rows as $row) {
            $raw = $row['template_data'] ?? null;
            if (!\is_string($raw) || '' === $raw) {
                continue;
            }
            $decoded = \json_decode($raw, true);
            if (!\is_array($decoded)) {
                continue;
            }

            $converted = $this->convert($decoded);
            if (null === $converted) {
                continue;
            }

            $this->connection->createQueryBuilder()
                ->update($this->getTable())
                ->set('templateData', ':template_data')
                ->where('id = :id')
                ->setParameter('template_data', $converted, Types::JSON)
                ->setParameter('id', $row['id'])
                ->executeStatement();
        }
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>|null null when nothing changed
     */
    private function convert(array $data): ?array
    {
        $changed = false;
        foreach ($data as $key => $value) {
            if (!\is_array($value)) {
                continue;
            }

            if ($this->isResolvableTagNameList($value)) {
                $ids = [];
                foreach ($value as $name) {
                    \assert(\is_string($name));
                    $ids[] = $this->tagMap[$name];
                }
                $data[$key] = $ids;
                $changed = true;
                continue;
            }

            $convertedChild = $this->convert($value);
            if (null !== $convertedChild) {
                $data[$key] = $convertedChild;
                $changed = true;
            }
        }

        return $changed ? $data : null;
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function isResolvableTagNameList(array $value): bool
    {
        if ([] === $value) {
            return false;
        }
        if (!\array_is_list($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (!\is_string($item) || !isset($this->tagMap[$item])) {
                return false;
            }
        }

        return true;
    }
}
