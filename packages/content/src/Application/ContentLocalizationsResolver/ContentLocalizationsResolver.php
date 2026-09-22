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

namespace Sulu\Content\Application\ContentLocalizationsResolver;

use Psr\Container\ContainerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Hands the content to the resolver registered for its resource key, like the route defaults
 * providers, and to the route-based default otherwise.
 *
 * @final
 */
class ContentLocalizationsResolver implements ContentLocalizationsResolverInterface
{
    public function __construct(
        private readonly ContainerInterface $resolverLocator,
        private readonly ContentLocalizationsResolverInterface $defaultResolver,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, string $webspaceKey): array
    {
        $resourceKey = $dimensionContent::getResourceKey();

        if (!$this->resolverLocator->has($resourceKey)) {
            return $this->defaultResolver->resolve($dimensionContent, $webspaceKey);
        }

        $resolver = $this->resolverLocator->get($resourceKey);
        \assert($resolver instanceof ContentLocalizationsResolverInterface, 'The localizations resolver for "' . $resourceKey . '" must implement ContentLocalizationsResolverInterface but got: ' . \get_debug_type($resolver));

        return $resolver->resolve($dimensionContent, $webspaceKey);
    }
}
