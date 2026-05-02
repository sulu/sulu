<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelper;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManager;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Bundle\TrashBundle\Infrastructure\Doctrine\Repository\TrashItemRepository;
use Sulu\Bundle\TrashBundle\Infrastructure\Sulu\Admin\TrashAdmin;
use Sulu\Bundle\TrashBundle\UserInterface\Controller\Admin\TrashItemController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    // Repository
    $services->set('sulu_trash.trash_item_repository', TrashItemRepository::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->alias(TrashItemRepositoryInterface::class, 'sulu_trash.trash_item_repository');

    // TrashManager
    $services->set('sulu_trash.trash_manager', TrashManager::class)
        ->args([
            new Reference('sulu_trash.trash_item_repository'),
            new Reference('sulu_activity.domain_event_collector'),
            tagged_locator('sulu_trash.store_trash_item_handler', indexAttribute: 'resourceKey', defaultIndexMethod: 'getResourceKey'),
            tagged_locator('sulu_trash.restore_trash_item_handler', indexAttribute: 'resourceKey', defaultIndexMethod: 'getResourceKey'),
            tagged_locator('sulu_trash.remove_trash_item_handler', indexAttribute: 'resourceKey', defaultIndexMethod: 'getResourceKey'),
        ]);

    $services->alias(TrashManagerInterface::class, 'sulu_trash.trash_manager');

    // DoctrineRestoreHelper
    $services->set('sulu_trash.doctrine_restore_helper', DoctrineRestoreHelper::class)
        ->args([new Reference('doctrine.orm.entity_manager')]);

    $services->alias(DoctrineRestoreHelperInterface::class, 'sulu_trash.doctrine_restore_helper');

    // Admin
    $services->set('sulu_trash.trash_admin', TrashAdmin::class)
        ->args([
            new Reference('sulu_admin.view_builder_factory'),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu.core.localization_manager'),
            tagged_iterator('sulu_trash.restore_configuration_provider', indexAttribute: 'resourceKey', defaultIndexMethod: 'getResourceKey'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    // Controller
    $services->set('sulu_trash.trash_item_controller', TrashItemController::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('translator'),
            new Reference('sulu_trash.trash_manager'),
            new Reference('sulu_trash.trash_item_repository'),
            new Reference('sulu_security.security_checker'),
            tagged_locator('sulu_trash.restore_configuration_provider', indexAttribute: 'resourceKey', defaultIndexMethod: 'getResourceKey'),
            '%sulu.model.trash_item.class%',
            '%sulu_security.permissions%',
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
