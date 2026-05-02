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

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Sulu\Bundle\CoreBundle\Cache\StructureWarmer;
use Sulu\Bundle\CoreBundle\DataFixtures\ReplacerXmlLoader;
use Sulu\Component\Content\Compat\LocalizationFinder;
use Sulu\Component\Content\Compat\StructureManager;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\Content\Template\TemplateResolver;
use Sulu\Component\Content\Types\Block\HiddenBlockVisitor;
use Sulu\Component\Content\Types\Block\ScheduleBlockVisitor;
use Sulu\Component\Content\Types\Block\SegmentBlockVisitor;
use Sulu\Component\Content\Types\BlockContentType;
use Sulu\Component\Content\Types\Link;
use Sulu\Component\Content\Types\Metadata\GlobalBlocksTypedFormMetadataVisitor;
use Sulu\Component\Content\Types\Number;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\ResourceLocator\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPool;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\TreeFullEditStrategy;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\TreeGenerator;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\TreeLeafEditStrategy;
use Sulu\Component\Content\Types\SingleIconSelection;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextEditor;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\PathCleanup;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProvider;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\String\Slugger\AsciiSlugger;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu.content.path_cleaner.replacer_loader.file_locator.class', FileLocator::class);
    $parameters->set('sulu.content.path_cleaner.replacer_loader.class', ReplacerXmlLoader::class);
    $parameters->set('sulu.content.path_cleaner.class', PathCleanup::class);
    $parameters->set('sulu.content.template_resolver.class', TemplateResolver::class);
    $parameters->set('sulu.content.mapper.class', ContentMapper::class);
    $parameters->set('sulu.content.structure_manager.class', StructureManager::class);
    $parameters->set('sulu.content.webspace_structure_provider.class', WebspaceStructureProvider::class);
    $parameters->set('sulu.content.type_manager.class', ContentTypeManager::class);
    $parameters->set('sulu.content.type.number.class', Number::class);
    $parameters->set('sulu.content.type.text_line.class', TextLine::class);
    $parameters->set('sulu.content.type.text_area.class', TextArea::class);
    $parameters->set('sulu.content.type.text_editor.class', TextEditor::class);
    $parameters->set('sulu.content.type.resource_locator.class', ResourceLocator::class);
    $parameters->set('sulu.content.type.link.class', Link::class);
    $parameters->set('sulu.content.type.single_icon_selection.class', SingleIconSelection::class);
    $parameters->set('sulu.content.type.block.class', BlockContentType::class);
    $parameters->set('sulu.content.resource_locator.mapper.phpcr.class', PhpcrMapper::class);
    $parameters->set('sulu.content.query_executor.class', ContentQueryExecutor::class);
    $parameters->set('sulu.cache.warmer.structure.class', StructureWarmer::class);
    $parameters->set('sulu.util.node_helper.class', SuluNodeHelper::class);

    $services->set('sulu.content.slugger', AsciiSlugger::class);

    $services->set('sulu.content.path_cleaner.replacer_loader.file_locator', '%sulu.content.path_cleaner.replacer_loader.file_locator.class%')
        ->synthetic(false);

    $services->set('sulu.content.path_cleaner.replacer_loader', '%sulu.content.path_cleaner.replacer_loader.class%')
        ->synthetic(false)
        ->args([new Reference('sulu.content.path_cleaner.replacer_loader.file_locator')]);

    $services->set('sulu.content.path_cleaner', '%sulu.content.path_cleaner.class%')
        ->public()
        ->args([
            '$replacers' => [],
            '$slugger' => new Reference('sulu.content.slugger'),
        ]);

    $services->alias(PathCleanupInterface::class, 'sulu.content.path_cleaner');

    $services->set('sulu.content.template_resolver', '%sulu.content.template_resolver.class%')
        ->private();

    $services->set('sulu.content.mapper', '%sulu.content.mapper.class%')
        ->public()
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('form.factory'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            new Reference('sulu.content.type_manager'),
            new Reference('sulu.phpcr.session'),
            new Reference('event_dispatcher'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('sulu_document_manager.namespace_registry'),
            new Reference('sulu_security.access_control_manager'),
            '%sulu_security.permissions%',
            new Reference('security.helper', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu.content.type_manager', '%sulu.content.type_manager.class%')
        ->public()
        ->args([new Reference('service_container')]);

    $services->set('sulu.content.structure_manager', '%sulu.content.structure_manager.class%')
        ->public()
        ->args([
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_page.compat.structure.legacy_property_factory'),
            '%sulu.content.structure.type_map%',
        ]);

    $services->set('sulu.content.webspace_structure_provider.cache_adapter', FilesystemAdapter::class)
        ->args([
            '',
            0,
            '%sulu.cache_dir%/webspace_structures',
        ]);

    $services->set('sulu.content.webspace_structure_provider.cache', CacheProvider::class)
        ->args([new Reference('sulu.content.webspace_structure_provider.cache_adapter')])
        ->factory([DoctrineProvider::class, 'wrap']);

    $services->set('sulu.content.webspace_structure_provider', '%sulu.content.webspace_structure_provider.class%')
        ->public()
        ->args([
            new Reference('twig'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu.content.webspace_structure_provider.cache'),
        ]);

    $services->set('sulu.content.resource_locator.mapper.phpcr', '%sulu.content.resource_locator.mapper.phpcr.class%')
        ->private()
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
        ]);

    $services->set('sulu.content.resource_locator.strategy_pool', ResourceLocatorStrategyPool::class)
        ->args([
            tagged_iterator('sulu.resource_locator.strategy', indexAttribute: 'alias'),
            new Reference('sulu_core.webspace.webspace_manager'),
        ]);

    $services->set('sulu.content.resource_locator.strategy.tree_generator', TreeGenerator::class);

    $services->set('sulu.content.resource_locator.strategy.tree_leaf_edit', TreeLeafEditStrategy::class)
        ->args([
            new Reference('sulu.content.resource_locator.mapper.phpcr'),
            new Reference('sulu.content.path_cleaner'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu.content.type_manager'),
            new Reference('sulu.util.node_helper'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu.content.resource_locator.strategy.tree_generator'),
        ])
        ->tag('sulu.resource_locator.strategy', ['alias' => 'tree_leaf_edit']);

    $services->set('sulu.content.resource_locator.strategy.tree_full_edit', TreeFullEditStrategy::class)
        ->args([
            new Reference('sulu.content.resource_locator.mapper.phpcr'),
            new Reference('sulu.content.path_cleaner'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu.content.type_manager'),
            new Reference('sulu.util.node_helper'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu.content.resource_locator.strategy.tree_generator'),
        ])
        ->tag('sulu.resource_locator.strategy', ['alias' => 'tree_full_edit']);

    $services->alias('sulu.content.rlp.mapper.phpcr', 'sulu.content.resource_locator.mapper.phpcr');

    $services->alias('sulu.content.rlp.strategy.tree', 'sulu.content.resource_locator.strategy.tree_leaf_edit');

    $services->set('sulu.content.type.number', '%sulu.content.type.number.class%')
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'number'])
        ->tag('sulu.content.type', ['alias' => 'number'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.text_line', '%sulu.content.type.text_line.class%')
        ->tag('sulu.content.type', ['alias' => 'text_line'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => true]);

    $services->set('sulu.content.type.text_area', '%sulu.content.type.text_line.class%')
        ->tag('sulu.content.type', ['alias' => 'text_area'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => true]);

    $services->set('sulu.content.type.text_editor', '%sulu.content.type.text_editor.class%')
        ->args([new Reference('sulu_markup.parser')])
        ->tag('sulu.content.type', ['alias' => 'text_editor'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => true]);

    $services->set('sulu.content.type.resource_locator', '%sulu.content.type.resource_locator.class%')
        ->tag('sulu.content.type', ['alias' => 'resource_locator'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.link', '%sulu.content.type.link.class%')
        ->args([
            new Reference('sulu_markup.link_tag.provider_pool'),
            new Reference('sulu_website.reference_store_pool'),
        ])
        ->tag('sulu.content.type', ['alias' => 'link'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.single_icon_selection', '%sulu.content.type.single_icon_selection.class%')
        ->tag('sulu.content.type', ['alias' => 'single_icon_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => true]);

    $services->set('sulu.content.type.block', '%sulu.content.type.block.class%')
        ->args([
            new Reference('sulu.content.type_manager'),
            '%sulu.content.language.namespace%',
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_audience_targeting.target_group_store', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            tagged_iterator('sulu_content.block_visitor'),
        ])
        ->tag('sulu.content.type', ['alias' => 'block'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu.content.type.block.hidden_visitor', HiddenBlockVisitor::class)
        ->tag('sulu_content.block_visitor');

    $services->set('sulu.content.type.block.segment_visitor', SegmentBlockVisitor::class)
        ->args([new Reference('sulu_core.webspace.request_analyzer')])
        ->tag('sulu_content.block_visitor');

    $services->set('sulu.content.type.block.schedule_visitor', ScheduleBlockVisitor::class)
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_http_cache.cache_lifetime.request_store', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu_content.block_visitor');

    $services->set('sulu.content.type.block.global_block_visitor', GlobalBlocksTypedFormMetadataVisitor::class)
        ->args([new Reference('sulu_admin.metadata_provider_registry')])
        ->tag('sulu_admin.typed_form_metadata_visitor')
        ->tag('sulu_admin.form_metadata_visitor');

    $services->set('sulu.content.query_executor', '%sulu.content.query_executor.class%')
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu.content.mapper'),
            new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu.cache.warmer.structure', '%sulu.cache.warmer.structure.class%')
        ->args([new Reference('sulu.content.structure_manager')])
        ->tag('kernel.cache_warmer');

    $services->set('sulu.util.node_helper', '%sulu.util.node_helper.class%')
        ->public()
        ->args([
            new Reference('sulu_document_manager.default_session'),
            '%sulu.content.language.namespace%',
            ['base' => '%sulu.content.node_names.base%', 'content' => '%sulu.content.node_names.content%', 'route' => '%sulu.content.node_names.route%', 'snippet' => '%sulu.content.node_names.snippet%'],
            new Reference('sulu_page.structure.factory'),
        ]);

    $services->set('sulu.content.localization_finder', LocalizationFinder::class)
        ->public()
        ->args([new Reference('sulu_core.webspace.webspace_manager')]);
};
