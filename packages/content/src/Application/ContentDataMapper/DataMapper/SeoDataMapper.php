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

use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Webmozart\Assert\Assert;

readonly class SeoDataMapper implements DataMapperInterface
{
    use UnlocalizedPropertiesDataMapperTrait;

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

        $this->setSeoData($unlocalizedDimensionContent, $localizedDimensionContent, $data);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $unlocalizedDimensionContent
     * @param mixed[] $data
     */
    private function setSeoData(DimensionContentInterface $unlocalizedDimensionContent, SeoInterface $dimensionContent, array $data): void
    {
        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        if (\array_key_exists('seo', $data)) {
            $seoData = $data['seo'];
            Assert::isArray($seoData);

            $unlocalizedSeoPropertyNames = $unlocalizedDimensionContent instanceof SeoInterface
                ? $this->getUnlocalizedPropertyNames($this->formMetadataProvider, 'content_seo', 'seo', $dimensionContent)
                : [];

            if ([] === $unlocalizedSeoPropertyNames) {
                $dimensionContent->setSeoData($seoData); // @phpstan-ignore-line argument.type
            } else {
                [$unlocalizedSeoData, $localizedSeoData] = $this->splitUnlocalizedData(
                    $seoData,
                    $unlocalizedDimensionContent->getSeoData(),
                    $unlocalizedSeoPropertyNames,
                );

                $unlocalizedDimensionContent->setSeoData($unlocalizedSeoData);
                $dimensionContent->setSeoData($localizedSeoData);
            }
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
}
