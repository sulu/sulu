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

use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Application\BlockIdGenerator\BlockIdGenerator;
use Sulu\Bundle\AdminBundle\Application\BlockIdGenerator\BlockIdGeneratorInterface;
use Sulu\Bundle\AdminBundle\Command\DownloadBuildCommand;
use Sulu\Bundle\AdminBundle\Command\DownloadLanguageCommand;
use Sulu\Bundle\AdminBundle\Command\InfoCommand;
use Sulu\Bundle\AdminBundle\Command\UpdateBuildCommand;
use Sulu\Bundle\AdminBundle\Command\ValidateBuildCommand;
use Sulu\Bundle\AdminBundle\Command\ViewDebugCommand;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\Controller\BlockIdController;
use Sulu\Bundle\AdminBundle\Controller\CollaborationController;
use Sulu\Bundle\AdminBundle\Controller\IconController;
use Sulu\Bundle\AdminBundle\Controller\SmartContentItemController;
use Sulu\Bundle\AdminBundle\Controller\TeaserController;
use Sulu\Bundle\AdminBundle\Entity\CollaborationRepository;
use Sulu\Bundle\AdminBundle\ExpressionLanguage\ContainerExpressionLanguageProvider;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistry;
use Sulu\Bundle\AdminBundle\Icon\providers\IcomoonProvider;
use Sulu\Bundle\AdminBundle\Icon\providers\SvgProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CachedFormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ExpressionFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\GlobalBlocksTypedFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\MetaXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\PropertiesXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\SchemaXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TagXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TemplateXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagFilterTypedFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateFilterTypedFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\BlockFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\ChainFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\TypesPropertyFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\BlockIdGeneratorFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\BlockSettingsFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\LocalesOptionFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlTemplateFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\GroupProvider;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ExpressionListMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\XmlListMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\BlockPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\EmailPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\NumberPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\SelectionPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\SelectPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\SingleSelectionPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\TeaserSelectionPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper\TextPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\AdminBundle\Serializer\Handler\SchemaHandler;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\DropdownToolbarActionSubscriber;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\MetadataSubscriber;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\SaveWithFormDialogToolbarActionSubscriber;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\TogglerToolbarActionSubscriber;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentQueryEnhancer;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderPool;
use Sulu\Bundle\AdminBundle\Teaser\TeaserManager;
use Sulu\Bundle\AdminBundle\Teaser\TeaserTagPropertyExtractor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_admin.admin_controller', AdminController::class)
        ->public()
        ->args([
            new Reference('router'),
            new Reference('security.token_storage'),
            new Reference('sulu_admin.admin_pool'),
            new Reference('jms_serializer'),
            new Reference('fos_rest.view_handler'),
            new Reference('twig'),
            new Reference('translator.default'),
            new Reference('sulu_admin.metadata_provider_registry'),
            new Reference('sulu_admin.view_registry'),
            new Reference('sulu_admin.navigation_registry'),
            new Reference('sulu_admin.field_type_option_registry'),
            new Reference('sulu_contact.contact_manager'),
            tagged_iterator('sulu_content.smart_content_provider', indexAttribute: 'type', defaultIndexMethod: 'getType'),
            new Reference('sulu_markup.link_tag.provider_pool'),
            new Reference('sulu.core.localization_manager'),
            '%kernel.environment%',
            '%sulu.version%',
            '%app.version%',
            '%sulu_admin.resources%',
            '%sulu_core.locales%',
            '%sulu_core.translations%',
            '%sulu_core.fallback_locale%',
            '%sulu_admin.collaboration_interval%',
            '%sulu_admin.collaboration_enabled%',
            '%sulu_security.password_policy_pattern%',
            '%sulu_security.password_policy_info_translation_key%',
            '%sulu_security.has_single_sign_on_providers%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.admin_pool', AdminPool::class)
        ->public()
        ->args([tagged_iterator('sulu.admin', defaultPriorityMethod: 'getPriority')])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.metadata_provider_registry', MetadataProviderRegistry::class)
        ->args([tagged_locator('sulu_admin.metadata_provider', indexAttribute: 'type')]);

    $services->set('sulu_admin.metadata_group_provider', GroupProvider::class)
        ->args([
            new Reference('sulu_admin.metadata_provider_registry'),
            new Reference('translator'),
        ]);

    $services->set('sulu_admin.form_metadata_provider', FormMetadataProvider::class)
        ->args([
            tagged_iterator('sulu_admin.form_metadata_loader'),
            tagged_iterator('sulu_admin.form_metadata_visitor'),
            tagged_iterator('sulu_admin.typed_form_metadata_visitor'),
            '%sulu_core.fallback_locale%',
        ])
        ->tag('sulu_admin.metadata_provider', ['type' => 'form']);

    $services->set('sulu_admin.cached_form_metadata_provider', CachedFormMetadataProvider::class)
        ->decorate('sulu_admin.form_metadata_provider')
        ->args([new Reference('.inner')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_admin.expression_language', ExpressionLanguage::class)
        ->args([
            null,
            [new Reference('sulu_admin.symfony_expression_language_provider')],
        ]);

    $services->set('sulu_admin.properties_xml_parser', PropertiesXmlParser::class)
        ->private()
        ->args([
            new Reference('sulu_admin.tag_xml_parser'),
            new Reference('sulu_admin.meta_xml_parser'),
        ]);

    $services->set('sulu_admin.meta_xml_parser', MetaXmlParser::class)
        ->private()
        ->args([
            new Reference('translator'),
            '%sulu_core.translated_locales%',
        ]);

    $services->set('sulu_admin.schema_xml_parser', SchemaXmlParser::class)
        ->private();

    $services->set('sulu_admin.template_xml_parser', TemplateXmlParser::class)
        ->private();

    $services->set('sulu_admin.tag_xml_parser', TagXmlParser::class)
        ->private();

    $services->set('sulu_admin.symfony_expression_language_provider', ContainerExpressionLanguageProvider::class)
        ->args([new Reference('service_container')]);

    $services->set('sulu_admin.expression_form_metadata_visitor', ExpressionFormMetadataVisitor::class)
        ->args([new Reference('sulu_admin.expression_language')])
        ->tag('sulu_admin.form_metadata_visitor')
        ->tag('sulu_admin.typed_form_metadata_visitor');

    $services->set('sulu_admin.tag_filter_typed_form_metadata_visitor', TagFilterTypedFormMetadataVisitor::class)
        ->tag('sulu_admin.typed_form_metadata_visitor');

    $services->set('sulu_admin.global_block_form_metadata_visitor', GlobalBlocksTypedFormMetadataVisitor::class)
        ->args([new Reference('sulu_admin.metadata_provider_registry')])
        ->tag('sulu_admin.typed_form_metadata_visitor')
        ->tag('sulu_admin.form_metadata_visitor');

    $services->set('sulu_admin.template_filter_typed_form_metadata_visitor', TemplateFilterTypedFormMetadataVisitor::class)
        ->tag('sulu_admin.typed_form_metadata_visitor');

    $services->set('sulu_admin.xml_form_metadata_loader', XmlFormMetadataLoader::class)
        ->args([
            new Reference('sulu_admin.form_metadata.form_xml_loader'),
            new Reference('sulu_admin.field_metadata_validator.chain'),
            '%sulu_admin.forms.directories%',
            '%sulu.cache_dir%/forms',
            '%kernel.debug%',
        ])
        ->tag('sulu_admin.form_metadata_loader', ['priority' => -64])
        ->tag('kernel.cache_warmer');

    $services->set('sulu_admin.template_form_metadata_loader', XmlTemplateFormMetadataLoader::class)
        ->lazy()
        ->args([
            new Reference('sulu_admin.form_metadata.template_xml_loader'),
            new Reference('sulu_admin.field_metadata_validator.chain'),
            '%sulu_admin.templates.configuration%',
            '%sulu.cache_dir%/templates',
            '%kernel.debug%',
        ])
        ->tag('sulu_admin.form_metadata_loader', ['priority' => 512])
        ->tag('kernel.cache_warmer', ['priority' => 512]);

    $services->set('sulu_admin.list_metadata_provider', ListMetadataProvider::class)
        ->args([
            tagged_iterator('sulu_admin.list_metadata_loader'),
            tagged_iterator('sulu_admin.list_metadata_visitor'),
        ])
        ->tag('sulu_admin.metadata_provider', ['type' => 'list']);

    $services->set('sulu_admin.xml_list_metadata_loader', XmlListMetadataLoader::class)
        ->args([
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('translator.default'),
        ])
        ->tag('sulu_admin.list_metadata_loader');

    $services->set('sulu_admin.expression_list_metadata_visitor', ExpressionListMetadataVisitor::class)
        ->args([new Reference('sulu_admin.expression_language')])
        ->tag('sulu_admin.list_metadata_visitor');

    $services->set('sulu_admin.view_builder_factory', ViewBuilderFactory::class);

    $services->alias(ViewBuilderFactoryInterface::class, 'sulu_admin.view_builder_factory');

    $services->set('sulu_admin.form_metadata.form_xml_loader', FormXmlLoader::class)
        ->args([
            new Reference('sulu_admin.properties_xml_parser'),
            new Reference('sulu_admin.schema_xml_parser'),
            new Reference('sulu_admin.tag_xml_parser'),
            new Reference('sulu_admin.schema_metadata_provider'),
        ]);

    $services->set('sulu_admin.form_metadata.template_xml_loader', TemplateXmlLoader::class)
        ->args([
            new Reference('sulu_admin.properties_xml_parser'),
            new Reference('sulu_admin.schema_xml_parser'),
            new Reference('sulu_admin.tag_xml_parser'),
            new Reference('sulu_admin.meta_xml_parser'),
            new Reference('sulu_admin.template_xml_parser'),
            new Reference('sulu_admin.schema_metadata_provider'),
        ]);

    $services->set('sulu_admin.property_metadata_mapper_registry', PropertyMetadataMapperRegistry::class)
        ->args([tagged_locator('sulu_admin.property_metadata_mapper', indexAttribute: 'type')]);

    $services->set('sulu_admin.property_metadata_min_max_value_resolver', PropertyMetadataMinMaxValueResolver::class);

    $services->set('sulu_admin.property_metadata_mapper.text', TextPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_min_max_value_resolver')])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'text_line'])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'text_area']);

    $services->set('sulu_admin.property_metadata_mapper.email', EmailPropertyMetadataMapper::class)
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'email']);

    $services->set('sulu_admin.property_metadata_mapper.number', NumberPropertyMetadataMapper::class)
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'number']);

    $services->set('sulu_admin.property_metadata_mapper.block', BlockPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.schema_metadata_provider')])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'block']);

    $services->set('sulu_admin.property_metadata_mapper.selection', SelectionPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_min_max_value_resolver')]);

    $services->set('sulu_admin.property_metadata_mapper.select', SelectPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_min_max_value_resolver')]);

    $services->set('sulu_admin.property_metadata_mapper.single_selection', SingleSelectionPropertyMetadataMapper::class);

    $services->set('sulu_admin.property_metadata_mapper.teaser_selection', TeaserSelectionPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_min_max_value_resolver')])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'teaser_selection']);

    $services->set('sulu_admin.schema_metadata_provider', SchemaMetadataProvider::class)
        ->args([new Reference('sulu_admin.property_metadata_mapper_registry')]);

    $services->set('sulu_admin.view_registry', ViewRegistry::class)
        ->args([new Reference('sulu_admin.admin_pool')])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.view_url_generator', ViewUrlGenerator::class)
        ->args([
            new Reference('router'),
            new Reference('sulu_admin.view_registry'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->alias(ViewUrlGeneratorInterface::class, 'sulu_admin.view_url_generator');

    $services->set('sulu_admin.navigation_registry', NavigationRegistry::class)
        ->args([
            new Reference('translator'),
            new Reference('sulu_admin.admin_pool'),
            new Reference('sulu_admin.view_registry'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.field_type_option_registry', FieldTypeOptionRegistry::class);

    $services->set('sulu_admin.collaboration_controller', CollaborationController::class)
        ->public()
        ->args([
            new Reference('security.token_storage'),
            new Reference('sulu_admin.collaboration_repository'),
            new Reference('fos_rest.view_handler'),
            '%kernel.secret%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.collaboration_repository', CollaborationRepository::class)
        ->args([
            new Reference('sulu_admin.collaboration_cache'),
            '%sulu_admin.collaboration_threshold%',
        ]);

    $services->set('sulu_admin.schema_handler', SchemaHandler::class)
        ->tag('jms_serializer.subscribing_handler');

    $services->set('sulu_admin.dropdown_toolbar_action_subscriber', DropdownToolbarActionSubscriber::class)
        ->args([new Reference('translator')])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_admin.item_metadata_serializer_subscriber', MetadataSubscriber::class)
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_admin.save_with_form_dialog_toolbar_action_subscriber', SaveWithFormDialogToolbarActionSubscriber::class)
        ->args([new Reference('translator')])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_admin.toggler_toolbar_action_subscriber', TogglerToolbarActionSubscriber::class)
        ->args([new Reference('translator')])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_admin.http_client', HttpClientInterface::class)
        ->factory([HttpClient::class, 'create']);

    $services->set('sulu_admin.update_build_command', UpdateBuildCommand::class)
        ->args([
            new Reference('http_client'),
            '%kernel.project_dir%',
            '%sulu.version%',
        ])
        ->tag('console.command');

    $services->set('sulu_admin.info_command', InfoCommand::class)
        ->args(['%sulu.version%'])
        ->tag('console.command');

    $services->set('sulu_admin.download_language_command', DownloadLanguageCommand::class)
        ->args([
            new Reference('sulu_admin.http_client'),
            new Reference('filesystem'),
            '%kernel.project_dir%',
            '%sulu_core.locales%',
        ])
        ->tag('console.command');

    $services->set('sulu_admin.valid_build_command', ValidateBuildCommand::class)
        ->args([
            new Reference('assets.packages'),
            '%kernel.project_dir%',
            '%sulu.version%',
        ])
        ->tag('sulu.context', ['context' => 'admin'])
        ->tag('console.command');

    $services->set('sulu_admin.debug_view_command', ViewDebugCommand::class)
        ->args([new Reference('sulu_admin.view_registry')])
        ->tag('sulu.context', ['context' => 'admin'])
        ->tag('console.command');

    $services->set('sulu_admin.field_metadata_validator.chain', ChainFieldMetadataValidator::class)
        ->args([tagged_iterator('sulu_admin.field_metadata_validator')]);

    $services->set('sulu_admin.field_metadata_validator.types_property', TypesPropertyFieldMetadataValidator::class)
        ->tag('sulu_admin.field_metadata_validator');

    $services->set('sulu_admin.field_metadata_validator.block', BlockFieldMetadataValidator::class)
        ->tag('sulu_admin.field_metadata_validator');

    $services->set('sulu_admin.form_metadata_validator.locale_options', LocalesOptionFormMetadataVisitor::class)
        ->tag('sulu_admin.form_metadata_visitor');

    $services->set('sulu_admin.block_settings_form_metadata_visitor', BlockSettingsFormMetadataVisitor::class)
        ->tag('sulu_admin.typed_form_metadata_visitor');

    $services->set('sulu_admin.block_id_generator_form_metadata_visitor', BlockIdGeneratorFormMetadataVisitor::class)
        ->tag('sulu_admin.typed_form_metadata_visitor');

    $services->set('sulu_admin.icon_controller', IconController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            '%sulu_admin.icon_sets%',
            tagged_iterator('sulu_admin.icon_provider', indexAttribute: 'type'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.icon_provider.icomoon', IcomoonProvider::class)
        ->tag('sulu_admin.icon_provider', ['type' => 'icomoon']);

    $services->set('sulu_admin.icon_provider.svg', SvgProvider::class)
        ->args([new Reference('sulu_admin.icon_cache')])
        ->tag('sulu_admin.icon_provider', ['type' => 'svg']);

    $services->set('sulu_admin.smart_content_item_controller', SmartContentItemController::class)
        ->public()
        ->args([
            tagged_locator('sulu_content.smart_content_provider', indexAttribute: 'type', defaultIndexMethod: 'getType'),
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.smart_content_query_enhancer', SmartContentQueryEnhancer::class);

    $services->set('sulu_admin.teaser_controller', TeaserController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_admin.teaser_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.teaser_provider_pool', TeaserProviderPool::class)
        ->args([tagged_iterator('sulu.teaser.provider', indexAttribute: 'alias')]);

    $services->set('sulu_admin.teaser_manager', TeaserManager::class)
        ->args([new Reference('sulu_admin.teaser_provider_pool')]);

    $services->set('sulu_admin.teaser_tag_property_extractor', TeaserTagPropertyExtractor::class)
        ->args([new Reference('sulu_admin.form_metadata_provider')]);

    $services->set('sulu_admin.block_id_generator', BlockIdGenerator::class);

    $services->alias(BlockIdGeneratorInterface::class, 'sulu_admin.block_id_generator');

    $services->set('sulu_admin.block_id_controller', BlockIdController::class)
        ->public()
        ->args([new Reference(BlockIdGeneratorInterface::class)])
        ->tag('sulu.context', ['context' => 'admin']);
};
