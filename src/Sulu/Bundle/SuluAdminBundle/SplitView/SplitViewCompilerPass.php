<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\SplitView;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * processes given tag and append widgets to given split view service
 * @package Sulu\Bundle\AdminBundle\SplitView
 */
class SplitViewCompilerPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $splitViewServiceId;

    /**
     * @var string
     */
    private $widgetTag;

    function __construct($splitViewServiceId, $widgetTag)
    {
        $this->splitViewServiceId = $splitViewServiceId;
        $this->widgetTag = $widgetTag;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        // return if service does not exists
        if (!$container->hasDefinition($this->splitViewServiceId)) {
            return;
        }

        // get service definition
        $splitViewService = $container->getDefinition(
            $this->splitViewServiceId
        );

        // get tagged services
        $taggedServices = $container->findTaggedServiceIds(
            $this->widgetTag
        );

        // add each widget
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $splitViewService->addMethodCall(
                    'addWidget',
                    array(new Reference($id), isset($attributes['priority']) ? $attributes['priority'] : 1)
                );
            }
        }
    }
}
