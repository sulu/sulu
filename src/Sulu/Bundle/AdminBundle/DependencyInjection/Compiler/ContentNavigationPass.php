<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add all services with given tag to the bundle content navigation
 *
 * @package Sulu\Bundle\AdminBundle\DependencyInjection\Compiler
 */
abstract class ContentNavigationPass implements CompilerPassInterface
{

    protected static $tag = null;
    protected static $serviceName = null;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (null !== self::$tag && null !== self::$serviceName) {
            $contentNavigation = $container->getDefinition(self::$serviceName);

            $taggedServices = $container->findTaggedServiceIds(self::$tag);

            foreach ($taggedServices as $id => $attributes) {
                /** @var ContentNavigationInterface $navigation */
                $navigation = $container->getDefinition($id);

                $contentNavigation->addMethodCall('addNavigation', array($navigation));
            }
        }
    }
}
