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
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;

readonly class ExcerptTaxonomyResolver implements ResolverInterface
{
    private const TAXONOMY_FIELDS = ['categories', 'tags', 'segment', 'audienceTargetGroups'];

    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
        private MetadataResolver $metadataResolver,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        if (!$dimensionContent instanceof ExcerptInterface && !$dimensionContent instanceof TaxonomyInterface) {
            return null;
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata(
            $this->getFormKey(),
            $locale,
            ['instanceOf' => $dimensionContent::class],
        );

        $formMetadataItems = $formMetadata->getFlatFieldMetadata();
        $data = $this->getExcerptTaxonomyData($dimensionContent);
        $nullProperties = [];
        if (null !== $properties) {
            $filteredFormMetadataItems = [];
            $filteredTemplateData = [];
            $properties = $this->filterProperties($properties);
            foreach ($properties as $key => $value) {
                if (\array_key_exists($value, $formMetadataItems)) {
                    $filteredFormMetadataItems[$key] = $formMetadataItems[$value];
                } else {
                    $nullProperties[$key] = null;
                }
                if (\array_key_exists($value, $data)) {
                    $filteredTemplateData[$key] = $data[$value];
                }
            }
            $formMetadataItems = $filteredFormMetadataItems;
            $data = $filteredTemplateData;
        }

        $resolvedItems = $this->metadataResolver->resolveItems($formMetadataItems, $data, $locale);

        return ContentView::create(
            \array_merge($nullProperties, $this->normalizeResolvedItems($resolvedItems, $properties)),
            [],
        );
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
                $suffix = \substr((string) $value, \strlen(self::getPrefix()));

                $normalizedValue = match (true) {
                    \in_array($suffix, self::TAXONOMY_FIELDS, true) => 'excerpt' . \ucfirst($suffix),
                    default => 'excerpt/' . $suffix,
                };
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
                if (\str_starts_with((string) $key, 'excerpt/')) {
                    $normalizedKey = \substr((string) $key, \strlen('excerpt/'));
                } elseif ('excerpt' === $key) {
                    $normalizedKey = 'excerpt';
                } elseif (\str_starts_with((string) $key, 'excerpt')) {
                    $normalizedKey = \lcfirst(\substr((string) $key, \strlen('excerpt')));
                } else {
                    $normalizedKey = $key;
                }
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
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     *
     * @return array<string, mixed>
     */
    protected function getExcerptTaxonomyData(DimensionContentInterface $dimensionContent): array
    {
        $data = [];

        if ($dimensionContent instanceof ExcerptInterface) {
            // Flatten nested structure for metadata resolver
            foreach ($dimensionContent->getExcerptData() as $fieldName => $value) {
                $data['excerpt/' . $fieldName] = $value;
            }
        }

        if ($dimensionContent instanceof TaxonomyInterface) {
            $data = \array_merge($data, [
                'excerptSegment' => $dimensionContent->getExcerptSegment(),
                'excerptCategories' => \array_map(
                    fn (CategoryInterface $category) => $category->getId(),
                    $dimensionContent->getExcerptCategories(),
                ),
                'excerptTags' => \array_map(
                    fn (TagInterface $tag) => $tag->getId(),
                    $dimensionContent->getExcerptTags(),
                ),
                'excerptAudienceTargetGroups' => \array_map(
                    fn (TargetGroupInterface $audienceTargetGroup) => $audienceTargetGroup->getId(),
                    $dimensionContent->getExcerptAudienceTargetGroups(),
                ),
            ]);
        }

        return $data;
    }

    public static function getPrefix(): string
    {
        return 'excerpt.';
    }
}
