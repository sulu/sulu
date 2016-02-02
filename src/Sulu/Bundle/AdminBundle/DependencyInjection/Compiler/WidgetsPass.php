<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Processes given tag and append widgets to given widgets-handler service.
 */
class WidgetsPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $widgetTag;

    public function __construct()
    {
        $this->widgetTag = 'sulu.widget';
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu_admin.widgets_handler')) {
            return;
        }
        $widgetsHandler = $container->getDefinition('sulu_admin.widgets_handler');

        // get tagged services
        $taggedServices = $container->findTaggedServiceIds(
            $this->widgetTag
        );

        // add each widget for each tag
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (array_key_exists('alias', $tagAttribute)) {
                    $widgetsHandler->addMethodCall(
                        'addWidget',
                        [new Reference($id), $tagAttribute['alias']]
                    );
                } else {
                    throw new InvalidArgumentException('A widget could not be registered.', 'alias');
                }
            }
        }
    }
}
