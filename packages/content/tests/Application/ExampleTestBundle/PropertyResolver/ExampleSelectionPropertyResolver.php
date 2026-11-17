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

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\DeduplicationStore\DeduplicationStoreInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader\ExampleResourceLoader;

class ExampleSelectionPropertyResolver implements PropertyResolverInterface
{
    public function __construct(
        private ?DeduplicationStoreInterface $deduplicationStore = null,
    ) {
    }

    /**
     * @param array{
     *     resourceLoader?: string,
     *     properties?: array<string, mixed>|null,
     * } $params
     */
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

        foreach ($ids as $id) {
            if ('' !== (string) $id) {
                $this->deduplicationStore?->add((string) $id, Example::RESOURCE_KEY);
            }
        }

        return ContentView::createResolvablesWithReferences(
            ids: $ids,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: Example::RESOURCE_KEY,
            view: [],
            priority: 150,
            metadata: [
                'properties' => $params['properties'] ?? null,
            ],
        );
    }

    public static function getType(): string
    {
        return 'example_selection';
    }
}
