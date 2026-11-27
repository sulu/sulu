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

/**
 * Splits the data of a form (e.g. excerpt or seo) into localized and unlocalized data,
 * based on the multilingual flag of its properties.
 *
 * @internal
 */
trait UnlocalizedPropertiesDataMapperTrait
{
    /**
     * Returns the names (without prefix) of the properties declared with multilingual="false" in the given form.
     *
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return string[]
     */
    private function getUnlocalizedPropertyNames(
        MetadataProviderInterface $formMetadataProvider,
        string $formKey,
        string $prefix,
        DimensionContentInterface $dimensionContent,
    ): array {
        $locale = $dimensionContent->getLocale();
        if (!$locale) {
            return [];
        }

        /** @var FormMetadata $formMetadata */
        $formMetadata = $formMetadataProvider->getMetadata(
            $formKey,
            $locale,
            ['instanceOf' => $dimensionContent::class],
        );

        $propertyNames = [];
        foreach ($formMetadata->getFlatFieldMetadata() as $fieldMetadata) {
            $name = $fieldMetadata->getName();
            if ($fieldMetadata->isMultilingual() || !\str_starts_with($name, $prefix . '/')) {
                continue;
            }

            $propertyNames[] = \explode('/', $name, 3)[1];
        }

        return $propertyNames;
    }

    /**
     * @param mixed[] $data
     * @param array<string, mixed> $unlocalizedData
     * @param string[] $unlocalizedPropertyNames
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} the unlocalized and the localized data
     */
    private function splitUnlocalizedData(array $data, array $unlocalizedData, array $unlocalizedPropertyNames): array
    {
        $localizedData = [];
        foreach ($data as $name => $value) {
            if (\in_array($name, $unlocalizedPropertyNames, true)) {
                $unlocalizedData[(string) $name] = $value;

                continue;
            }

            $localizedData[(string) $name] = $value;
        }

        return [$unlocalizedData, $localizedData];
    }
}
