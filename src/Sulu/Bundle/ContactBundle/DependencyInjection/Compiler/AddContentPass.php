<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add all services with the tag "sulu.contact.content_navigation" to the the content navigation
 *
 * @package Sulu\Bundle\AdminBundle\DependencyInjection\Compiler
 */
class AddContentPass implements CompilerPassInterface
{

    const CONTENT_NAVIGATION_TAG = 'sulu.contact.admin.content_navigation';
    const CONTENT_NAVIGATION_SERVICE = 'sulu_contact.admin.content_navigation';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $contentNavigation = $container->getDefinition(self::CONTENT_NAVIGATION_SERVICE);

        $taggedServices = $container->findTaggedServiceIds(self::CONTENT_NAVIGATION_TAG);

        foreach ($taggedServices as $id => $attributes) {
            $navigation = $container->getDefinition($id);
            $contentNavigation->addMethodCall('addNavigationItem', array($navigation));
        }
    }
}
