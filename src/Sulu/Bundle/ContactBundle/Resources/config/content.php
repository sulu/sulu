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

use Sulu\Bundle\ContactBundle\Content\Types\AccountSelection;
use Sulu\Bundle\ContactBundle\Content\Types\ContactAccountSelection;
use Sulu\Bundle\ContactBundle\Content\Types\ContactSelection;
use Sulu\Bundle\ContactBundle\Content\Types\SingleAccountSelection;
use Sulu\Bundle\ContactBundle\Content\Types\SingleContactSelection;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_contact.content.contact_account_selection', ContactAccountSelection::class)
        ->args([
            new Reference('sulu_contact.contact_manager'),
            new Reference('sulu_contact.account_manager'),
            new Reference('sulu_core.array_serializer'),
            new Reference('sulu_contact.util.id_converter'),
            new Reference('sulu_contact.util.index_comparator'),
            new Reference('sulu_contact.reference_store.account'),
            new Reference('sulu_contact.reference_store.contact'),
        ])
        ->tag('sulu.content.type', ['alias' => 'contact_account_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_contact.content.single_contact_selection', SingleContactSelection::class)
        ->args([
            new Reference('sulu.repository.contact'),
            new Reference('sulu_contact.reference_store.contact'),
        ])
        ->tag('sulu.content.type', ['alias' => 'single_contact_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_contact.content.contact_selection', ContactSelection::class)
        ->args([
            new Reference('sulu.repository.contact'),
            new Reference('sulu_contact.reference_store.contact'),
        ])
        ->tag('sulu.content.type', ['alias' => 'contact_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_contact.content.single_account_selection', SingleAccountSelection::class)
        ->args([
            new Reference('sulu_contact.account_manager'),
            new Reference('sulu_contact.reference_store.account'),
        ])
        ->tag('sulu.content.type', ['alias' => 'single_account_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_contact.content.account_selection', AccountSelection::class)
        ->args([
            new Reference('sulu_contact.account_manager'),
            new Reference('sulu_contact.reference_store.account'),
        ])
        ->tag('sulu.content.type', ['alias' => 'account_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);
};
