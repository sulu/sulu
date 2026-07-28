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

use Sulu\Content\Application\ContentDataMapper\DataMapper\AuthorDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\ExcerptDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\LinkDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\RoutableDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\SeoDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\ShadowDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\TableTemplateDataMapper;
use Sulu\Content\Domain\Table\TableTemplateDataNormalizer;
use Sulu\Content\Application\ContentDataMapper\DataMapper\TaxonomyDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\TemplateDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\WebspaceDataMapper;
use Sulu\Content\Application\ContentDataMapper\DataMapper\WorkflowDataMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.template_data_mapper', TemplateDataMapper::class)
        ->args([new Reference('sulu_admin.metadata_provider_registry')])
        ->tag('sulu_content.data_mapper', ['priority' => 128]);

    $services->set('sulu_content.table_template_data_normalizer', TableTemplateDataNormalizer::class);

    $services->set('sulu_content.table_template_data_mapper', TableTemplateDataMapper::class)
        ->args([
            new Reference('sulu_admin.metadata_provider_registry'),
            new Reference('sulu_content.table_template_data_normalizer'),
        ])
        ->tag('sulu_content.data_mapper', ['priority' => 120]);

    $services->set('sulu_content.excerpt_data_mapper', ExcerptDataMapper::class)
        ->args([new Reference('sulu_admin.form_metadata_provider')])
        ->tag('sulu_content.data_mapper', ['priority' => 64]);

    $services->set('sulu_content.taxonomy_data_mapper', TaxonomyDataMapper::class)
        ->args([
            new Reference('sulu.repository.tag'),
            new Reference('sulu_content.category_factory'),
            new Reference('sulu_audience_targeting.target_group_factory', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu_content.data_mapper', ['priority' => 63]);

    $services->set('sulu_content.seo_data_mapper', SeoDataMapper::class)
        ->args([new Reference('sulu_admin.form_metadata_provider')])
        ->tag('sulu_content.data_mapper', ['priority' => 32]);

    $services->set('sulu_content.workflow_data_mapper', WorkflowDataMapper::class)
        ->args([new Reference('sulu_content.content_workflow')])
        ->tag('sulu_content.data_mapper', ['priority' => 24]);

    $services->set('sulu_content.webspace_data_mapper', WebspaceDataMapper::class)
        ->args([new Reference('sulu_core.webspace.webspace_manager')])
        ->tag('sulu_content.data_mapper', ['priority' => 16]);

    $services->set('sulu_content.author_data_mapper', AuthorDataMapper::class)
        ->args([new Reference('sulu_content.contact_factory')])
        ->tag('sulu_content.data_mapper', ['priority' => 8]);

    $services->set('sulu_content.shadow_data_mapper', ShadowDataMapper::class)
        ->tag('sulu_content.data_mapper', ['priority' => 4]);

    $services->set('sulu_content.link_data_mapper', LinkDataMapper::class)
        ->tag('sulu_content.data_mapper', ['priority' => 2]);

    $services->set('sulu_content.route_data_mapper', RoutableDataMapper::class)
        ->args([
            new Reference('sulu_route.route_repository'),
            new Reference('sulu_admin.metadata_provider_registry'),
        ])
        ->tag('sulu_content.data_mapper', ['priority' => -32]);
};
