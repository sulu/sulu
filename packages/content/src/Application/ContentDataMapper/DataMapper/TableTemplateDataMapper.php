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
use Sulu\Content\Domain\Table\TableData;

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

        $tableProperties = $this->resolveTableProperties(
            $localizedDimensionContent::getTemplateType(),
            $localizedDimensionContent->getLocale(),
            $template,
        );

        if ([] === $tableProperties) {
            return;
        }

        foreach ([$unlocalizedDimensionContent, $localizedDimensionContent] as $dimensionContent) {
            $this->normalize($dimensionContent, $tableProperties);
        }
    }

    /**
     * @param list<string> $tableProperties
     */
    private function normalize(TemplateInterface $dimensionContent, array $tableProperties): void
    {
        $templateData = $dimensionContent->getTemplateData();
        $changed = false;

        foreach ($tableProperties as $name) {
            if (!\array_key_exists($name, $templateData)) {
                continue;
            }

            $templateData[$name] = TableData::fromArray($templateData[$name])->toArray();
            $changed = true;
        }

        if ($changed) {
            $dimensionContent->setTemplateData($templateData);
        }
    }

    /**
     * @return list<string>
     */
    private function resolveTableProperties(string $type, ?string $locale, string $template): array
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

        $tableProperties = [];
        foreach ($metadata->getFlatFieldMetadata() as $property) {
            if (self::TABLE_TYPE !== $property->getType()) {
                continue;
            }

            // Only handle top-level properties (block paths use a "/" separator).
            $name = \explode('/', $property->getName(), 2)[0];
            $tableProperties[$name] = true;
        }

        return \array_keys($tableProperties);
    }
}
