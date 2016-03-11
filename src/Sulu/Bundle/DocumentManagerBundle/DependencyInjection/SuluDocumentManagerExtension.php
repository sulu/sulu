<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class SuluDocumentManagerExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'directories' => [
                            [
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\DocumentManager',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            'Sulu\Component\DocumentManager\Exception\DocumentNotFoundException' => 404,
                            'Sulu\Component\Content\Exception\MandatoryPropertyException' => 400,
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('core.xml');
        $loader->load('behaviors.xml');
        $loader->load('serializer.xml');
        $loader->load('command.xml');
        $loader->load('data_fixtures.xml');
        $this->configureDocumentManagers($config, $container);
    }

    private function configureDocumentManagers(array $config, ContainerBuilder $container)
    {
        // set the default document manager
        $defaultManager = $config['default_manager'];
        $container->setParameter('sulu_document_manager.default_manager', $defaultManager);

        // let the container know what the manager names are (required for
        // example in the SubscriberPass).
        $container->setParameter('sulu_document_manager.managers', array_keys($config['managers']));

        // create the document manager services.
        $managerMap = [];
        $debug = $config['debug'];
        foreach ($config['managers'] as $name => $manager) {
            // create a concrete event dispatcher for the document manager from
            // the abstract service.  choose either the debug or "standard"
            // dispatcher based on the "debug" flag.
            $abstractDispatcherId = $debug ? 'sulu_document_manager.abstract_event_dispatcher.debug' : 'sulu_document_manager.abstract_event_dispatcher.standard';
            $dispatcherId = sprintf('sulu_document_manager.event_dispatcher.%s', $name);
            $dispatcherDef = new DefinitionDecorator($abstractDispatcherId);
            $dispatcherDef->setPublic(false);
            $container->setDefinition($dispatcherId, $dispatcherDef);

            // create the concrete document manager instance from the abstract
            // service using the correct PHPCR session and the event dispatcher
            // defined above.
            $phpcrSessionId = sprintf('doctrine_phpcr.%s_session', $manager['session']);
            $managerId = sprintf('sulu_document_manager.document_manager.%s', $name);
            $managerDef = new DefinitionDecorator('sulu_document_manager.abstract_document_manager');
            $managerDef->replaceArgument(0, new Reference($phpcrSessionId));
            $managerDef->replaceArgument(1, new Reference($dispatcherId));
            $container->setDefinition($managerId, $managerDef);
            $managerMap[$name] = $managerId;
        }

        // set the document manager service map on the document manager registry.
        $registryDef = $container->getDefinition('sulu_document_manager.registry');
        $registryDef->replaceArgument(1, $managerMap);

        // create aliases to the default services.
        $container->setAlias('sulu_document_manager.document_manager', 'sulu_document_manager.document_manager.' . $defaultManager);
        $container->setAlias('sulu_document_manager.event_dispatcher', 'sulu_document_manager.event_dispatcher.' . $defaultManager);

        // set the metadata mapping configuration into the container (it is then
        // subsequently used by the MetadataSubscriber).
        //
        // NOTE: It would potentially be cleaner to directly set these configuration
        //       values on the service definition(s) themselves.
        $realMapping = [];
        foreach ($config['mapping'] as $alias => $mapping) {
            $realMapping[] = array_merge([
                'alias' => $alias,
            ], $mapping);
        }
        $container->setParameter('sulu_document_manager.mapping', $realMapping);
        $container->setParameter('sulu_document_manager.namespace_mapping', $config['namespace']);
    }
}
