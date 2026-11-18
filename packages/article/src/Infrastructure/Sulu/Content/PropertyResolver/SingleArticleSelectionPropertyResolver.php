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
use Sulu\Content\Application\DeduplicationStore\DeduplicationStoreInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SingleArticleSelectionPropertyResolver implements PropertyResolverInterface
{
    public function __construct(
        private ?DeduplicationStoreInterface $deduplicationStore = null,
    ) {
        if (null === $this->deduplicationStore) {
            @trigger_deprecation('sulu/sulu', '3.0.12', \sprintf('Instantiating %s without the $deduplicationStore argument is deprecated.', static::class));
        }
    }

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
        $resourceLoaderKey = $params['resourceLoader'] ?? ArticleResourceLoader::getKey();

        $this->deduplicationStore?->add(ArticleInterface::RESOURCE_KEY, $data);

        return ContentView::createResolvableWithReferences(
            id: $data,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: ArticleInterface::RESOURCE_KEY,
            view: [
                'id' => $data,
                ...$params,
            ],
            priority: 100,
            metadata: [
                'properties' => \array_merge(
                    $params['properties'] ?? [],
                    [
                        'title' => 'title',
                        'url' => 'url',
                    ],
                ),
            ],
        );
    }

    public static function getType(): string
    {
        return 'single_article_selection';
    }
}
