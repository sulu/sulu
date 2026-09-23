<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataVisitorInterface;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\ListBuilder\MediaLanguageFilterType;
use Sulu\Bundle\MediaBundle\Media\MediaLanguage\MediaLanguageProvider;

/**
 * @internal This class is internal. Create a separate visitor if you want to manipulate the metadata in your project.
 */
class MediaLanguageListMetadataVisitor implements ListMetadataVisitorInterface
{
    public function __construct(
        private MediaLanguageProvider $mediaLanguageProvider,
    ) {
    }

    public static function getDefaultPriority(): int
    {
        return 50;
    }

    public function visitListMetadata(ListMetadata $listMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        if ('media' !== $key) {
            return;
        }

        if (!isset($listMetadata->getFields()['mediaLanguage'])) {
            return;
        }

        $options = $this->mediaLanguageProvider->getLanguageNames($locale);
        $options[MediaLanguageFilterType::NONE_VALUE] = 'sulu_media.media_language_none';

        $listMetadata->getField('mediaLanguage')->setFilterTypeParameters(['options' => $options]);
    }
}
