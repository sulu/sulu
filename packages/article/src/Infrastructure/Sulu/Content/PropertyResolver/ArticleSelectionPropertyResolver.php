<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader\ArticleResourceLoader;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class ArticleSelectionPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param array{
     *     resourceLoader?: string,
     *     properties?: array<string, mixed>|null,
     * } $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (
            !\is_array($data)
            || !\array_is_list($data)
        ) {
            return ContentView::create([], \array_merge(['ids' => []], $params));
        }

        $identifiers = [];
        foreach ($data as $identifier) {
            if (!\is_string($identifier)) {
                return ContentView::create([], $params);
            }

            $identifiers[] = $identifier;
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? ArticleResourceLoader::getKey();

        return ContentView::createResolvablesWithReferences(
            ids: $identifiers,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: ArticleInterface::RESOURCE_KEY,
            view: [
                'ids' => $identifiers,
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
        return 'article_selection';
    }
}
