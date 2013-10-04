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
 * Add all services with the tag "sulu.contact" to the the content list
 *
 * @package Sulu\Bundle\AdminBundle\DependencyInjection\Compiler
 */
class AddContentPass implements CompilerPassInterface
{

    const ADMIN_TAG = 'sulu.contact';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $pool = $container->getDefinition('sulu_contact.content_pool');

        $taggedServices = $container->findTaggedServiceIds(self::ADMIN_TAG);


        foreach ($taggedServices as $id => $attributes) {
            $content = $container->getDefinition($id);
            $pool->addMethodCall('addContent', array($content));
        }
    }
}
