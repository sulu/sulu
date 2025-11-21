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
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Webmozart\Assert\Assert;

readonly class SeoDataMapper implements DataMapperInterface
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
    ) {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof SeoInterface) {
            return;
        }

        $this->setSeoData($localizedDimensionContent, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setSeoData(SeoInterface $dimensionContent, array $data): void
    {
        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        if (\array_key_exists('seo', $data)) {
            $validSeoProperties = $this->getSeoProperties($dimensionContent); // add support of unlocalized seo data in future

            Assert::isArray($data['seo']);
            $dimensionContent->setSeoData($data['seo']);
        }

        if (\array_key_exists('seoHideInSitemap', $data)) {
            Assert::boolean($data['seoHideInSitemap']);
            $dimensionContent->setSeoHideInSitemap($data['seoHideInSitemap']);
        }

        if (\array_key_exists('seoNoFollow', $data)) {
            Assert::boolean($data['seoNoFollow']);
            $dimensionContent->setSeoNoFollow($data['seoNoFollow']);
        }

        if (\array_key_exists('seoNoIndex', $data)) {
            Assert::boolean($data['seoNoIndex']);
            $dimensionContent->setSeoNoIndex($data['seoNoIndex']);
        }
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return array<string, mixed>
     */
    private function getSeoProperties(DimensionContentInterface $dimensionContent): array
    {
        $locale = $dimensionContent->getLocale();
        if (!$locale) {
            return [];
        }

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata(
            'content_seo',
            $locale,
            ['instanceOf' => $dimensionContent::class],
        );

        return $formMetadata->getFlatFieldMetadata();
    }
}
