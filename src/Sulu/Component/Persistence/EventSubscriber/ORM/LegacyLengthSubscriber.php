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
use Sulu\Article\Domain\Model\ArticleDimensionContentAdditionalWebspace;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentNavigationContext;
use Sulu\Route\Domain\Model\Route;

/**
 * Restores legacy field lengths for installations that still run the older database schema.
 *
 * @internal
 */
class LegacyLengthSubscriber
{
    /**
     * @var array<class-string, array<string, int>>
     */
    private const LEGACY_FIELD_LENGTHS = [
        Route::class => ['webspace' => 32],
        Page::class => ['webspaceKey' => 64],
        PageDimensionContentNavigationContext::class => ['navigationContext' => 64],
        ArticleDimensionContentAdditionalWebspace::class => ['additionalWebspace' => 64],
    ];

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();
        $className = $metadata->getName();

        $fieldLengths = self::LEGACY_FIELD_LENGTHS[$className] ?? [];

        if ($metadata->hasField('templateKey')) {
            $fieldLengths['templateKey'] = 64;
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
