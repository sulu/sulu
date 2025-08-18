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

namespace Sulu\Content\Application\PropertyResolver;

use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;

/**
 * @internal The constructor of this class may change in future releases to add new features or improve performance.
 *           Use this service via the dependency injection container via the PropertyResolverProviderInterface only.
 *           It is still fine to use this class to create a new instance for mock-less unit tests,
 *           but no backwards compatibility promise can be given for the constructor.
 *
 * If you need to override this service use service decoration via the provided interface: https://symfony.com/doc/6.4/service_container/service_decoration.html.
 */
final class PropertyResolverProvider implements PropertyResolverProviderInterface
{
    /**
     * @var array<PropertyResolverInterface>
     */
    private array $propertyResolvers;

    /**
     * @param iterable<PropertyResolverInterface> $propertyResolvers
     */
    public function __construct(iterable $propertyResolvers)
    {
        $this->propertyResolvers = \iterator_to_array($propertyResolvers);
    }

    public function getPropertyResolver(string $type): PropertyResolverInterface
    {
        if (!\array_key_exists($type, $this->propertyResolvers)) {
            return $this->propertyResolvers['default'];
        }

        return $this->propertyResolvers[$type];
    }
}
