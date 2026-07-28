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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Table\TableTemplateDataNormalizer;

/**
 * Normalizes every `table` property of the active template after the
 * TemplateDataMapper has written the raw values, so the persisted JSON always
 * follows the {@see TableData} contract (rectangular, no empty rows).
 */
final readonly class TableTemplateDataMapper implements DataMapperInterface
{
    private const TABLE_TYPE = 'table';

    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
        private TableTemplateDataNormalizer $tableTemplateDataNormalizer,
    ) {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof TemplateInterface
            || !$unlocalizedDimensionContent instanceof TemplateInterface
        ) {
            return;
        }

        $template = $localizedDimensionContent->getTemplateKey();
        if (null === $template) {
            return;
        }

        $tablePropertyPaths = $this->resolveTablePropertyPaths(
            $localizedDimensionContent::getTemplateType(),
            $localizedDimensionContent->getLocale(),
            $template,
        );

        if ([] === $tablePropertyPaths) {
            return;
        }

        foreach ([$unlocalizedDimensionContent, $localizedDimensionContent] as $dimensionContent) {
            $this->normalize($dimensionContent, $tablePropertyPaths);
        }
    }

    /**
     * @param list<string> $tablePropertyPaths
     */
    private function normalize(TemplateInterface $dimensionContent, array $tablePropertyPaths): void
    {
        $templateData = $dimensionContent->getTemplateData();

        $dimensionContent->setTemplateData(
            $this->tableTemplateDataNormalizer->normalize($templateData, $tablePropertyPaths),
        );
    }

    /**
     * @return list<string>
     */
    private function resolveTablePropertyPaths(string $type, ?string $locale, string $template): array
    {
        if (null === $locale) {
            return [];
        }

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
