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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ResourceLoader\Loader\TeaserResourceLoader;

class TeaserSelectionPropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        $view = [
            'presentAs' => \is_array($data) && isset($data['presentAs']) && \is_string($data['presentAs']) ? $data['presentAs'] : null,
            'items' => [],
            ...$params,
        ];
        unset($view['metadata'], $view['present_as']);

        if (
            !\is_array($data)
            || !\array_key_exists('items', $data)
            || !\is_array($data['items'])
        ) {
            return ContentView::create([], $view);
        }

        $resourceLoaderKey = isset($params['resourceLoader']) && \is_string($params['resourceLoader']) ? $params['resourceLoader'] : TeaserResourceLoader::getKey();

        /** @var list<array{id: string, type: string}> $items */
        $items = [];
        $resolvableResources = [];
        foreach ($data['items'] as $item) {
            if (!\is_array($item)
                || !\array_key_exists('id', $item)
                || !\array_key_exists('type', $item)
                || !\is_string($item['id'])
                || !\is_string($item['type'])
            ) {
                continue;
            }

            $type = $item['type'];
            $id = $item['id'];
            /** @var array<string, mixed> $itemData */
            $itemData = $item;

            $items[] = [
                'id' => $id,
                'type' => $type,
            ];

            $resolvableResources[] = new ResolvableResource(
                id: $type . '::' . $id,
                resourceLoaderKey: $resourceLoaderKey,
                priority: -50,
                resourceCallback: static function(Teaser $resource) use ($itemData): Teaser {
                    return $resource->merge($itemData);
                }
            );
        }

        $view['items'] = $items;

        return ContentView::create($resolvableResources, $view);
    }

    public static function getType(): string
    {
        return 'teaser_selection';
    }
}
