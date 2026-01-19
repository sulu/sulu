<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ReferenceBundle\Application\MessageHandler\RefreshReferenceMessageHandler;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Doctrine\Repository\ReferenceRepository;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactory;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactoryInterface;
use Sulu\Bundle\ReferenceBundle\UserInterface\Command\RefreshCommand;
use Sulu\Bundle\ReferenceBundle\UserInterface\Controller\Admin\ReferenceController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    // Repository
    $services->set('sulu_reference.reference_repository', ReferenceRepository::class)
        ->args([new Reference(EntityManagerInterface::class)]);

    $services->alias(ReferenceRepositoryInterface::class, 'sulu_reference.reference_repository');

    // Admin
    $services->set('sulu_reference.reference_admin', ReferenceAdmin::class)
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_reference.reference_list_view_builder_factory', ReferenceViewBuilderFactory::class)
        ->args([
            new Reference('sulu_admin.view_builder_factory'),
            new Reference('sulu_security.security_checker'),
        ]);

    $services->alias(ReferenceViewBuilderFactoryInterface::class, 'sulu_reference.reference_list_view_builder_factory');

    // Controller
    $services->set('sulu_reference.reference_controller', ReferenceController::class)
        ->public()
        ->args([
            new Reference(ReferenceRepositoryInterface::class),
            new Reference('sulu_security.security_checker'),
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    // Command
    $services->set('sulu_reference.refresh_command', RefreshCommand::class)
        ->args([
            tagged_iterator('sulu_reference.refresher', defaultIndexMethod: 'getResourceKey'),
            new Reference(ReferenceRepositoryInterface::class),
            '%sulu.context%',
        ])
        ->tag('console.command');
};
