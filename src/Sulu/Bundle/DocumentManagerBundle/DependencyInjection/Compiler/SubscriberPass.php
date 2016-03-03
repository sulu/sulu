<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to register document manager subscribers.
 *
 * Subscriber tags may indicate to which document manager they
 * should be associated.
 */
class SubscriberPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $managers = $container->getParameter('sulu_document_manager.managers');
        $baseDispatcherId = 'sulu_document_manager.event_dispatcher';
        $dispatcherIds = [];

        foreach ($managers as $inde => $manager) {
            $dispatcherId = $baseDispatcherId . '.' . $manager;

            // TODO: Why would this happen?
            if (!$container->hasDefinition($dispatcherId) && !$container->hasAlias($dispatcherId)) {
                continue;
            }

            $dispatcherIds[] = $dispatcherId;
        }

        foreach ($container->findTaggedServiceIds('sulu_document_manager.event_subscriber') as $subscriberId => $attributes) {
            $subscriber = $container->getDefinition($subscriberId);

            if (!$subscriber->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event subscribers are lazy-loaded.', $subscriberId));
            }

            if ($subscriber->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event subscribers are lazy-loaded.', $subscriberId));
            }

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            $class = $container->getParameterBag()->resolveValue($subscriber->getClass());

            $reflClass = new \ReflectionClass($class);
            $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';

            if (!$reflClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $subscriberId, $interface));
            }

            $validKeys = ['manager'];

            // throw a good exception if the specified manager does not exist.
            if ($diff = array_diff(array_keys($attributes[0]), $validKeys)) {
                throw new \InvalidArgumentException(sprintf(
                    'Subscriber "%s" has invalid tag keys: "%s", valid keys: "%s"',
                    $subscriberId,
                    implode('", "', $diff),
                    implode('", "', $validKeys)
                ));
            }

            // build the list of event dispatcher IDs to which this subscriber should be added.
            // note that the "manager" attribute may be a comma-separated list of manager names.
            $targetDispatcherIds = [];
            if (isset($attributes[0]['manager'])) {
                $targetDispatcherIds = [];
                $documentManagerNames = array_unique(explode(',', $attributes[0]['manager']));

                foreach ($documentManagerNames as $documentManagerName) {
                    if (!in_array($documentManagerName, $managers)) {
                        throw new \InvalidArgumentException(sprintf(
                            'Unknown document manager "%s" specified for event subscriber "%s". Known document managers: "%s"',
                            $documentManagerName, $subscriberId, implode('", "', $managers)
                        ));
                    }

                    $dispatcherId = $baseDispatcherId . '.' . $documentManagerName;
                    $targetDispatcherIds[] = $dispatcherId;
                }
            } else {
                $targetDispatcherIds = $dispatcherIds;
            }

            foreach ($targetDispatcherIds as $dispatcherId) {
                $dispatcher = $container->findDefinition($dispatcherId);
                $dispatcher->addMethodCall('addSubscriberService', [$subscriberId, $class]);
            }
        }
    }
}
