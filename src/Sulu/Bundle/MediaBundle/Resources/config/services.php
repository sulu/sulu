<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\ORM\EntityRepository;
use Imagine\Gmagick\Imagine;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager;
use Sulu\Bundle\MediaBundle\Command\FormatCacheCleanupCommand;
use Sulu\Bundle\MediaBundle\Command\FormatCacheRegenerateCommand;
use Sulu\Bundle\MediaBundle\Controller\CollectionController;
use Sulu\Bundle\MediaBundle\Controller\FormatController;
use Sulu\Bundle\MediaBundle\Controller\MediaController;
use Sulu\Bundle\MediaBundle\Controller\MediaFormatController;
use Sulu\Bundle\MediaBundle\Controller\MediaPreviewController;
use Sulu\Bundle\MediaBundle\Controller\MediaRedirectController;
use Sulu\Bundle\MediaBundle\Controller\MediaStreamController;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRemovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRestoredEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaRemovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaRestoredEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaVersionAddedEvent;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepository;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMetaRepository;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\EventListener\CacheInvalidationListener;
use Sulu\Bundle\MediaBundle\FileInspector\SvgFileInspector;
use Sulu\Bundle\MediaBundle\FileInspector\SvgSanitizerFactory;
use Sulu\Bundle\MediaBundle\FileInspector\UploadFileSubscriber;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\SmartContent\MediaSmartContentProvider;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\Visitor\MediaSmartContentFiltersVisitor;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search\AdminCollectionIndexListener;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search\AdminCollectionReindexProvider;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search\AdminMediaIndexListener;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search\AdminMediaReindexProvider;
use Sulu\Bundle\MediaBundle\Markup\Link\MediaLinkProvider;
use Sulu\Bundle\MediaBundle\Media\DispositionType\DispositionTypeResolver;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidator;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheClearer;
use Sulu\Bundle\MediaBundle\Media\FormatCache\LocalFormatCache;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManager;
use Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManager;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropper\Cropper;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Focus\Focus;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImagineImageConverter;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\MediaImageExtractor;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\Scaler;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\BlurTransformation;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\CropTransformation;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\GammaTransformation;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\GrayscaleTransformation;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\NegativeTransformation;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\PasteTransformation;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\SharpenTransformation;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\TransformationPool;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\ImagePropertiesProvider;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManager;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Video\VideoThumbnailService;
use Sulu\Bundle\MediaBundle\Metadata\ImageMapFieldMetadataValidator;
use Sulu\Bundle\MediaBundle\Serializer\Subscriber\MediaPermissionsSubscriber;
use Sulu\Bundle\MediaBundle\Twig\DispositionTypeTwigExtension;
use Sulu\Bundle\MediaBundle\Twig\MediaTwigExtension;
use Sulu\Component\Cache\DataCache;
use Sulu\Component\Media\SystemCollections\SystemCollectionBuilder;
use Sulu\Component\Media\SystemCollections\SystemCollectionManager;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_media.collection_entity', Collection::class);
    $parameters->set('sulu_media.format_options_entity', FormatOptions::class);
    $parameters->set('sulu_media.entity.file_version_meta', FileVersionMeta::class);

    $services->set('sulu_media.media_list_builder_factory', MediaListBuilderFactory::class)
        ->args([
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_media.collection_repository'),
            new Reference('sulu_security.security_checker'),
            '%sulu.model.media.class%',
            '%sulu.model.collection.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.media_list_representation_factory', MediaListRepresentationFactory::class)
        ->args([
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_media.format_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.media_controller', MediaController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_media.media_manager'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_media.storage'),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            '%sulu.model.media.class%',
            new Reference('sulu_media.media_list_builder_factory'),
            new Reference('sulu_media.media_list_representation_factory'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.media_preview_controller', MediaPreviewController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_media.system_collections.manager'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.media_format_controller', MediaFormatController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_media.format_options_manager'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.media_redirect_controller', MediaRedirectController::class)
        ->public()
        ->args([new Reference('sulu_media.media_manager')])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.collection_controller', CollectionController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_core.list_rest_helper'),
            new Reference('sulu_security.security_checker'),
            new Reference('translator'),
            new Reference('sulu_media.system_collections.manager'),
            new Reference('sulu_media.collection_manager'),
            '%sulu_media.collection.type.default%',
            '%sulu_security.permissions%',
            '%sulu.model.collection.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.media_stream_controller', MediaStreamController::class)
        ->public()
        ->args([
            new Reference('sulu_media.disposition_type.resolver'),
            new Reference('sulu.repository.media'),
            new Reference('sulu_route.path_cleanup'),
            new Reference('sulu_media.format_manager'),
            new Reference('sulu_media.format_cache'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_media.storage'),
            new Reference('sulu_security.security_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_media.format_controller', FormatController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_media.format_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.admin', MediaAdmin::class)
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu.core.localization_manager'),
            new Reference('router'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_activity.activity_list_view_builder_factory'),
            new Reference('sulu_reference.reference_list_view_builder_factory'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.collection_repository', CollectionRepository::class)
        ->public()
        ->args(['%sulu_media.collection_entity%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository'])
        ->call('setAccessControlQueryEnhancer', [new Reference('sulu_security.access_control_query_enhancer')])
        ->tag('sulu_security.access_control_descendant_provider');

    $services->set('sulu_media.file_version_meta_repository', FileVersionMetaRepository::class)
        ->args(['%sulu_media.entity.file_version_meta%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_media.file_validator', FileValidator::class);

    $services->set('sulu_media.format_cache_clearer', FormatCacheClearer::class)
        ->args([tagged_iterator('sulu_media.format_cache', indexAttribute: 'alias')]);

    $services->set('sulu_media.format_cache', LocalFormatCache::class)
        ->public()
        ->args([
            new Reference('filesystem'),
            '%sulu_media.format_cache.path%',
            '%sulu_media.format_cache.media_proxy_path%',
            '%sulu_media.format_cache.segments%',
        ])
        ->tag('sulu_media.format_cache', ['alias' => 'local']);

    // Imagine Adapters
    $services->alias('sulu_media.adapter', 'sulu_media.adapter.gd');

    $services->set('sulu_media.adapter.gd', \Imagine\Gd\Imagine::class);

    $services->set('sulu_media.adapter.imagick', \Imagine\Imagick\Imagine::class);

    $services->set('sulu_media.adapter.gmagick', Imagine::class);

    $services->set('sulu_media.image.converter', ImagineImageConverter::class)
        ->args([
            new Reference('sulu_media.adapter'),
            new Reference('sulu_media.storage'),
            new Reference('sulu_media.image.media_extractor'),
            new Reference('sulu_media.image.transformation_pool'),
            new Reference('sulu_media.image.focus'),
            new Reference('sulu_media.image.scaler'),
            new Reference('sulu_media.image.cropper'),
            '%sulu_media.image.formats%',
            '%sulu_media.format_manager.mime_types%',
            new Reference('sulu_media.adapter.svg', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_media.image.media_extractor', MediaImageExtractor::class)
        ->args([
            new Reference('sulu_media.adapter'),
            new Reference('sulu_media.video_thumbnail_generator'),
            '%sulu_media.ghost_script.path%',
        ]);

    $services->set('sulu_media.image.focus', Focus::class);

    $services->set('sulu_media.image.scaler', Scaler::class);

    $services->set('sulu_media.image.cropper', Cropper::class);

    $services->set('sulu_media.image.transformation_pool', TransformationPool::class)
        ->args([tagged_locator('sulu_media.image.transformation', indexAttribute: 'alias')]);

    $services->set('sulu_media.image.transformation.crop', CropTransformation::class)
        ->tag('sulu_media.image.transformation', ['alias' => 'crop']);

    $services->set('sulu_media.image.transformation.paste', PasteTransformation::class)
        ->args([
            new Reference('sulu_media.adapter'),
            new Reference('file_locator'),
        ])
        ->tag('sulu_media.image.transformation', ['alias' => 'paste']);

    $services->set('sulu_media.image.transformation.blur', BlurTransformation::class)
        ->tag('sulu_media.image.transformation', ['alias' => 'blur']);

    $services->set('sulu_media.image.transformation.gamma', GammaTransformation::class)
        ->tag('sulu_media.image.transformation', ['alias' => 'gamma']);

    $services->set('sulu_media.image.transformation.grayscale', GrayscaleTransformation::class)
        ->tag('sulu_media.image.transformation', ['alias' => 'grayscale']);

    $services->set('sulu_media.image.transformation.negative', NegativeTransformation::class)
        ->tag('sulu_media.image.transformation', ['alias' => 'negative']);

    $services->set('sulu_media.image.transformation.sharpen', SharpenTransformation::class)
        ->tag('sulu_media.image.transformation', ['alias' => 'sharpen']);

    $services->set('sulu_media.media_manager', MediaManager::class)
        ->public()
        ->args([
            new Reference('sulu.repository.media'),
            new Reference('sulu_media.collection_repository'),
            new Reference('sulu.repository.user'),
            new Reference('sulu.repository.category'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_media.storage'),
            new Reference('sulu_media.file_validator'),
            new Reference('sulu_media.format_manager'),
            new Reference('sulu_tag.tag_manager'),
            new Reference('sulu_media.type_manager'),
            new Reference('sulu_route.path_cleanup'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_security.security_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            tagged_iterator('sulu_media.media_properties_provider'),
            '%sulu_media.media_manager.media_download_path%',
            new Reference('sulu.repository.target_group', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_media.media_manager.media_download_path_admin%',
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->alias(MediaManagerInterface::class, 'sulu_media.media_manager');

    $services->set('sulu_media.image_properties_provider', ImagePropertiesProvider::class)
        ->args([
            new Reference('sulu_media.adapter'),
            new Reference('sulu_media.adapter.svg', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu_media.media_properties_provider');

    $services->set('sulu_media.type_manager', TypeManager::class)
        ->args(['%sulu_media.media.types%']);

    $services->alias(TypeManagerInterface::class, 'sulu_media.type_manager');

    $services->set('sulu_media.format_options_repository', EntityRepository::class)
        ->args(['%sulu_media.format_options_entity%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_media.format_options_manager', FormatOptionsManager::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_media.format_options_repository'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_media.format_manager'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu_media.image.formats%',
        ]);

    $services->set('sulu_media.format_manager', FormatManager::class)
        ->public()
        ->args([
            new Reference('sulu.repository.media'),
            new Reference('sulu_media.format_cache'),
            new Reference('sulu_media.image.converter'),
            '%sulu_media.format_cache.save_image%',
            '%sulu_media.format_manager.response_headers%',
            '%sulu_media.image.formats%',
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_media.collection_manager', CollectionManager::class)
        ->public()
        ->args([
            new Reference('sulu_media.collection_repository'),
            new Reference('sulu.repository.media'),
            new Reference('sulu_media.format_manager'),
            new Reference('sulu.repository.user'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_media.collection.previews.format%',
            '%sulu_security.permissions%',
        ]);

    $services->set('sulu_media.twig_extension.disposition_type', DispositionTypeTwigExtension::class)
        ->tag('twig.extension');

    $services->set('sulu_media.twig_extension.media', MediaTwigExtension::class)
        ->args([new Reference('sulu_media.media_manager')])
        ->tag('twig.extension');

    $services->set('sulu_media.video_thumbnail_generator', VideoThumbnailService::class)
        ->args([new Reference('sulu_media.ffmpeg', ContainerInterface::NULL_ON_INVALID_REFERENCE)]);

    $services->set('sulu_media.security_context', SecurityCondition::class)
        ->args(['sulu.media.collections']);

    $services->set('sulu_media.media_smart_content_provider', MediaSmartContentProvider::class)
        ->args([
            new Reference('doctrine.orm.default_entity_manager'),
            new Reference('sulu_admin.smart_content_query_enhancer'),
            new Reference('translator'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_security.access_control_query_enhancer'),
            new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            expr('container.hasParameter(\'sulu_audience_targeting.enabled\')'),
            '%sulu_security.permissions%',
        ])
        ->tag('sulu_content.smart_content_provider', ['type' => 'media']);

    $services->set('sulu_media.media_smart_content_filters_visitor', MediaSmartContentFiltersVisitor::class)
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('request_stack'),
        ])
        ->tag('sulu_content.smart_content_filters_visitor');

    $services->set('sulu_media.system_collections.cache', DataCache::class)
        ->args(['%sulu.cache_dir%/system_collection.cache']);

    $services->set('sulu_media.system_collections.manager', SystemCollectionManager::class)
        ->public()
        ->args([
            '%sulu_media.system_collections%',
            new Reference('sulu_media.collection_manager'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_media.system_collections.cache'),
            '%kernel.default_locale%',
        ]);

    $services->alias(SystemCollectionManagerInterface::class, 'sulu_media.system_collections.manager');

    $services->set('sulu_media.system_collections.builder', SystemCollectionBuilder::class)
        ->args([new Reference('sulu_media.system_collections.manager')])
        ->tag('massive_build.builder');

    $services->set('sulu_media.disposition_type.resolver', DispositionTypeResolver::class)
        ->public()
        ->args([
            '%sulu_media.disposition_type.default%',
            '%sulu_media.disposition_type.mime_types_inline%',
            '%sulu_media.disposition_type.mime_types_attachment%',
        ]);

    $services->set('sulu_media.doctrine.invalidation_listener', CacheInvalidationListener::class)
        ->args([new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ->tag('doctrine.event_listener', ['event' => 'postPersist'])
        ->tag('doctrine.event_listener', ['event' => 'postUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'preRemove']);

    $services->set('sulu_media.media_link_provider', MediaLinkProvider::class)
        ->args([
            new Reference('sulu.repository.media'),
            new Reference('sulu_media.media_manager'),
            new Reference('translator'),
        ])
        ->tag('sulu.link.provider', ['alias' => 'media']);

    $services->set('sulu_media.command.format_cache.cleanup', FormatCacheCleanupCommand::class)
        ->args([
            new Reference('sulu.repository.media'),
            new Reference('filesystem'),
            '%sulu_media.format_cache.path%',
        ])
        ->tag('console.command');

    $services->set('sulu_media.command.format_cache.regenerate', FormatCacheRegenerateCommand::class)
        ->args([
            new Reference('filesystem'),
            new Reference('sulu_media.format_manager'),
            '%sulu_media.format_cache.path%',
        ])
        ->tag('console.command');

    $services->set('sulu_media.fixtures.collection_types', LoadCollectionTypes::class)
        ->tag('doctrine.fixture.orm');

    $services->set('sulu_media.field_metadata_validator.image_map', ImageMapFieldMetadataValidator::class)
        ->tag('sulu_admin.field_metadata_validator');

    $services->set('sulu_security.serializer.media_permissions', MediaPermissionsSubscriber::class)
        ->args([
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage'),
        ])
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_media.file_inspector.sanitizer_factory', SvgSanitizerFactory::class);

    $services->set('sulu_media.file_inspector.html_sanitizer', HtmlSanitizerInterface::class)
        ->factory([new Reference('sulu_media.file_inspector.sanitizer_factory'), 'create']);

    $services->set('sulu_media.file_inspector.html_sanitizer_safe', HtmlSanitizerInterface::class)
        ->factory([new Reference('sulu_media.file_inspector.sanitizer_factory'), 'createSafe']);

    $services->set('sulu_media.file_inspector.svg_inspector', SvgFileInspector::class)
        ->args([
            new Reference('sulu_media.file_inspector.html_sanitizer'),
            new Reference('sulu_media.file_inspector.html_sanitizer_safe'),
        ])
        ->tag('sulu_media.file_inspector');

    $services->alias('Sulu\Bundle\MediaBundle\FileInspector\SvgSafetyInspectorInterface', 'sulu_media.file_inspector.svg_inspector');

    $services->set('sulu_media.file_inspector.subscriber', UploadFileSubscriber::class)
        ->args([tagged_iterator('sulu_media.file_inspector')])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_media.admin_media_index_listener', AdminMediaIndexListener::class)
        ->args([new Reference('sulu_message_bus')])
        ->tag('kernel.event_listener', ['event' => MediaCreatedEvent::class, 'method' => 'onMediaChanged'])
        ->tag('kernel.event_listener', ['event' => MediaModifiedEvent::class, 'method' => 'onMediaChanged'])
        ->tag('kernel.event_listener', ['event' => MediaRemovedEvent::class, 'method' => 'onMediaChanged'])
        ->tag('kernel.event_listener', ['event' => MediaRestoredEvent::class, 'method' => 'onMediaChanged'])
        ->tag('kernel.event_listener', ['event' => MediaVersionAddedEvent::class, 'method' => 'onMediaChanged']);

    $services->set('sulu_media.admin_collection_index_listener', AdminCollectionIndexListener::class)
        ->args([new Reference('sulu_message_bus')])
        ->tag('kernel.event_listener', ['event' => CollectionCreatedEvent::class, 'method' => 'onCollectionChanged'])
        ->tag('kernel.event_listener', ['event' => CollectionModifiedEvent::class, 'method' => 'onCollectionChanged'])
        ->tag('kernel.event_listener', ['event' => CollectionRemovedEvent::class, 'method' => 'onCollectionChanged'])
        ->tag('kernel.event_listener', ['event' => CollectionRestoredEvent::class, 'method' => 'onCollectionChanged']);

    $services->set('sulu_media.admin_media_reindex_provider', AdminMediaReindexProvider::class)
        ->args([new Reference('doctrine.orm.entity_manager')])
        ->tag('cmsig_seal.reindex_provider');

    $services->set('sulu_media.admin_collection_reindex_provider', AdminCollectionReindexProvider::class)
        ->args([new Reference('doctrine.orm.entity_manager')])
        ->tag('cmsig_seal.reindex_provider');
};
