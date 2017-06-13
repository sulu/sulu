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
class DefaultSnippetCompilerPass implements CompilerPassInterface
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
            $template = $structure->getName();

            $templateTitles = [];
            foreach ($locales as $locale) {
                $templateTitles[$locale] = $structure->getTitle($locale);
            }

            $defaultZones[$template] = [
                'key' => $template,
                'template' => $template,
                'title' => $templateTitles,
            ];

            foreach ($structure->getZones() as $zone) {
                $zone = $this->getZone($template, $zone, $locales, $templateTitles);
                $zones[$zone['key']] = $zone;
            }
        }

        if (empty($zones)) {
            $zones = $defaultZones;
        }

        $container->setParameter('sulu_snippet.default_types', $zones);
    }

    /**
     * Get zone.
     *
     * @return array
     */
    private function getZone($template, $zone, $locales, $templateTitles)
    {
        $key = $template . '.' . $zone['key'];

        $titles = [];

        foreach ($locales as $locale) {
            $title = $templateTitles[$locale] . ' ' . ucfirst($zone['key']);
            if (isset($zone['title'][$locale])) {
                $title = $zone['title'][$locale];
            }

            $titles[$locale] = $title;
        }

        return [
            'key' => $key,
            'template' => $template,
            'title' => $titles,
        ];
    }
}
