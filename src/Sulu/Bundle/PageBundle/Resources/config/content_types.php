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

use Sulu\Bundle\PageBundle\Content\Types\Checkbox;
use Sulu\Bundle\PageBundle\Content\Types\Color;
use Sulu\Bundle\PageBundle\Content\Types\Date;
use Sulu\Bundle\PageBundle\Content\Types\DateTime;
use Sulu\Bundle\PageBundle\Content\Types\Email;
use Sulu\Bundle\PageBundle\Content\Types\PageSelection;
use Sulu\Bundle\PageBundle\Content\Types\Password;
use Sulu\Bundle\PageBundle\Content\Types\Phone;
use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
use Sulu\Bundle\PageBundle\Content\Types\Select;
use Sulu\Bundle\PageBundle\Content\Types\SinglePageSelection;
use Sulu\Bundle\PageBundle\Content\Types\SingleSelect;
use Sulu\Bundle\PageBundle\Content\Types\Time;
use Sulu\Bundle\PageBundle\Content\Types\Url;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu.content.type.page_selection', PageSelection::class)
        ->args([
            new Reference('sulu.content.query_executor'),
            new Reference('sulu_page.smart_content.data_provider.content.query_builder'),
            new Reference('sulu_page.reference_store.content'),
            '%sulu_document_manager.show_drafts%',
            '%sulu_security.permissions%',
            '%sulu_website.enabled_twig_attributes%',
        ])
        ->tag('sulu.content.type', ['alias' => 'page_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.single_page_selection', SinglePageSelection::class)
        ->args([new Reference('sulu_page.reference_store.content')])
        ->tag('sulu.content.type', ['alias' => 'single_page_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.phone', Phone::class)
        ->tag('sulu.content.type', ['alias' => 'phone'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.segment_select', SegmentSelect::class)
        ->tag('sulu.content.type', ['alias' => 'segment_select']);

    $services->set('sulu.content.type.password', Password::class)
        ->tag('sulu.content.type', ['alias' => 'password'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.url', Url::class)
        ->tag('sulu.content.type', ['alias' => 'url'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.email', Email::class)
        ->tag('sulu.content.type', ['alias' => 'email'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'email']);

    $services->set('sulu.content.type.date', Date::class)
        ->tag('sulu.content.type', ['alias' => 'date'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.datetime', DateTime::class)
        ->tag('sulu.content.type', ['alias' => 'datetime'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.time', Time::class)
        ->tag('sulu.content.type', ['alias' => 'time'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.color', Color::class)
        ->tag('sulu.content.type', ['alias' => 'color'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.checkbox', Checkbox::class)
        ->tag('sulu.content.type', ['alias' => 'checkbox'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.select', Select::class)
        ->tag('sulu.content.type', ['alias' => 'select'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'select']);

    $services->set('sulu.content.type.single_select', SingleSelect::class)
        ->tag('sulu.content.type', ['alias' => 'single_select'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);
};
