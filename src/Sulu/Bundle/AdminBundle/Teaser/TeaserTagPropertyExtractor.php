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

namespace Sulu\Bundle\AdminBundle\Teaser;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class TeaserTagPropertyExtractor
{
    public const TAG_TEASER_DESCRIPTION = 'sulu.teaser.description';
    public const TAG_TEASER_MEDIA = 'sulu.teaser.media';

    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
    ) {
    }

    /**
     * @param array<string, mixed> $templateData
     */
    public function extractDescription(string $templateType, string $templateKey, string $locale, array $templateData): ?string
    {
        $propertyName = $this->findPropertyNameByTag($templateType, $templateKey, $locale, self::TAG_TEASER_DESCRIPTION);
        if (null === $propertyName) {
            return null;
        }

        $value = $templateData[$propertyName] ?? null;
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        return $value;
    }

    /**
     * Extracts media ID from a template property tagged with sulu.teaser.media.
     *
     * @param array<string, mixed> $templateData
     */
    public function extractMediaId(string $templateType, string $templateKey, string $locale, array $templateData): ?int
    {
        $propertyName = $this->findPropertyNameByTag($templateType, $templateKey, $locale, self::TAG_TEASER_MEDIA);
        if (null === $propertyName) {
            return null;
        }

        $value = $templateData[$propertyName] ?? null;

        // Handle single_media_selection format: ['id' => int]
        if (\is_array($value) && isset($value['id']) && \is_numeric($value['id'])) {
            return (int) $value['id'];
        }

        // Handle media_selection format: ['ids' => [...]]
        if (\is_array($value) && isset($value['ids']) && \is_array($value['ids'])) {
            $firstId = \reset($value['ids']);
            if (\is_numeric($firstId)) {
                return (int) $firstId;
            }
        }

        return null;
    }

    private function findPropertyNameByTag(string $templateType, string $templateKey, string $locale, string $tagName): ?string
    {
        $formMetadata = $this->getFormMetadata($templateType, $templateKey, $locale);
        if (null === $formMetadata) {
            return null;
        }

        foreach ($formMetadata->getFlatFieldMetadata() as $field) {
            foreach ($field->getTags() as $tag) {
                if ($tag->getName() === $tagName) {
                    return $field->getName();
                }
            }
        }

        return null;
    }

    private function getFormMetadata(string $templateType, string $templateKey, string $locale): ?FormMetadata
    {
        $typedFormMetadata = $this->formMetadataProvider->getMetadata($templateType, $locale, []);

        return $typedFormMetadata->getForms()[$templateKey] ?? null;
    }
}
