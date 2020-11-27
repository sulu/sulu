<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectionPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SingleSelectionPropertyMetadataMapper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddPropertyMetadataMapperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $fieldTypeOptionRegistry = $container->get('sulu_admin.field_type_option_registry');
        $baseFieldTypes = $fieldTypeOptionRegistry->toArray();

        $this->registerPropertyMetadataMappers(
            $container,
            SelectionPropertyMetadataMapper::class,
            \array_keys($baseFieldTypes['selection'] ?? [])
        );

        $this->registerPropertyMetadataMappers(
            $container,
            SingleSelectionPropertyMetadataMapper::class,
            \array_keys($baseFieldTypes['single_selection'] ?? [])
        );
    }

    /**
     * @param string[] $fieldTypes
     */
    private function registerPropertyMetadataMappers(ContainerBuilder $container, string $className, array $fieldTypes)
    {
        foreach ($fieldTypes as $fieldType) {
            $definition = new Definition($className);
            $definition->addTag('sulu_admin.property_metadata_mapper', [
                'type' => $fieldType,
            ]);
            $container->setDefinition('sulu_admin.property_metadata_mapper.' . $fieldType, $definition);
        }
    }
}
