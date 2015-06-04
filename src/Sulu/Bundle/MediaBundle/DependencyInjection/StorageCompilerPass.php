<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Sulu\Bundle\MediaBundle\Media\Exception\StorageAdapterNotFoundException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Compiler pass for collecting services tagged with sulu_media.image.command
 */
class StorageCompilerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    protected $adapters = array();

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu_media.storage_manager')) {
            return;
        }

        $storageManagerDefinition = $container->getDefinition('sulu_media.storage_manager');
        $taggedServices = $container->findTaggedServiceIds('sulu_media.storage_adapter');

        $storageAdapters = $container->getParameter('sulu_media.storage.adapters');

        $this->adapters = array();
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $this->adapters[$attributes['alias']] = $id;
            }
        }

        foreach ($storageAdapters as $alias => $config) {
            // create new storage definition
            $id = $this->getStorageDefinition($container, $alias, $config);

            // add storage to manager
            $storageManagerDefinition->addMethodCall(
                'add',
                array(new Reference($id), $alias)
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param $alias
     * @param $config
     * @return string
     * @throws StorageAdapterNotFoundException
     */
    protected function getStorageDefinition(
        ContainerBuilder $container,
        $alias,
        $config
    ) {
        if (!$config['type']) {
            throw new StorageAdapterNotFoundException(sprintf('Storage adapter for "%s" not found!', $alias));
        }

        // get abstract storage
        $adapterName = $this->getAdapter($config['type']);
        unset($config['type']);
        $id = sprintf('sulu_media.%s_storage', $alias);

        // create definition by abstract adapters
        $storageDefinition = $container->setDefinition($id, new DefinitionDecorator($adapterName));

        // get reflection class to set constructor correct
        $class = new \ReflectionClass($container->getParameterBag()->resolveValue(
            $container->getDefinition($adapterName)->getClass()
        ));

        // set constructor
        foreach ($config as $name => $value) {
            foreach ($class->getMethod('__construct')->getParameters() as $key => $parameter) {
                if ($parameter->getName() == $name) {
                    $storageDefinition->replaceArgument($key, $value);
                    break;
                }
            }
        }

        return $id;
    }

    /**
     * @param $type
     * @return string
     * @throws StorageAdapterNotFoundException
     */
    protected function getAdapter($type)
    {
        if (!isset($this->adapters[$type])) {
            throw new StorageAdapterNotFoundException(sprintf('Storage adapter "%s" was not found!', $type));
        }

        return $this->adapters[$type];
    }
}
