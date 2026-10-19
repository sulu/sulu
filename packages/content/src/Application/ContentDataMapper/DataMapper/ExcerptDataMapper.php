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
use Webmozart\Assert\Assert;

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

        if (\array_key_exists('excerpt', $data)) {
            $excerptData = $data['excerpt'];
            Assert::isArray($excerptData);

            $unlocalizedExcerptPropertyNames = $unlocalizedDimensionContent instanceof ExcerptInterface
                ? $this->getUnlocalizedExcerptPropertyNames($localizedDimensionContent)
                : [];

            if ([] === $unlocalizedExcerptPropertyNames) {
                $localizedDimensionContent->setExcerptData($excerptData); // @phpstan-ignore-line argument.type

                return;
            }

            $unlocalizedExcerptData = $unlocalizedDimensionContent->getExcerptData();
            $localizedExcerptData = [];
            foreach ($excerptData as $name => $value) {
                if (\in_array($name, $unlocalizedExcerptPropertyNames, true)) {
                    $unlocalizedExcerptData[$name] = $value;

                    continue;
                }

                $localizedExcerptData[$name] = $value;
            }

            $unlocalizedDimensionContent->setExcerptData($unlocalizedExcerptData);
            $localizedDimensionContent->setExcerptData($localizedExcerptData); // @phpstan-ignore-line argument.type
        }
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return string[]
     */
    private function getUnlocalizedExcerptPropertyNames(DimensionContentInterface $dimensionContent): array
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

        $propertyNames = [];
        foreach ($formMetadata->getFlatFieldMetadata() as $fieldMetadata) {
            $name = $fieldMetadata->getName();
            if ($fieldMetadata->isMultilingual() || !\str_starts_with($name, 'excerpt/')) {
                continue;
            }

            $propertyNames[] = \explode('/', $name, 3)[1];
        }

        return $propertyNames;
    }
}
