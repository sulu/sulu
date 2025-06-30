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

namespace Sulu\Content\Application\SmartResolver;

use Sulu\Content\Application\SmartResolver\Resolver\SmartResolverInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SmartResolverProvider implements SmartResolverProviderInterface
{
    /**
     * @param ServiceLocator<SmartResolverInterface> $smartResolvers
     */
    public function __construct(private ServiceLocator $smartResolvers)
    {
    }

    public function getSmartResolver(string $type): SmartResolverInterface
    {
        if (!$this->smartResolvers->has($type)) {
            throw new \InvalidArgumentException(
                \sprintf('Smart resolver for type "%s" not found.', $type),
            );
        }

        return $this->smartResolvers->get($type);
    }

    public function hasSmartResolver(string $type): bool
    {
        return $this->smartResolvers->has($type);
    }
}
