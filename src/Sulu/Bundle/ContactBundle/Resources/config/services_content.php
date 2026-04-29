<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver\AccountSelectionPropertyResolver;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver\ContactAccountSelectionPropertyResolver;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver\ContactSelectionPropertyResolver;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleAccountSelectionPropertyResolver;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleContactSelectionPropertyResolver;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\AccountResourceLoader;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\ContactResourceLoader;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_contact.account_selection_property_resolver', AccountSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_contact.single_account_selection_property_resolver', SingleAccountSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_contact.contact_selection_property_resolver', ContactSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_contact.single_contact_selection_property_resolver', SingleContactSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_contact.account_contact_selection_property_resolver', ContactAccountSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_contact.contact_resource_loader', ContactResourceLoader::class)
        ->args([new Reference('sulu_contact.contact_manager')])
        ->tag('sulu_content.resource_loader', ['key' => 'contact']);

    $services->set('sulu_contact.account_resource_loader', AccountResourceLoader::class)
        ->args([new Reference('sulu_contact.account_manager')])
        ->tag('sulu_content.resource_loader', ['key' => 'account']);
};
