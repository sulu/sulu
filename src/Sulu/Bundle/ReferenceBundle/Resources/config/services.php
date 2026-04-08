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
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Doctrine\Repository\ReferenceRepository;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactory;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactoryInterface;
use Sulu\Bundle\ReferenceBundle\UserInterface\Command\RefreshCommand;
use Sulu\Bundle\ReferenceBundle\UserInterface\Controller\Admin\ReferenceController;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference(SecurityCheckerInterface::class),
        ]);

    $services->alias(ReferenceViewBuilderFactoryInterface::class, 'sulu_reference.reference_list_view_builder_factory');

    // Controller
    $services->set('sulu_reference.reference_controller', ReferenceController::class)
        ->public()
        ->args([
            new Reference(ReferenceRepositoryInterface::class),
            new Reference(SecurityCheckerInterface::class),
            new Reference(ViewHandlerInterface::class),
            new Reference(TokenStorageInterface::class),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    // Command
    $services->set('sulu_reference.refresh_command', RefreshCommand::class)
        ->args([
            new TaggedIteratorArgument('sulu_reference.refresher', defaultIndexMethod: 'getResourceKey'),
            new Reference(ReferenceRepositoryInterface::class),
            '%sulu.context%',
        ])
        ->tag('console.command');
};
