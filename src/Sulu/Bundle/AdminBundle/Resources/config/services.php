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
use Sulu\Bundle\AdminBundle\Command\DownloadBuildCommand;
use Sulu\Bundle\AdminBundle\Command\DownloadLanguageCommand;
use Sulu\Bundle\AdminBundle\Command\InfoCommand;
use Sulu\Bundle\AdminBundle\Command\UpdateBuildCommand;
use Sulu\Bundle\AdminBundle\Command\ValidateBuildCommand;
use Sulu\Bundle\AdminBundle\Command\ViewDebugCommand;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\Controller\CollaborationController;
use Sulu\Bundle\AdminBundle\Controller\IconController;
use Sulu\Bundle\AdminBundle\Entity\CollaborationRepository;
use Sulu\Bundle\AdminBundle\FieldType\FieldTypeOptionRegistry;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Icon\providers\IcomoonProvider;
use Sulu\Bundle\AdminBundle\Icon\providers\SvgProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ExpressionFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\StructureFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagFilterTypedFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\BlockFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\ChainFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\TypesPropertyFieldMetadataValidator;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\LocalesOptionFormMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ExpressionListMetadataVisitor;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\XmlListMetadataLoader;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectionPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SingleSelectionPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\TextPropertyMetadataMapper;
use Sulu\Bundle\AdminBundle\Serializer\Handler\SchemaHandler;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\DropdownToolbarActionSubscriber;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\SaveWithFormDialogToolbarActionSubscriber;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\TogglerToolbarActionSubscriber;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_admin.admin_controller.class', AdminController::class);
    $parameters->set('sulu_admin.admin_pool.class', AdminPool::class);

    $services->set('sulu_admin.admin_controller', '%sulu_admin.admin_controller.class%')
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
            new Reference('sulu_page.smart_content.data_provider_pool'),
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

    $services->set('sulu_admin.admin_pool', '%sulu_admin.admin_pool.class%')
        ->public()
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_admin.metadata_provider_registry', MetadataProviderRegistry::class)
        ->args([tagged_locator('sulu_admin.metadata_provider', indexAttribute: 'type')]);

    $services->set('sulu_admin.form_metadata_provider', FormMetadataProvider::class)
        ->args([
            tagged_iterator('sulu_admin.form_metadata_loader'),
            tagged_iterator('sulu_admin.form_metadata_visitor'),
            tagged_iterator('sulu_admin.typed_form_metadata_visitor'),
            '%sulu_core.fallback_locale%',
        ])
        ->tag('sulu_admin.metadata_provider', ['type' => 'form']);

    $services->set('sulu_admin.expression_form_metadata_visitor', ExpressionFormMetadataVisitor::class)
        ->args([new Reference('sulu_core.expression_language')])
        ->tag('sulu_admin.form_metadata_visitor')
        ->tag('sulu_admin.typed_form_metadata_visitor');

    $services->set('sulu_admin.tag_filter_typed_form_metadata_visitor', TagFilterTypedFormMetadataVisitor::class)
        ->tag('sulu_admin.typed_form_metadata_visitor');

    $services->set('sulu_admin.xml_form_metadata_loader', XmlFormMetadataLoader::class)
        ->args([
            new Reference('sulu_admin.form_metadata.form_xml_loader'),
            new Reference('sulu_admin.field_metadata_validator.chain'),
            '%sulu_admin.forms.directories%',
            '%sulu.cache_dir%/forms',
            '%kernel.debug%',
        ])
        ->tag('sulu_admin.form_metadata_loader')
        ->tag('kernel.cache_warmer');

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
        ->args([new Reference('sulu_core.expression_language')])
        ->tag('sulu_admin.list_metadata_visitor');

    $services->set('sulu_admin.view_builder_factory', ViewBuilderFactory::class);

    $services->alias(ViewBuilderFactoryInterface::class, 'sulu_admin.view_builder_factory');

    $services->set('sulu_admin.form_metadata.form_xml_loader', FormXmlLoader::class)
        ->args([
            new Reference('sulu_page.structure.properties_xml_parser'),
            new Reference('sulu_page.structure.schema_xml_parser'),
            '%sulu_core.locales%',
            new Reference('sulu_admin.form_metadata.form_metadata_mapper'),
        ]);

    $services->set('sulu_admin.property_metadata_mapper_registry', PropertyMetadataMapperRegistry::class)
        ->args([tagged_locator('sulu_admin.property_metadata_mapper', indexAttribute: 'type')]);

    $services->set('sulu_admin.property_metadata_min_max_value_resolver', PropertyMetadataMinMaxValueResolver::class);

    $services->set('sulu_admin.property_metadata_mapper.text', TextPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_min_max_value_resolver')])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'text_line'])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'text_area']);

    $services->set('sulu_admin.property_metadata_mapper.selection', SelectionPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_min_max_value_resolver')]);

    $services->set('sulu_admin.property_metadata_mapper.single_selection', SingleSelectionPropertyMetadataMapper::class);

    $services->set('sulu_admin.form_metadata.form_metadata_mapper', FormMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_mapper_registry')]);

    $services->set('sulu_admin.structure_form_metadata_loader', StructureFormMetadataLoader::class)
        ->lazy()
        ->args([
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_admin.form_metadata.form_metadata_mapper'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_admin.field_metadata_validator.chain'),
            '%sulu.content.structure.default_types%',
            '%sulu_core.locales%',
            '%sulu.cache_dir%/form-structures',
            '%kernel.debug%',
        ])
        ->tag('sulu_admin.form_metadata_loader')
        ->tag('kernel.cache_warmer', ['priority' => 512]);

    $services->set('sulu_admin.view_registry', ViewRegistry::class)
        ->args([new Reference('sulu_admin.admin_pool')])
        ->tag('sulu.context', ['context' => 'admin']);

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

    $services->set('sulu_admin.save_with_form_dialog_toolbar_action_subscriber', SaveWithFormDialogToolbarActionSubscriber::class)
        ->args([new Reference('translator')])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_admin.toggler_toolbar_action_subscriber', TogglerToolbarActionSubscriber::class)
        ->args([new Reference('translator')])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_admin.http_client', HttpClientInterface::class)
        ->factory([HttpClient::class, 'create']);

    $services->set('sulu_admin.download_build_command', DownloadBuildCommand::class)
        ->tag('console.command');

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
};
