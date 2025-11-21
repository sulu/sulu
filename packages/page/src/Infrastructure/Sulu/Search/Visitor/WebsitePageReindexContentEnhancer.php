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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;

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

    public function __construct(
        private FormMetadataProvider $formMetadataProvider,
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
        $this->collectSearchableFields($metadata->getFlatFieldMetadata(), $searchableFields);
        $content = $this->extractContent($templateData, $searchableFields);

        $document['content'] = $content;

        return $document;
    }

    private function getTemplateFormMetadata(string $templateKey, string $locale): ?FormMetadata
    {
        /** @var TypedFormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata('page', $locale);
        /** @var array<string, FormMetadata> $forms */
        $forms = $formMetadata->getForms();

        return $forms[$templateKey] ?? null;
    }

    /**
     * @param array<ItemMetadata> $items
     * @param array<string, string> $fields
     */
    private function collectSearchableFields(array $items, array &$fields, string $prefix = ''): void
    {
        foreach ($items as $item) {
            if (!($item instanceof FieldMetadata)) {
                continue;
            }

            $hasSearchTag = false;
            foreach ($item->getTags() as $tag) {
                if (TagMetadata::SEARCH_FIELD_TAG === $tag->getName()) {
                    $hasSearchTag = true;
                    break;
                }
            }

            if ($hasSearchTag) {
                $fieldPath = $prefix ? $prefix . '.' . $item->getName() : $item->getName();
                $fields[$fieldPath] = $item->getType();
            }

            if ('block' === $item->getType()) {
                $blockPath = $prefix ? $prefix . '.' . $item->getName() : $item->getName();
                foreach ($item->getTypes() as $type => $typeFormMetadata) {
                    $this->collectSearchableFields(
                        $typeFormMetadata->getItems(),
                        $fields,
                        $blockPath
                    );
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $templateData
     * @param array<string, string> $searchableFields
     *
     * @return array<int, string>
     */
    private function extractContent(array $templateData, array $searchableFields): array
    {
        $content = [];

        foreach ($searchableFields as $fieldPath => $fieldType) {
            if (!\in_array($fieldType, self::SUPPORTED_FIELD_TYPES, true)) {
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
