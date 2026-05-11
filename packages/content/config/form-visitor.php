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

use Sulu\Content\Infrastructure\Sulu\Form\ExcerptFormMetadataVisitor;
use Sulu\Content\Infrastructure\Sulu\Form\InstanceOfFormMetadataVisitor;
use Sulu\Content\Infrastructure\Sulu\Form\RouteFieldSkipTemplateDataMapperFormMetadataVisitor;
use Sulu\Content\Infrastructure\Sulu\Form\SeoFormMetadataVisitor;
use Sulu\Content\Infrastructure\Sulu\Form\SettingsFormMetadataVisitor;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.settings_author_visitor', SettingsFormMetadataVisitor::class)
        ->args([new Reference('sulu_admin.xml_form_metadata_loader')])
        ->tag('sulu_admin.form_metadata_visitor');

    $services->set('sulu_content.excerpt_form_metadata_visitor', ExcerptFormMetadataVisitor::class)
        ->args([new Reference('sulu_admin.xml_form_metadata_loader')])
        ->tag('sulu_admin.form_metadata_visitor');

    $services->set('sulu_content.seo_form_metadata_visitor', SeoFormMetadataVisitor::class)
        ->args([new Reference('sulu_admin.xml_form_metadata_loader')])
        ->tag('sulu_admin.form_metadata_visitor');

    $services->set('sulu_content.excerpt_instanceof_form_metadata_visitor', InstanceOfFormMetadataVisitor::class)
        ->args([
            new Reference('sulu_admin.xml_form_metadata_loader'),
            'content_excerpt',
            '%sulu_content.content_excerpt_forms%',
        ])
        ->tag('sulu_admin.form_metadata_visitor', ['priority' => -10]);

    $services->set('sulu_content.seo_instanceof_form_metadata_visitor', InstanceOfFormMetadataVisitor::class)
        ->args([
            new Reference('sulu_admin.xml_form_metadata_loader'),
            'content_seo',
            '%sulu_content.content_seo_forms%',
        ])
        ->tag('sulu_admin.form_metadata_visitor', ['priority' => -10]);

    $services->set('sulu_content.route_field_skip_template_data_mapper_form_metadata_visitor', RouteFieldSkipTemplateDataMapperFormMetadataVisitor::class)
        ->tag('sulu_admin.typed_form_metadata_visitor');
};
