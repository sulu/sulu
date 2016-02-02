<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects all the content navigations from all bundles using a specific alias.
 */
class ContentNavigationRegistry extends ContainerAware implements ContentNavigationRegistryInterface
{
    /**
     * @var array
     */
    private $providers = [];

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems($alias, array $options = [])
    {
        if (!array_key_exists($alias, $this->providers)) {
            throw new ContentNavigationAliasNotFoundException($alias, array_keys($this->providers));
        }

        $navigationItems = [];

        foreach ($this->providers[$alias] as $providerId) {
            $navigationItems = array_merge(
                $navigationItems,
                $this->container->get($providerId)->getNavigationItems($options)
            );
        }

        usort(
            $navigationItems,
            function (ContentNavigationItem $a, ContentNavigationItem $b) {
                $aPosition = $a->getPosition() ?: PHP_INT_MAX;
                $bPosition = $b->getPosition() ?: PHP_INT_MAX;

                return $aPosition - $bPosition;
            }
        );

        return $navigationItems;
    }

    /**
     * {@inheritdoc}
     */
    public function addContentNavigationProvider($alias, $id)
    {
        if (!array_key_exists($alias, $this->providers)) {
            $this->providers[$alias] = [];
        }

        $this->providers[$alias][] = $id;
    }
}
