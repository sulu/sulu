<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SingleSnippetSelectionPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param array{
     *     resourceLoader?: string,
     *     properties?: array<string, mixed>|null,
     * } $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_string($data)) {
            return ContentView::create(null, \array_merge(['id' => null], $params));
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? SnippetResourceLoader::getKey();

        return ContentView::createResolvable(
            id: $data,
            resourceLoaderKey: $resourceLoaderKey,
            view: [
                'id' => $data,
                ...$params,
            ],
            priority: 100,
            metadata: [
                'properties' => $params['properties'] ?? null,
            ]
        );
    }

    public static function getType(): string
    {
        return 'single_snippet_selection';
    }
}
