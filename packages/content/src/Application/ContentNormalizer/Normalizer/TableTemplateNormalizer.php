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

namespace Sulu\Content\Application\ContentNormalizer\Normalizer;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Table\TableTemplateDataNormalizer;

/**
 * Ensures every `table` property in template data is returned to the Admin UI in
 * the {@see TableData} JSON shape (`version`, `head`, `body`) after save/load.
 */
final readonly class TableTemplateNormalizer implements NormalizerInterface
{
    private const TABLE_TYPE = 'table';

    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
        private TableTemplateDataNormalizer $tableTemplateDataNormalizer,
    ) {
    }

    public function enhance(object $object, array $normalizedData): array
    {
        if (!$object instanceof TemplateInterface) {
            return $normalizedData;
        }

        $template = $object->getTemplateKey();
        $locale = $object->getLocale();

        if (null === $template || null === $locale) {
            return $normalizedData;
        }

        if (!\is_array($normalizedData['templateData'] ?? null)) {
            return $normalizedData;
        }

        $tablePropertyPaths = $this->resolveTablePropertyPaths(
            $object::getTemplateType(),
            $locale,
            $template,
        );

        if ([] === $tablePropertyPaths) {
            return $normalizedData;
        }

        /** @var array<string, mixed> $templateData */
        $templateData = $normalizedData['templateData'];
        $normalizedData['templateData'] = $this->tableTemplateDataNormalizer->normalize(
            $templateData,
            $tablePropertyPaths,
        );

        return $normalizedData;
    }

    public function getIgnoredAttributes(object $object): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    private function resolveTablePropertyPaths(string $type, string $locale, string $template): array
    {
        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($type, $locale, []);

        if (!$typedMetadata instanceof TypedFormMetadata) {
            return [];
        }

        $metadata = $typedMetadata->getForms()[$template] ?? null;

        if (!$metadata instanceof FormMetadata) {
            return [];
        }

        $tablePropertyPaths = [];
        foreach ($metadata->getFlatFieldMetadata() as $property) {
            if (self::TABLE_TYPE !== $property->getType()) {
                continue;
            }

            $tablePropertyPaths[] = $property->getName();
        }

        return $tablePropertyPaths;
    }
}
