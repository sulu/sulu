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
use Sulu\Content\Domain\Repository\DimensionContentRepositoryInterface;

class TemplateDataMapper implements DataMapperInterface
{
    public const SKIP_TAG = 'sulu_content.skip_template_data_mapper';

    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
        private ?DimensionContentRepositoryInterface $dimensionContentRepository = null,
    ) {
        if (null === $this->dimensionContentRepository) {
            @trigger_deprecation('sulu/sulu', '3.1', 'Instantiating TemplateDataMapper without the $dimensionContentRepository argument is deprecated.');
        }
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

        /** @var string|null $template */
        $template = $data['template'] ?? $localizedDimensionContent->getTemplateKey();

        $shadowSourceDimensionContent = null;
        if (null === $template) {
            // A new shadow saved from the settings tab sends no template, and not every type has a default.
            $shadowSourceDimensionContent = $this->findShadowSourceDimensionContent($localizedDimensionContent, $data);
            $template = $shadowSourceDimensionContent?->getTemplateKey();
        }

        $template ??= $typedMetadata->getDefaultType();

        if (!$template) {
            return;
        }

        $metadata = $typedMetadata->getForms()[$template] ?? null;

        if (!$metadata instanceof FormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find form metadata "%s" of type "%s".', $template, $type));
        }

        [$unlocalizedData, $localizedData, $hasAnyValue] = $this->getTemplateData(
            // the settings tab sends null for fields it does not show, which must not wipe the seeded source data
            $shadowSourceDimensionContent ? \array_filter($data, static fn ($value) => null !== $value) : $data,
            $unlocalizedDimensionContent->getTemplateData(),
            ($shadowSourceDimensionContent ?? $localizedDimensionContent)->getTemplateData(),
            $metadata,
        );

        if (!\array_key_exists('template', $data) && !$hasAnyValue && !$shadowSourceDimensionContent) {
            // do nothing when no data was given
            return;
        }

        $unlocalizedDimensionContent->setTemplateData($unlocalizedData);
        $localizedDimensionContent->setTemplateKey($template);
        $localizedDimensionContent->setTemplateData($localizedData);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $localizedDimensionContent
     * @param array<string, mixed> $data
     */
    private function findShadowSourceDimensionContent(
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): ?TemplateInterface {
        $shadowLocale = $data['shadowLocale'] ?? null;
        if (!$this->dimensionContentRepository
            || true !== ($data['shadowOn'] ?? false)
            || !\is_string($shadowLocale)
            || '' === $shadowLocale
        ) {
            return null;
        }

        $sourceDimensionContent = $this->dimensionContentRepository->findOneBy(
            $localizedDimensionContent->getResource(),
            [
                'locale' => $shadowLocale,
                'stage' => $localizedDimensionContent->getStage(),
            ],
        );

        return $sourceDimensionContent instanceof TemplateInterface ? $sourceDimensionContent : null;
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
            if ($property->hasTag(self::SKIP_TAG)) {
                continue;
            }

            $name = $property->getName();
            $name = \explode('/', $name, 2)[0];

            $isMultilingual = $property->isMultilingual();

            $value = $isMultilingual ? $defaultLocalizedData[$name] ?? null : $unlocalizedData[$name] ?? null;
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
