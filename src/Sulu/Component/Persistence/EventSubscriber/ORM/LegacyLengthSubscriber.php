<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Sulu\Content\Domain\Model\TemplateInterface;

/**
 * Shrinks field lengths back down to the pre-3.x column widths for installations whose
 * database schema has not yet been migrated to the new, longer default column lengths.
 *
 * @internal
 */
class LegacyLengthSubscriber
{
    /**
     * MySQL's combined-index byte limit (767 bytes under `innodb_large_prefix=false`) forces a
     * shorter slug length than Postgres, which has no equivalent per-column index prefix limit.
     */
    private const LEGACY_SLUG_LENGTH_MYSQL = 144;

    private const LEGACY_SLUG_LENGTH_POSTGRES = 208;

    /**
     * @var array<string, array<string, int>>
     */
    private const LEGACY_FIELD_LENGTHS = [
        'Sulu\Route\Domain\Model\Route' => ['webspace' => 31],
        'Sulu\Page\Domain\Model\Page' => ['webspaceKey' => 31],
        'Sulu\Page\Domain\Model\PageDimensionContentNavigationContext' => ['navigationContext' => 31],
        'Sulu\Article\Domain\Model\ArticleDimensionContentAdditionalWebspace' => ['additionalWebspace' => 31],
    ];

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();
        $className = $metadata->getName();

        $fieldLengths = self::LEGACY_FIELD_LENGTHS[$className] ?? [];

        if ('Sulu\Route\Domain\Model\Route' === $className) {
            $platform = $event->getEntityManager()->getConnection()->getDatabasePlatform();
            $fieldLengths['slug'] = $platform instanceof PostgreSQLPlatform
                ? self::LEGACY_SLUG_LENGTH_POSTGRES
                : self::LEGACY_SLUG_LENGTH_MYSQL;
        }

        if (\is_a($className, TemplateInterface::class, true) && $metadata->hasField('templateKey')) {
            $fieldLengths['templateKey'] = 31;
        }

        foreach ($fieldLengths as $fieldName => $length) {
            if (!$metadata->hasField($fieldName)) {
                continue;
            }

            $fieldMapping = $metadata->fieldMappings[$fieldName];

            // @phpstan-ignore-next-line
            if (\is_array($fieldMapping)) {
                // Doctrine ORM 2
                // @phpstan-ignore-next-line
                $metadata->fieldMappings[$fieldName]['length'] = $length;
            } else {
                // Doctrine ORM 3
                $fieldMapping->length = $length;
            }
        }
    }
}
