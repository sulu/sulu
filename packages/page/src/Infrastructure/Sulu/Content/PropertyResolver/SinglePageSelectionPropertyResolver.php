<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\DeduplicationStore\DeduplicationStoreInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SinglePageSelectionPropertyResolver implements PropertyResolverInterface
{
    public function __construct(
        private ?DeduplicationStoreInterface $deduplicationStore = null,
    ) {
        if (null === $this->deduplicationStore) {
            @trigger_deprecation('sulu/sulu', '3.0.12', 'Instantiating SinglePageSelectionPropertyResolver without the $deduplicationStore argument is deprecated.');
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
            return ContentView::create(null, ['id' => null, ...$params]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? PageResourceLoader::getKey();

        if ('' !== (string) $data) {
            $this->deduplicationStore?->add((string) $data, PageInterface::RESOURCE_KEY);
        }

        return ContentView::createResolvableWithReferences(
            id: $data,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: PageInterface::RESOURCE_KEY,
            view: [
                'id' => $data,
                ...$params,
            ],
            priority: 150,
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
        return 'single_page_selection';
    }
}
