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

namespace Sulu\Page\Infrastructure\Sulu\Search\Visitor;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

/**
 * @internal if you need to override this service, create a new service based on the WebsitePageReindexProviderEnhancerInterface
 * instead of extending this class
 *
 * @final
 */
class WebsitePageReindexContentEnhancer implements WebsitePageReindexProviderEnhancerInterface
{
    private const SUPPORTED_FIELD_TYPES = [
        'text_line',
        'text_area',
        'text_editor',
    ];

    private const MEDIA_FIELD_TYPES = [
        'media_selection',
        'single_media_selection',
    ];

    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
    ) {
    }

    public function enhanceQuery(QueryBuilder $queryBuilder): void
    {
        $queryBuilder
            ->addSelect('dimensionContent.templateKey')
            ->addSelect('dimensionContent.templateData');
    }

    public function enhanceDocument(array $queryResult, array $document): array
    {
        $templateKey = $queryResult['templateKey'] ?? null;
        $locale = $queryResult['locale'] ?? null;
        $templateData = $queryResult['templateData'] ?? [];
        $document['content'] = [];

        if (!\is_string($templateKey) || !\is_string($locale) || !\is_array($templateData) || 0 === \count($templateData)) {
            return $document;
        }

        /** @var array<string, mixed> $templateData */
        $metadata = $this->getTemplateFormMetadata($templateKey, $locale);
        if (!$metadata) {
            return $document;
        }

        $searchableFields = [];
        $this->collectSearchableFields($metadata->getFlatFieldMetadata(), $searchableFields, '', $locale);

        $document['content'] = $this->extractContent($templateData, $searchableFields);

        $title = $this->extractTitle($templateData, $searchableFields);
        if (null !== $title) {
            $document['title'] = $title;
        }

        $mediaId = $this->extractMediaId($templateData, $searchableFields);
        if ('' !== $mediaId) {
            $document['mediaId'] = $mediaId;
        }

        return $document;
    }

    private function getTemplateFormMetadata(string $templateKey, string $locale): ?FormMetadata
    {
        /** @var TypedFormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata('page', $locale, []);
        /** @var array<string, FormMetadata> $forms */
        $forms = $formMetadata->getForms();

        return $forms[$templateKey] ?? null;
    }

    /**
     * @param array<ItemMetadata> $items
     * @param array<string, array{type: string, role: string|null}> $fields
     */
    private function collectSearchableFields(array $items, array &$fields, string $prefix, string $locale): void
    {
        foreach ($items as $item) {
            if (!$item instanceof FieldMetadata) {
                continue;
            }

            $role = null;
            $hasSearchTag = false;
            foreach ($item->getTags() as $tag) {
                if (TagMetadata::SEARCH_FIELD_TAG === $tag->getName()) {
                    $hasSearchTag = true;
                    $role = $tag->getAttribute('role');
                    break;
                }
            }

            if ($hasSearchTag) {
                $fieldPath = $prefix ? $prefix . '.' . $item->getName() : $item->getName();
                $fields[$fieldPath] = [
                    'type' => $item->getType(),
                    'role' => \is_string($role) ? $role : null,
                ];
            }

            if ('block' === $item->getType()) {
                $blockPath = $prefix ? $prefix . '.' . $item->getName() : $item->getName();
                foreach ($item->getTypes() as $typeFormMetadata) {
                    $this->collectSearchableFields(
                        $this->resolveTypeItems($typeFormMetadata, $locale),
                        $fields,
                        $blockPath,
                        $locale
                    );
                }
            }
        }
    }

    /**
     * A global block type has no items of its own, so its referenced block form is resolved instead.
     *
     * @return array<ItemMetadata>
     */
    private function resolveTypeItems(FormMetadata $type, string $locale): array
    {
        $globalBlockTag = $type->getTagsByName('sulu.global_block')[0] ?? null;
        if (null === $globalBlockTag) {
            return $type->getItems();
        }

        $globalBlockName = $globalBlockTag->getAttribute('global_block');
        if (!\is_string($globalBlockName)) {
            return $type->getItems();
        }

        /** @var TypedFormMetadata $blockMetadata */
        $blockMetadata = $this->formMetadataProvider->getMetadata('block', $locale, ['ignore_global_blocks' => true]);
        /** @var array<string, FormMetadata> $forms */
        $forms = $blockMetadata->getForms();
        $globalBlockForm = $forms[$globalBlockName] ?? null;

        return $globalBlockForm?->getItems() ?? $type->getItems();
    }

    /**
     * @param array<string, mixed> $templateData
     * @param array<string, array{type: string, role: string|null}> $searchableFields
     *
     * @return array<int, string>
     */
    private function extractContent(array $templateData, array $searchableFields): array
    {
        $content = [];

        foreach ($searchableFields as $fieldPath => $fieldInfo) {
            if (!\in_array($fieldInfo['type'], self::SUPPORTED_FIELD_TYPES, true)) {
                continue;
            }

            if (\in_array($fieldInfo['role'], ['image', 'title', 'url'], true)) {
                continue;
            }

            $value = $this->getValueByPath($templateData, $fieldPath);
            if (null !== $value) {
                $extracted = $this->extractTextFromValue($value);
                $content = \array_merge($content, $extracted);
            }
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $templateData
     * @param array<string, array{type: string, role: string|null}> $searchableFields
     */
    private function extractTitle(array $templateData, array $searchableFields): ?string
    {
        foreach ($searchableFields as $fieldPath => $fieldInfo) {
            if ('title' !== $fieldInfo['role']) {
                continue;
            }

            if (!\in_array($fieldInfo['type'], self::SUPPORTED_FIELD_TYPES, true)) {
                continue;
            }

            $value = $this->getValueByPath($templateData, $fieldPath);
            if (\is_string($value) && '' !== \trim($value)) {
                return $this->stripHtml($value);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $templateData
     * @param array<string, array{type: string, role: string|null}> $searchableFields
     */
    private function extractMediaId(array $templateData, array $searchableFields): string
    {
        foreach ($searchableFields as $fieldPath => $fieldInfo) {
            if ('image' !== $fieldInfo['role']) {
                continue;
            }

            if (!\in_array($fieldInfo['type'], self::MEDIA_FIELD_TYPES, true)) {
                continue;
            }

            $value = $this->getValueByPath($templateData, $fieldPath);
            if (!\is_array($value)) {
                continue;
            }

            if (isset($value['id']) && \is_numeric($value['id'])) {
                return (string) $value['id'];
            }

            if (isset($value['ids']) && \is_array($value['ids']) && [] !== $value['ids']) {
                $firstId = $value['ids'][0] ?? null;
                if (null !== $firstId && \is_numeric($firstId)) {
                    return (string) $firstId;
                }
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getValueByPath(array $data, string $path): mixed
    {
        $keys = \explode('.', $path);
        $current = $data;

        foreach ($keys as $keyIndex => $key) {
            if (!\is_array($current)) {
                return null;
            }

            if (\array_key_exists($key, $current)) {
                $current = $current[$key];
                continue;
            }

            if (\array_is_list($current)) {
                $remainingPath = \implode('.', \array_slice($keys, $keyIndex));

                return $this->extractFromArrayList($current, $remainingPath);
            }

            return null;
        }

        return $current;
    }

    /**
     * @param array<int, mixed> $list
     *
     * @return array<int, mixed>|null
     */
    private function extractFromArrayList(array $list, string $remainingPath): ?array
    {
        $results = [];

        foreach ($list as $item) {
            if (\is_array($item)) {
                /** @var array<string, mixed> $item */
                $value = $this->getValueByPath($item, $remainingPath);
                if (null !== $value) {
                    $results[] = $value;
                }
            }
        }

        return 0 !== \count($results) ? $results : null;
    }

    /**
     * @return array<int, string>
     */
    private function extractTextFromValue(mixed $value): array
    {
        if (\is_string($value)) {
            $text = $this->stripHtml($value);

            return !empty(\trim($text)) ? [$text] : [];
        }

        if (\is_array($value)) {
            $content = [];
            foreach ($value as $item) {
                if (\is_array($item)) {
                    $content = \array_merge($content, $this->extractTextFromValue($item));
                } elseif (\is_string($item)) {
                    $text = $this->stripHtml($item);
                    if (!empty(\trim($text))) {
                        $content[] = $text;
                    }
                }
            }

            return $content;
        }

        return [];
    }

    private function stripHtml(string $html): string
    {
        return \trim(\strip_tags($html));
    }
}
