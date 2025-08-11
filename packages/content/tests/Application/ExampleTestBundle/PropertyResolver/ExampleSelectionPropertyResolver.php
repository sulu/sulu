<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application\ExampleTestBundle\PropertyResolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader\ExampleResourceLoader;

class ExampleSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_array($data)
            || 0 === \count($data)
            || !\array_is_list($data)
        ) {
            return ContentView::create([], ['ids' => [], ...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? ExampleResourceLoader::getKey();

        /** @var string[] $ids */
        $ids = $data;

        $metadata = $params['metadata'] ?? null;
        $properties = null;
        if ($metadata instanceof FieldMetadata && $propertiesMetadata = $metadata->getOptions()['properties'] ?? null) {
            $properties = [];

            /** @var OptionMetadata[] $optionsMetadataArray */
            $optionsMetadataArray = $propertiesMetadata->getValue();
            foreach ($optionsMetadataArray as $optionMetadata) {
                $properties[$optionMetadata->getName()] = $optionMetadata->getValue();
            }
        }

        return ContentView::createResolvables(
            ids: $ids,
            resourceLoaderKey: $resourceLoaderKey,
            view: [
                'ids' => $ids,
                ...$params,
            ],
            priority: 150,
            metadata: [
                'properties' => $properties,
            ]
        );
    }

    public static function getType(): string
    {
        return 'example_selection';
    }
}
