<?php

declare(strict_types=1);

namespace Sulu\Content\Application\SmartResolver;

use Sulu\Content\Application\SmartResolver\Resolver\SmartResolverInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SmartResolverProvider implements SmartResolverProviderInterface
{
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

    public function getSmartResolvers(): array
    {
        return $this->smartResolvers->getProvidedServices();
    }
}
