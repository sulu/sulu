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
use Sulu\Content\Domain\Model\ExcerptInterface;

readonly class ExcerptDataMapper implements DataMapperInterface
{
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

        $excerptData = $localizedDimensionContent->getExcerptData();
        $validExcerptProperties = $this->getExcerptProperties($localizedDimensionContent);

        if (isset($data['excerpt']) && \is_array($data['excerpt'])) {
            $excerptData['excerpt'] ??= [];
            \assert(\is_array($excerptData['excerpt']));

            foreach ($data['excerpt'] as $fieldName => $value) {
                $propertyKey = 'excerpt/' . $fieldName;

                // Only store if the property is defined in the form metadata
                if (\array_key_exists($propertyKey, $validExcerptProperties)) {
                    $excerptData['excerpt'][$fieldName] = $value;
                }
            }
        }

        $localizedDimensionContent->setExcerptData($excerptData);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return array<string, mixed>
     */
    private function getExcerptProperties(DimensionContentInterface $dimensionContent): array
    {
        $locale = $dimensionContent->getLocale();
        if (!$locale) {
            return [];
        }

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->formMetadataProvider->getMetadata(
            'content_excerpt',
            $locale,
            ['instanceOf' => $dimensionContent::class],
        );

        return $formMetadata->getFlatFieldMetadata();
    }
}
