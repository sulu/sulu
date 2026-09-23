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

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Hands the content to the resolver registered for its resource key, like the route defaults
 * providers, and to the route-based default otherwise.
 *
 * @final
 */
class ContentLocalizationsResolver implements ContentLocalizationsResolverInterface
{
    /**
     * @param ServiceLocator<ContentLocalizationsResolverInterface> $resolvers
     */
    public function __construct(
        private readonly ServiceLocator $resolvers,
        private readonly ContentLocalizationsResolverInterface $defaultResolver,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, string $webspaceKey): array
    {
        $resourceKey = $dimensionContent::getResourceKey();

        if (!$this->resolvers->has($resourceKey)) {
            return $this->defaultResolver->resolve($dimensionContent, $webspaceKey);
        }

        return $this->resolvers->get($resourceKey)->resolve($dimensionContent, $webspaceKey);
    }
}
