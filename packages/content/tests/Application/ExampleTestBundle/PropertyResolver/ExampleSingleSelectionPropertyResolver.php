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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader\ExampleResourceLoader;

class ExampleSingleSelectionPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param array{
     *     resourceLoader?: string,
     *     properties?: array<string, mixed>|null,
     * } $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_int($data) && !(\is_string($data) && '' !== $data)) {
            return ContentView::create(null, ['id' => null, ...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? ExampleResourceLoader::getKey();

        return ContentView::createResolvableWithReferences(
            id: $data,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: Example::RESOURCE_KEY,
            view: ['id' => $data],
            priority: 150,
            metadata: [
                'properties' => $params['properties'] ?? null,
            ],
        );
    }

    public static function getType(): string
    {
        return 'single_example_selection';
    }
}
