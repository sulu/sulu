<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\DependencyInjection\Compiler;

use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Get all snippets from compiler.
 */
class SnippetZoneCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $structureFactory = $container->get('sulu_content.structure.factory');
        $structures = $structureFactory->getStructures('snippet');

        $locales = $container->getParameter('sulu_core.locales');

        $defaultZones = [];
        $zones = [];

        /** @var StructureMetadata $structure */
        foreach ($structures as $structure) {
            $template = $structure->name;

            foreach ($structure->zones as $zone) {
                $key = $template . '.' . $zone['key'];

                $zones[$key] = [
                    'key' => $key,
                    'template' => $template,
                    'title' => $zone['title'],
                ];
            }

            $titles = [];

            foreach ($locales as $locale) {
                $titles[$locale] = $structure->getTitle($locale);
            }

            $defaultZones[$template] = [
                'key' => $template,
                'template' => $template,
                'title' => $titles,
            ];
        }

        if (empty($zones)) {
            $zones = $defaultZones;
        }

        $container->setParameter('sulu_snippet.default_types', $zones);
    }
}
