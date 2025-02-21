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
 * If you need to override this service use service decoration via the provided interface: https://symfony.com/doc/6.4/service_container/service_decoration.html.
 */
final class PropertyResolverProvider implements PropertyResolverProviderInterface
{
    /**
     * @var PropertyResolverInterface[]
     */
    private array $propertyResolvers;

    /**
     * @internal The constructor of this class maybe change in future releases. Use this service via the dependency injection container only.
     *
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
