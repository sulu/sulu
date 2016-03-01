<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge;

use Sulu\Component\DocumentManager\DocumentManagerRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Container based document manager registry.
 */
class DocumentManagerRegistry implements DocumentManagerRegistryInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $managers;

    /**
     * @var string
     */
    private $defaultName;

    /**
     * @param ContainerInterface $container Dependency injection container.
     * @param array $managers Associative array of manager names => service IDs
     * @param string Default document manager name.
     */
    public function __construct(
        ContainerInterface $container,
        $managers,
        $defaultName
    ) {
        $this->container = $container;
        $this->managers = $managers;
        $this->defaultName = $defaultName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultManagerName()
    {
        return $this->defaultName;
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerNames()
    {
        return array_keys($this->managers);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager($name = null)
    {
        $name = $name ?: $this->defaultName;

        if (!isset($this->managers[$name])) {
            throw new \RuntimeException(sprintf(
                'Manager with name "%s" does not exist. Valid manager names: "%s"',
                $name, implode('", "', array_keys($this->managers))
            ));
        }

        return $this->container->get($this->managers[$name]);
    }
}
