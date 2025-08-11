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

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;

readonly class ExcerptResolver implements ResolverInterface
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
        private MetadataResolver $metadataResolver
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        if (!$dimensionContent instanceof ExcerptInterface) {
            return null;
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata($this->getFormKey(), $locale, []);

        $formMetadataItems = $formMetadata->getItems();
        $data = $this->getExcerptData($dimensionContent);
        if (null !== $properties) {
            $filteredFormMetadataItems = [];
            $filteredTemplateData = [];
            $properties = $this->filterProperties($properties);
            foreach ($properties as $key => $value) {
                if (\array_key_exists($value, $formMetadataItems)) {
                    $filteredFormMetadataItems[$key] = $formMetadataItems[$value];
                }
                if (\array_key_exists($value, $data)) {
                    $filteredTemplateData[$key] = $data[$value];
                }
            }
            $formMetadataItems = $filteredFormMetadataItems;
            $data = $filteredTemplateData;
        }

        $resolvedItems = $this->metadataResolver->resolveItems($formMetadataItems, $data, $locale);

        return ContentView::create($this->normalizeResolvedItems($resolvedItems, $properties), []);
    }

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, string>
     */
    private function filterProperties(array $properties): array
    {
        $filteredProperties = [];
        foreach ($properties as $key => $value) {
            if (\str_starts_with((string) $value, self::getPrefix())) {
                $normalizedValue = 'excerpt' . \ucfirst(\substr((string) $value, \strlen(self::getPrefix())));
                $filteredProperties[$key] = $normalizedValue;
            }
        }

        return $filteredProperties;
    }

    /**
     * @param mixed[] $resolvedItems
     * @param array<string, string> $properties
     *
     * @return mixed[]
     */
    protected function normalizeResolvedItems(array $resolvedItems, ?array $properties): array
    {
        $result = [];
        foreach ($resolvedItems as $key => $item) {
            if (null !== $properties && \array_key_exists($key, $properties)) {
                $normalizedKey = $key;
            } else {
                $normalizedKey = \str_starts_with((string) $key, 'excerpt') ? \lcfirst(\substr((string) $key, \strlen('excerpt'))) : $key;
            }

            $result[$normalizedKey] = $item;
        }

        return $result;
    }

    protected function getFormKey(): string
    {
        return 'content_excerpt';
    }

    /**
     * @return array{
     *     excerptTitle: string|null,
     *     excerptMore: string|null,
     *     excerptDescription: string|null,
     *     excerptCategories: int[],
     *     excerptTags: string[],
     *     excerptIcon: array{id: int}|null,
     *     excerptImage: array{id: int}|null
     * }
     */
    protected function getExcerptData(ExcerptInterface $dimensionContent): array
    {
        return [
            'excerptTitle' => $dimensionContent->getExcerptTitle(),
            'excerptMore' => $dimensionContent->getExcerptMore(),
            'excerptDescription' => $dimensionContent->getExcerptDescription(),
            'excerptCategories' => \array_map(
                fn (CategoryInterface $category) => $category->getId(),
                $dimensionContent->getExcerptCategories()
            ),
            'excerptTags' => \array_map(
                fn (TagInterface $tag) => $tag->getName(), $dimensionContent->getExcerptTags()
            ),
            'excerptIcon' => $dimensionContent->getExcerptIcon(),
            'excerptImage' => $dimensionContent->getExcerptImage(),
        ];
    }

    public static function getPrefix(): string
    {
        return 'excerpt.';
    }
}
