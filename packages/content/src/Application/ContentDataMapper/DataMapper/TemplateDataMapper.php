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

class TemplateDataMapper implements DataMapperInterface
{
    /**
     * @var MetadataProviderRegistry
     */
    private $metadataProviderRegistry;

    public function __construct(MetadataProviderRegistry $metadataProviderRegistry)
    {
        $this->metadataProviderRegistry = $metadataProviderRegistry;
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof TemplateInterface
            || !$unlocalizedDimensionContent instanceof TemplateInterface
        ) {
            return;
        }

        $type = $localizedDimensionContent::getTemplateType();

        $locale = $localizedDimensionContent->getLocale();

        \assert(\is_string($locale), 'Expected locale to be defined always when using TemplateInterface');

        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($type, $locale, []);

        if (!$typedMetadata instanceof TypedFormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find metadata "%s" of type "%s".', 'form', $type));
        }

        /** @var string $template */
        $template = $data['template'] ?? $typedMetadata->getDefaultType();

        $metadata = $typedMetadata->getForms()[$template] ?? null;

        if (!$metadata instanceof FormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find form metadata "%s" of type "%s".', $template, $type));
        }

        [$unlocalizedData, $localizedData, $hasAnyValue] = $this->getTemplateData(
            $data,
            $unlocalizedDimensionContent->getTemplateData(),
            $localizedDimensionContent->getTemplateData(),
            $metadata,
        );

        if (!isset($data['template']) && !$hasAnyValue) {
            // do nothing when no data was given
            return;
        }

        $unlocalizedDimensionContent->setTemplateData($unlocalizedData);
        $localizedDimensionContent->setTemplateKey($template);
        $localizedDimensionContent->setTemplateData($localizedData);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $unlocalizedData
     * @param array<string, mixed> $localizedData
     *
     * @return array{
     *      0: array<string, mixed>,
     *      1: array<string, mixed>,
     *      2: bool,
     * }
     */
    private function getTemplateData(
        array $data,
        array $unlocalizedData,
        array $localizedData,
        FormMetadata $metadata,
    ): array {
        $hasAnyValue = false;

        $defaultLocalizedData = $localizedData; // use existing localizedData only as default to remove not longer existing properties of the template
        $localizedData = [];
        foreach ($metadata->getFlatFieldMetadata() as $property) {
            $name = $property->getName();

            $isMultilingual = $property->isMultilingual();

            $value = $isMultilingual ? $defaultLocalizedData[$name] ?? null : $defaultLocalizedData[$name] ?? null;
            if (\array_key_exists($name, $data)) { // values not explicitly given need to stay untouched for e.g. for shadow pages urls
                $hasAnyValue = true;
                $value = $data[$name];
            }

            if ($isMultilingual) {
                $localizedData[$name] = $value;
                continue;
            }

            $unlocalizedData[$name] = $value;
        }

        return [$unlocalizedData, $localizedData, $hasAnyValue];
    }
}
