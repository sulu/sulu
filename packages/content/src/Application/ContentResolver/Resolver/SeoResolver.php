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
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;

readonly class SeoResolver implements ResolverInterface
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
        private MetadataResolver $metadataResolver
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        if (!$dimensionContent instanceof SeoInterface) {
            return null;
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata($this->getFormKey(), $locale, []);

        $formMetadataItems = \array_filter($formMetadata->getItems(), function($item) {
            return !\in_array($item->getType(), $this->excludedPropertyTypes(), true);
        });
        $data = $this->getSeoData($dimensionContent);
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
                $normalizedValue = 'seo' . \ucfirst(\substr((string) $value, \strlen(self::getPrefix())));
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
                $normalizedKey = \str_starts_with((string) $key, 'seo') ? \lcfirst(\substr((string) $key, \strlen('seo'))) : $key;
            }

            $result[$normalizedKey] = $item;
        }

        return $result;
    }

    protected function getFormKey(): string
    {
        return 'content_seo';
    }

    /**
     * @return string[]
     */
    protected function excludedPropertyTypes(): array
    {
        return ['search_result'];
    }

    /**
     * @return array{
     *     seoTitle: string|null,
     *     seoDescription: string|null,
     *     seoKeywords: string|null,
     *     seoCanonicalUrl: string|null,
     *     seoNoIndex: bool,
     *     seoNoFollow: bool,
     *     seoHideInSitemap: bool
     * }
     */
    protected function getSeoData(SeoInterface $dimensionContent): array
    {
        return [
            'seoTitle' => $dimensionContent->getSeoTitle(),
            'seoDescription' => $dimensionContent->getSeoDescription(),
            'seoKeywords' => $dimensionContent->getSeoKeywords(),
            'seoCanonicalUrl' => $dimensionContent->getSeoCanonicalUrl(),
            'seoNoIndex' => $dimensionContent->getSeoNoIndex(),
            'seoNoFollow' => $dimensionContent->getSeoNoFollow(),
            'seoHideInSitemap' => $dimensionContent->getSeoHideInSitemap(),
        ];
    }

    public static function getPrefix(): string
    {
        return 'seo.';
    }
}
