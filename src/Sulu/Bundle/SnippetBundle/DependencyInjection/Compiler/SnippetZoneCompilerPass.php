<?php

namespace Sulu\Bundle\SnippetBundle\DependencyInjection\Compiler;

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

        $defaultZones = [];
        $zones = [];

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

            $defaultZones[$template] = [
                'key' => $template,
                'template' => $template,
                'title' => $structure->title,
            ];
        }

        if (empty($zones)) {
            $zones = $defaultZones;
        }

        $container->setParameter('sulu_snippet.default_types', $zones);
    }
}
