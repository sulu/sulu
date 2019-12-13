<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\DependencyInjection;

use Sulu\Bundle\RouteBundle\Content\Type\PageTreeRouteContentType;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PageTreeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $structureFactory = $container->get('sulu_page.structure.factory');

        $pageTreeEnabled = false;

        foreach ($structureFactory->getStructureTypes() as $structureType) {
            foreach ($structureFactory->getStructures($structureType) as $metadata) {
                if ($this->hasPageTree($metadata->getProperties())) {
                    $pageTreeEnabled = true;

                    break;
                }
            }
        }

        $container->setParameter('sulu_route.page_tree_enabled', $pageTreeEnabled);
    }

    /**
     * @param PropertyMetadata[] $properties
     *
     * @return bool
     */
    protected function hasPageTree(array $properties)
    {
        foreach ($properties as $property) {
            if (PageTreeRouteContentType::NAME === $property->getType()) {
                return true;
            } elseif ($property instanceof BlockMetadata && $this->hasPageTree($property->getChildren())) {
                return true;
            }
        }

        return false;
    }
}
