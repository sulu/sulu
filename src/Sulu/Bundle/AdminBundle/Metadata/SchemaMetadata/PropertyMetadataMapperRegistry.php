<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sulu\Bundle\AdminBundle\Exception\PropertyMetadataMapperNotFoundException;

class PropertyMetadataMapperRegistry
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function has(string $type): bool
    {
        return $this->locator->has($type);
    }

    public function get(string $type): PropertyMetadataMapperInterface
    {
        try {
            return $this->locator->get($type);
        } catch (NotFoundExceptionInterface $e) {
            throw new PropertyMetadataMapperNotFoundException($type);
        }
    }
}
