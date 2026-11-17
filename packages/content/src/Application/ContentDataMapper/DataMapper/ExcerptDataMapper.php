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
use Sulu\Content\Domain\Model\ExcerptInterface;
use Webmozart\Assert\Assert;

readonly class ExcerptDataMapper implements DataMapperInterface
{
    use UnlocalizedPropertiesDataMapperTrait;

    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
    ) {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof ExcerptInterface) {
            return;
        }

        if (\array_key_exists('excerpt', $data)) {
            $excerptData = $data['excerpt'];
            Assert::isArray($excerptData);

            $unlocalizedExcerptPropertyNames = $unlocalizedDimensionContent instanceof ExcerptInterface
                ? $this->getUnlocalizedPropertyNames($this->formMetadataProvider, 'content_excerpt', 'excerpt', $localizedDimensionContent)
                : [];

            if ([] === $unlocalizedExcerptPropertyNames) {
                $localizedDimensionContent->setExcerptData($excerptData); // @phpstan-ignore-line argument.type

                return;
            }

            [$unlocalizedExcerptData, $localizedExcerptData] = $this->splitUnlocalizedData(
                $excerptData,
                $unlocalizedDimensionContent->getExcerptData(),
                $unlocalizedExcerptPropertyNames,
            );

            $unlocalizedDimensionContent->setExcerptData($unlocalizedExcerptData);
            $localizedDimensionContent->setExcerptData($localizedExcerptData);
        }
    }
}
