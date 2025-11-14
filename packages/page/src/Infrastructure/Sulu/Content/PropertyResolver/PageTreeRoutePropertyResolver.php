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
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class PageTreeRoutePropertyResolver implements PropertyResolverInterface
{
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_array($data)
            || !\is_array($data['page'] ?? null)
            || !\is_string($data['page']['path'] ?? null)
            || !\is_string($data['suffix'] ?? null)
        ) {
            return ContentView::create(null, [...$params]);
        }

        return ContentView::create(\rtrim($data['page']['path'], '/') . '/' . \ltrim($data['suffix'], '/'), [...$data, ...$params]);
    }

    public static function getType(): string
    {
        return 'page_tree_route';
    }
}
