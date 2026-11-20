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

namespace Sulu\Snippet\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\DeduplicationStore\DeduplicationStoreInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SnippetSelectionPropertyResolver implements PropertyResolverInterface
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
     *     default?: string,
     * } $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if ((null === $data || [] === $data) && isset($params['default'])) {
            return ContentView::createSmartResolvable(
                data: [
                    'areaKey' => [$params['default']],
                ],
                resourceLoaderKey: 'snippet_area_default',
                view: $params,
            );
        }

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
        $resourceLoaderKey = $params['resourceLoader'] ?? SnippetResourceLoader::getKey();

        $this->deduplicationStore?->add(SnippetInterface::RESOURCE_KEY, ...$identifiers);

        return ContentView::createResolvablesWithReferences(
            ids: $identifiers,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: SnippetInterface::RESOURCE_KEY,
            view: [],
            priority: 100,
            metadata: [
                'properties' => $params['properties'] ?? null,
            ],
        );
    }

    public static function getType(): string
    {
        return 'snippet_selection';
    }
}
