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

use Sulu\Bundle\DocumentManagerBundle\Session\Session;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class SuluDocumentManagerExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $preview = $container->hasParameter('sulu.preview') ? $container->getParameter('sulu.preview') : false;
        $context = $container->getParameter('sulu.context');

        $configs = $container->getExtensionConfig($this->getAlias());
        $parameterBag = $container->getParameterBag();
        $configs = $parameterBag->resolveValue($configs);
        $config = $this->processConfiguration(new Configuration(), $configs);

        // FIXME: The entire foreach can be removed when upgrading to DoctrinePhpcrBundle 1.3
        // see https://github.com/doctrine/DoctrinePHPCRBundle/issues/178
        foreach ($config['sessions'] as &$session) {
            if (isset($session['backend'])) {
                $session['backend']['check_login_on_server'] = false;
            }
        }

        $liveSession = 'live';
        if (isset($config['live_session'])) {
            $liveSession = $config['live_session'];
        }

        $defaultSession = 'default';
        if (!$preview && isset($config['default_session'])) {
            $defaultSession = $config['default_session'];
        }

        if (!$preview && SuluKernel::CONTEXT_WEBSITE === $context) {
            $defaultSession = $liveSession;
        }

        $container->prependExtensionConfig(
            'sulu_document_manager',
            [
                'default_session' => $defaultSession,
                'live_session' => $liveSession,
            ]
        );

        if ($container->hasExtension('doctrine_phpcr')) {
            $doctrinePhpcrConfig = [
                'session' => [
                    'sessions' => $config['sessions'],
                ],
            ];

            $doctrinePhpcrConfig['session']['default_session'] = $defaultSession;

            $container->prependExtensionConfig(
                'doctrine_phpcr',
                $doctrinePhpcrConfig
            );
        }

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
                            'Sulu\Component\DocumentManager\Exception\VersionNotFoundException' => 404,
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

        $this->configureDocumentManager($config, $container);
        $this->configurePathSegmentRegistry($config, $container);

        $loader->load('admin.xml');
        $loader->load('core.xml');
        $loader->load('behaviors.xml');
        $loader->load('serializer.xml');
        $loader->load('command.xml');
        $loader->load('data_fixtures.xml');
        $loader->load('routing.xml');

        if ($config['versioning']['enabled']) {
            $loader->load('versioning.xml');
        }
    }

    private function configureDocumentManager($config, ContainerBuilder $container)
    {
        $debug = $config['debug'];

        $dispatcherId = $debug ? 'sulu_document_manager.event_dispatcher.debug' : 'sulu_document_manager.event_dispatcher.standard';
        $container->setAlias('sulu_document_manager.event_dispatcher', $dispatcherId);

        $realMapping = [];
        foreach ($config['mapping'] as $alias => $mapping) {
            $realMapping[] = array_merge([
                'alias' => $alias,
            ], $mapping);
        }
        $container->setParameter('sulu_document_manager.mapping', $realMapping);
        $container->setParameter('sulu_document_manager.namespace_mapping', $config['namespace']);
        $container->setParameter('sulu_document_manager.versioning.enabled', $config['versioning']['enabled']);

        $defaultSessionId = $this->getSessionServiceId($config['default_session']);
        $container->setAlias(
            'sulu_document_manager.default_session',
            $defaultSessionId
        );

        $defaultSessionDefinition = new Definition(Session::class, [new Reference('sulu_document_manager.decorated_default_session.inner')]);
        $defaultSessionDefinition->setDecoratedService($defaultSessionId);
        $container->setDefinition('sulu_document_manager.decorated_default_session', $defaultSessionDefinition);

        $liveSessionId = $this->getSessionServiceId($config['live_session']);
        $container->setAlias(
            'sulu_document_manager.live_session',
            $liveSessionId
        );

        $liveSessionDefinition = new Definition(Session::class, [new Reference('sulu_document_manager.decorated_live_session.inner')]);
        $liveSessionDefinition->setDecoratedService($liveSessionId);
        $container->setDefinition('sulu_document_manager.decorated_live_session', $liveSessionDefinition);

        $container->setParameter(
            'sulu_document_manager.show_drafts',
            SuluKernel::CONTEXT_ADMIN === $container->getParameter('sulu.context')
            || ($container->hasParameter('sulu.preview') && $container->getParameter('sulu.preview'))
        );

        $container->setParameter(
            'sulu_document_manager.show_drafts',
            SuluKernel::CONTEXT_ADMIN === $container->getParameter('sulu.context')
            || ($container->hasParameter('sulu.preview') && $container->getParameter('sulu.preview'))
        );
    }

    private function configurePathSegmentRegistry($config, ContainerBuilder $container)
    {
        $pathSegments = array_merge(
            $config['path_segments'],
            [
                'base' => $container->getParameter('sulu.content.node_names.base'),
                'content' => $container->getParameter('sulu.content.node_names.content'),
                'route' => $container->getParameter('sulu.content.node_names.route'),
                'snippet' => $container->getParameter('sulu.content.node_names.snippet'),
            ]
        );

        $container->setParameter('sulu_document_manager.segments', $pathSegments);
    }

    /**
     * Returns the service id for the given session.
     *
     * @param string $session The name of the session
     *
     * @return string
     */
    private function getSessionServiceId($session)
    {
        return sprintf('doctrine_phpcr.%s_session', $session);
    }
}
