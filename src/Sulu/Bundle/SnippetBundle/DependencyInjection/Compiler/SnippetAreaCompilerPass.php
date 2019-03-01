<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
class SnippetAreaCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $structureFactory = $container->get('sulu_content.structure.factory');
        $structures = $structureFactory->getStructures('snippet');

        $locales = $container->getParameter('sulu_core.locales');

        $defaultAreas = [];
        $areas = [];

        /** @var StructureMetadata $structure */
        foreach ($structures as $structure) {
            $template = $structure->getName();

            $templateTitles = [];
            foreach ($locales as $locale) {
                $templateTitles[$locale] = $structure->getTitle($locale);
            }

            $defaultAreas[$template] = [
                'key' => $template,
                'template' => $template,
                'title' => $templateTitles,
            ];

            foreach ($structure->getAreas() as $area) {
                $area = $this->getArea($template, $area, $locales, $templateTitles);

                if (isset($areas[$area['key']])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Snippet area "%s" need to be unique it is defined in "%s" and "%s"',
                        $area['key'],
                        $areas[$area['key']]['template'] . '.xml',
                        $area['template'] . '.xml'
                    ));
                }

                $areas[$area['key']] = $area;
            }
        }

        if (empty($areas)) {
            $areas = $defaultAreas;
        }

        $container->setParameter('sulu_snippet.areas', $areas);
    }

    /**
     * Get area.
     *
     * @return array
     */
    private function getArea($template, $area, $locales, $templateTitles)
    {
        $key = $area['key'];

        $titles = [];

        foreach ($locales as $locale) {
            $title = $templateTitles[$locale] . ' ' . ucfirst($key);
            if (isset($area['title'][$locale])) {
                $title = $area['title'][$locale];
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
