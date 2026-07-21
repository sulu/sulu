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

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Shrinks field lengths back down to the pre-3.x column widths for installations whose
 * database schema has not yet been migrated to the new, longer default column lengths.
 *
 * @internal
 */
class LegacyLengthSubscriber
{
    /**
     * @var array<string, array<string, int>>
     */
    private const LEGACY_FIELD_LENGTHS = [
        'Sulu\Route\Domain\Model\Route' => ['webspace' => 31, 'slug' => 144],
        'Sulu\Page\Domain\Model\Page' => ['webspaceKey' => 31],
        'Sulu\Page\Domain\Model\PageDimensionContentNavigationContext' => ['navigationContext' => 31],
        'Sulu\Article\Domain\Model\ArticleDimensionContentAdditionalWebspace' => ['additionalWebspace' => 31],
    ];

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();
        $className = $metadata->getName();

        $fieldLengths = self::LEGACY_FIELD_LENGTHS[$className] ?? [];

        if ($metadata->hasField('templateKey')) {
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
