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

use Sulu\Bundle\AdminBundle\Application\BlockIdGenerator\BlockIdGeneratorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

class TemplateDataMapper implements DataMapperInterface
{
    public const SKIP_TAG = 'sulu_content.skip_template_data_mapper';

    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
        private BlockIdGeneratorInterface $blockIdGenerator,
    ) {
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

        // getDimensionContent() may alias both params to the same instance (e.g. preview) - plain
        // overwrite would then wipe the unlocalized data just written above. Merge instead, mirroring
        // TemplateMerger's own unlocalized+localized merge pattern; the normal (distinct-instance)
        // persisted-save path is unaffected since $localizedData already fully replaces that instance.
        $localizedDimensionContent->setTemplateData(
            $unlocalizedDimensionContent === $localizedDimensionContent
                ? \array_merge($unlocalizedData, $localizedData)
                : $localizedData
        );
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

                $value = $this->ensureBlockIds($value, $property);
            }

            if ($isMultilingual) {
                $localizedData[$name] = $value;
                continue;
            }

            $unlocalizedData[$name] = $value;
        }

        return [$unlocalizedData, $localizedData, $hasAnyValue];
    }

    /**
     * Assigns a generated `_id` to every item of a "typed" property (one that declares `<types>` sub-forms
     * in its template XML - block, image_map, and any future/custom content type following the same
     * pattern) that does not already have one, recursing into further nested typed properties (e.g. a
     * "columns" block type containing its own block property, or a hotspot type containing its own
     * image_map). Deliberately keyed off `getTypes()` rather than a hardcoded list of type names (like
     * 'block'/'image_map') so a custom bundle registering its own polymorphic content type is covered too,
     * without needing to touch this class. This runs on every save regardless of whether the admin UI ever
     * mounted (i.e. expanded) the item, so ids are guaranteed to exist for the preview-to-admin-form
     * navigation feature to rely on.
     */
    private function ensureBlockIds(mixed $value, FieldMetadata $property): mixed
    {
        $types = $property->getTypes();

        if ([] === $types || !\is_array($value)) {
            return $value;
        }

        if ($this->isItemList($value)) {
            return $this->ensureItemIds($value, $property, $types);
        }

        // A typed property's items don't always live directly in $value - image_map for example wraps
        // them as $value['hotspots'] alongside its own 'imageId'. Since the wrapping key is defined by
        // each content type's own storage format (not discoverable from metadata), fall back to
        // scanning $value's own array entries for the item list instead of hardcoding known keys.
        foreach ($value as $key => $nested) {
            if (\is_array($nested) && $this->isItemList($nested)) {
                $value[$key] = $this->ensureItemIds($nested, $property, $types);
            }
        }

        return $value;
    }

    /**
     * A "list of typed items" is a sequential array of associative arrays - the shape every block/image_map
     * -like content type stores its polymorphic items as. Plain lists of scalars (e.g. select/category_selection
     * values) or single associative values (e.g. link) never match, so they're left untouched.
     *
     * @param mixed[] $value
     */
    private function isItemList(array $value): bool
    {
        if (!\array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!\is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed[] $items
     * @param array<string, FormMetadata> $types
     *
     * @return mixed[]
     */
    private function ensureItemIds(array $items, FieldMetadata $property, array $types): array
    {
        foreach ($items as $index => $item) {
            if (!\is_array($item)) {
                continue;
            }

            if (!isset($item['_id']) || !\is_string($item['_id']) || '' === $item['_id']) {
                $item['_id'] = $this->blockIdGenerator->generateId();
            }

            /** @var string|null $itemType */
            $itemType = $item['type'] ?? $property->getDefaultType();
            $itemMetadata = null !== $itemType ? ($types[$itemType] ?? null) : null;

            if ($itemMetadata instanceof FormMetadata) {
                foreach ($itemMetadata->getFlatFieldMetadata() as $subProperty) {
                    $subName = $subProperty->getName();

                    if (\array_key_exists($subName, $item)) {
                        $item[$subName] = $this->ensureBlockIds($item[$subName], $subProperty);
                    }
                }
            }

            $items[$index] = $item;
        }

        return $items;
    }
}
