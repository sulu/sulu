<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Symfony\CompilerPass;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Get all snippets from compiler.
 */
class SnippetAreaCompilerPass implements CompilerPassInterface
{
    public const SNIPPET_AREA_PARAM = 'sulu_snippet.areas';

    public function process(ContainerBuilder $container): void
    {
        //$structureFactory = $container->get('sulu_page.structure.factory');
        //$structures = $structureFactory->getStructures(Structure::TYPE_SNIPPET);

        $structure = new StructureMetadata();
        $structure->setAreas([
            ['key' => 'a', 'cache-invalidation' => true, 'title' => ['testing-a']],
            ['key' => 'b', 'cache-invalidation' => true, 'title' => ['testing-b']],
        ]);
        $structures[] = $structure;

        $locales = $container->getParameter('sulu_core.locales');

        $areas = [];

        /** @var StructureMetadata $structure */
        foreach ($structures as $structure) {
            $template = $structure->getName();

            $templateTitles = [];
            foreach ($locales as $locale) {
                $templateTitles[$locale] = $structure->getTitle($locale);
            }

            foreach ($structure->getAreas() as $area) {
                $areaKey = $area['key'];
                $area = $this->getArea($container, (string) $template, $area, $locales, $templateTitles);

                if (\array_key_exists($areaKey, $areas)) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Snippet area "%s" need to be unique it is defined in "%s" and "%s"',
                        $areaKey,
                        $areas[$areaKey]['template'] . '.xml',
                        $area['template'] . '.xml'
                    ));
                }

                $areas[$area['key']] = $area;
            }
        }

        \ksort($areas);

        $container->setParameter(self::SNIPPET_AREA_PARAM, $areas);
    }

    /**
     * @param array{key: string, title: array<string>, cache-invalidation: string} $area
     * @param array<string> $locales
     * @param array<string, string> $templateTitles
     *
     * @return array{key: string, template: string, title: array<string, string>}
     */
    private function getArea(
        ContainerBuilder $container,
        string $template,
        array $area,
        array $locales,
        array $templateTitles,
    ): array {
        $key = $area['key'];
        $cacheInvalidation = $area['cache-invalidation'];

        $titles = [];
        $areaTitles = $area['title'];

        // If we only have one title and no locale (indexed 0) then it's a translation key
        if (1 === \count($areaTitles) && \array_key_exists(0, $areaTitles)) {
            //$translator = $container->get('translator');
            $titleToTranslate = \reset($areaTitles);
            foreach ($locales as $locale) {
                // $titles[$locale] = $translator->trans($titleToTranslate, [], 'admin', $locale);
                $titles[$locale] = $titleToTranslate;
            }
        } else {
            foreach ($locales as $locale) {
                $titles[$locale] = $areaTitles[$locale] ?? ($templateTitles[$locale] . ' ' . \ucfirst($key));
            }
        }

        return [
            'key' => $key,
            'template' => $template,
            'title' => $titles,
            'cache-invalidation' => $cacheInvalidation,
        ];
    }
}
