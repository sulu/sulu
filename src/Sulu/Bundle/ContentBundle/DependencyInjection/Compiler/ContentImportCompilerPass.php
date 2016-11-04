<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection\Compiler;

use Sulu\Component\Content\Import\Webspace;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all webspace import formats.
 */
class ContentImportCompilerPass implements CompilerPassInterface
{
    const CONTENT_IMPORT_SERVICE_ID = 'sulu_content.import.webspace';
    const IMPORT_SERVICE_TAG = 'sulu.content.import.service';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::CONTENT_IMPORT_SERVICE_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::CONTENT_IMPORT_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::IMPORT_SERVICE_TAG);
        foreach ($taggedServices as $id => $tagAttributes) {
            $this->addTagAttributes($definition, $id, $tagAttributes);
        }
    }

    /**
     * @param Webspace $definition
     * @param int $id
     * @param $tagAttributes
     */
    private function addTagAttributes(Webspace $definition, $id, $tagAttributes)
    {
        foreach ($tagAttributes as $attributes) {
            $format = $attributes['format'];
            $definition->addMethodCall('add', [new Reference($id), $format]);
        }
    }
}
