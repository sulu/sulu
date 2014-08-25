<?php

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * appTestDebugProjectContainer
 *
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 */
class appTestDebugProjectContainer extends Container
{
    private $parameters;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->parameters = $this->getDefaultParameters();

        $this->services =
        $this->scopedServices =
        $this->scopeStacks = array();

        $this->set('service_container', $this);

        $this->scopes = array('request' => 'container');
        $this->scopeChildren = array('request' => array());
        $this->methodMap = array(
            'annotation_reader' => 'getAnnotationReaderService',
            'bazinga_hateoas.expression_language' => 'getBazingaHateoas_ExpressionLanguageService',
            'cache_clearer' => 'getCacheClearerService',
            'cache_warmer' => 'getCacheWarmerService',
            'controller_name_converter' => 'getControllerNameConverterService',
            'debug.controller_resolver' => 'getDebug_ControllerResolverService',
            'debug.debug_handlers_listener' => 'getDebug_DebugHandlersListenerService',
            'debug.deprecation_logger_listener' => 'getDebug_DeprecationLoggerListenerService',
            'debug.emergency_logger_listener' => 'getDebug_EmergencyLoggerListenerService',
            'debug.event_dispatcher' => 'getDebug_EventDispatcherService',
            'debug.scream_logger_listener' => 'getDebug_ScreamLoggerListenerService',
            'debug.stopwatch' => 'getDebug_StopwatchService',
            'debug.templating.engine.php' => 'getDebug_Templating_Engine_PhpService',
            'doctrine' => 'getDoctrineService',
            'doctrine.dbal.connection_factory' => 'getDoctrine_Dbal_ConnectionFactoryService',
            'doctrine.dbal.default_connection' => 'getDoctrine_Dbal_DefaultConnectionService',
            'doctrine.orm.default_entity_manager' => 'getDoctrine_Orm_DefaultEntityManagerService',
            'doctrine.orm.default_manager_configurator' => 'getDoctrine_Orm_DefaultManagerConfiguratorService',
            'doctrine.orm.validator.unique' => 'getDoctrine_Orm_Validator_UniqueService',
            'doctrine.orm.validator_initializer' => 'getDoctrine_Orm_ValidatorInitializerService',
            'doctrine_phpcr' => 'getDoctrinePhpcrService',
            'doctrine_phpcr.console_dumper' => 'getDoctrinePhpcr_ConsoleDumperService',
            'doctrine_phpcr.default_session' => 'getDoctrinePhpcr_DefaultSessionService',
            'doctrine_phpcr.initializer_manager' => 'getDoctrinePhpcr_InitializerManagerService',
            'doctrine_phpcr.jackalope.repository.default' => 'getDoctrinePhpcr_Jackalope_Repository_DefaultService',
            'doctrine_phpcr.jackalope.repository.factory.doctrinedbal' => 'getDoctrinePhpcr_Jackalope_Repository_Factory_DoctrinedbalService',
            'doctrine_phpcr.jackalope.repository.factory.jackrabbit' => 'getDoctrinePhpcr_Jackalope_Repository_Factory_JackrabbitService',
            'doctrine_phpcr.jackalope.repository.factory.prismic' => 'getDoctrinePhpcr_Jackalope_Repository_Factory_PrismicService',
            'doctrine_phpcr.jackalope.repository.factory.service.doctrinedbal' => 'getDoctrinePhpcr_Jackalope_Repository_Factory_Service_DoctrinedbalService',
            'doctrine_phpcr.jackalope.repository.factory.service.jackrabbit' => 'getDoctrinePhpcr_Jackalope_Repository_Factory_Service_JackrabbitService',
            'doctrine_phpcr.jackalope.repository.factory.service.prismic' => 'getDoctrinePhpcr_Jackalope_Repository_Factory_Service_PrismicService',
            'doctrine_phpcr.jackalope_doctrine_dbal.schema_listener' => 'getDoctrinePhpcr_JackalopeDoctrineDbal_SchemaListenerService',
            'doctrine_phpcr.odm.default_document_manager' => 'getDoctrinePhpcr_Odm_DefaultDocumentManagerService',
            'doctrine_phpcr.odm.form.type.path' => 'getDoctrinePhpcr_Odm_Form_Type_PathService',
            'doctrine_phpcr.odm.validator.valid_phpcr_odm' => 'getDoctrinePhpcr_Odm_Validator_ValidPhpcrOdmService',
            'file_locator' => 'getFileLocatorService',
            'filesystem' => 'getFilesystemService',
            'form.type.entity' => 'getForm_Type_EntityService',
            'form.type.phpcr.document' => 'getForm_Type_Phpcr_DocumentService',
            'form.type.phpcr.reference' => 'getForm_Type_Phpcr_ReferenceService',
            'form.type.phpcr_odm.reference_collection' => 'getForm_Type_PhpcrOdm_ReferenceCollectionService',
            'form.type_guesser.doctrine' => 'getForm_TypeGuesser_DoctrineService',
            'form.type_guesser.doctrine_phpcr' => 'getForm_TypeGuesser_DoctrinePhpcrService',
            'fos_rest.body_listener' => 'getFosRest_BodyListenerService',
            'fos_rest.decoder.json' => 'getFosRest_Decoder_JsonService',
            'fos_rest.decoder.jsontoform' => 'getFosRest_Decoder_JsontoformService',
            'fos_rest.decoder.xml' => 'getFosRest_Decoder_XmlService',
            'fos_rest.decoder_provider' => 'getFosRest_DecoderProviderService',
            'fos_rest.format_negotiator' => 'getFosRest_FormatNegotiatorService',
            'fos_rest.inflector.doctrine' => 'getFosRest_Inflector_DoctrineService',
            'fos_rest.normalizer.camel_keys' => 'getFosRest_Normalizer_CamelKeysService',
            'fos_rest.request.param_fetcher' => 'getFosRest_Request_ParamFetcherService',
            'fos_rest.request.param_fetcher.reader' => 'getFosRest_Request_ParamFetcher_ReaderService',
            'fos_rest.routing.loader.controller' => 'getFosRest_Routing_Loader_ControllerService',
            'fos_rest.routing.loader.processor' => 'getFosRest_Routing_Loader_ProcessorService',
            'fos_rest.routing.loader.reader.action' => 'getFosRest_Routing_Loader_Reader_ActionService',
            'fos_rest.routing.loader.reader.controller' => 'getFosRest_Routing_Loader_Reader_ControllerService',
            'fos_rest.routing.loader.xml_collection' => 'getFosRest_Routing_Loader_XmlCollectionService',
            'fos_rest.routing.loader.yaml_collection' => 'getFosRest_Routing_Loader_YamlCollectionService',
            'fos_rest.serializer.exception_wrapper_serialize_handler' => 'getFosRest_Serializer_ExceptionWrapperSerializeHandlerService',
            'fos_rest.view.exception_wrapper_handler' => 'getFosRest_View_ExceptionWrapperHandlerService',
            'fos_rest.view_handler' => 'getFosRest_ViewHandlerService',
            'fos_rest.violation_formatter' => 'getFosRest_ViolationFormatterService',
            'fragment.handler' => 'getFragment_HandlerService',
            'fragment.renderer.esi' => 'getFragment_Renderer_EsiService',
            'fragment.renderer.hinclude' => 'getFragment_Renderer_HincludeService',
            'fragment.renderer.inline' => 'getFragment_Renderer_InlineService',
            'hateoas.configuration.provider.resolver' => 'getHateoas_Configuration_Provider_ResolverService',
            'hateoas.configuration.relations_repository' => 'getHateoas_Configuration_RelationsRepositoryService',
            'hateoas.embeds_factory' => 'getHateoas_EmbedsFactoryService',
            'hateoas.event_subscriber.json' => 'getHateoas_EventSubscriber_JsonService',
            'hateoas.event_subscriber.xml' => 'getHateoas_EventSubscriber_XmlService',
            'hateoas.expression.evaluator' => 'getHateoas_Expression_EvaluatorService',
            'hateoas.generator.registry' => 'getHateoas_Generator_RegistryService',
            'hateoas.generator.symfony' => 'getHateoas_Generator_SymfonyService',
            'hateoas.links_factory' => 'getHateoas_LinksFactoryService',
            'hateoas.serializer.exclusion_manager' => 'getHateoas_Serializer_ExclusionManagerService',
            'http_kernel' => 'getHttpKernelService',
            'jms_serializer' => 'getJmsSerializerService',
            'jms_serializer.array_collection_handler' => 'getJmsSerializer_ArrayCollectionHandlerService',
            'jms_serializer.constraint_violation_handler' => 'getJmsSerializer_ConstraintViolationHandlerService',
            'jms_serializer.datetime_handler' => 'getJmsSerializer_DatetimeHandlerService',
            'jms_serializer.doctrine_proxy_subscriber' => 'getJmsSerializer_DoctrineProxySubscriberService',
            'jms_serializer.form_error_handler' => 'getJmsSerializer_FormErrorHandlerService',
            'jms_serializer.handler_registry' => 'getJmsSerializer_HandlerRegistryService',
            'jms_serializer.json_deserialization_visitor' => 'getJmsSerializer_JsonDeserializationVisitorService',
            'jms_serializer.json_serialization_visitor' => 'getJmsSerializer_JsonSerializationVisitorService',
            'jms_serializer.metadata.file_locator' => 'getJmsSerializer_Metadata_FileLocatorService',
            'jms_serializer.metadata_driver' => 'getJmsSerializer_MetadataDriverService',
            'jms_serializer.metadata_factory' => 'getJmsSerializer_MetadataFactoryService',
            'jms_serializer.naming_strategy' => 'getJmsSerializer_NamingStrategyService',
            'jms_serializer.object_constructor' => 'getJmsSerializer_ObjectConstructorService',
            'jms_serializer.php_collection_handler' => 'getJmsSerializer_PhpCollectionHandlerService',
            'jms_serializer.stopwatch_subscriber' => 'getJmsSerializer_StopwatchSubscriberService',
            'jms_serializer.templating.helper.serializer' => 'getJmsSerializer_Templating_Helper_SerializerService',
            'jms_serializer.unserialize_object_constructor' => 'getJmsSerializer_UnserializeObjectConstructorService',
            'jms_serializer.xml_deserialization_visitor' => 'getJmsSerializer_XmlDeserializationVisitorService',
            'jms_serializer.xml_serialization_visitor' => 'getJmsSerializer_XmlSerializationVisitorService',
            'jms_serializer.yaml_serialization_visitor' => 'getJmsSerializer_YamlSerializationVisitorService',
            'kernel' => 'getKernelService',
            'liip_theme.active_theme' => 'getLiipTheme_ActiveThemeService',
            'liip_theme.theme_controller' => 'getLiipTheme_ThemeControllerService',
            'locale_listener' => 'getLocaleListenerService',
            'logger' => 'getLoggerService',
            'monolog.handler.main' => 'getMonolog_Handler_MainService',
            'monolog.handler.nested' => 'getMonolog_Handler_NestedService',
            'monolog.logger.deprecation' => 'getMonolog_Logger_DeprecationService',
            'monolog.logger.doctrine' => 'getMonolog_Logger_DoctrineService',
            'monolog.logger.doctrine_phpcr' => 'getMonolog_Logger_DoctrinePhpcrService',
            'monolog.logger.emergency' => 'getMonolog_Logger_EmergencyService',
            'monolog.logger.event' => 'getMonolog_Logger_EventService',
            'monolog.logger.request' => 'getMonolog_Logger_RequestService',
            'monolog.logger.router' => 'getMonolog_Logger_RouterService',
            'monolog.logger.scream' => 'getMonolog_Logger_ScreamService',
            'monolog.logger.security' => 'getMonolog_Logger_SecurityService',
            'monolog.logger.templating' => 'getMonolog_Logger_TemplatingService',
            'property_accessor' => 'getPropertyAccessorService',
            'request' => 'getRequestService',
            'request_stack' => 'getRequestStackService',
            'response_listener' => 'getResponseListenerService',
            'router' => 'getRouterService',
            'router.request_context' => 'getRouter_RequestContextService',
            'router_listener' => 'getRouterListenerService',
            'routing.loader' => 'getRouting_LoaderService',
            'security.access.decision_manager' => 'getSecurity_Access_DecisionManagerService',
            'security.authentication.manager' => 'getSecurity_Authentication_ManagerService',
            'security.authentication.trust_resolver' => 'getSecurity_Authentication_TrustResolverService',
            'security.context' => 'getSecurity_ContextService',
            'security.encoder_factory' => 'getSecurity_EncoderFactoryService',
            'security.firewall' => 'getSecurity_FirewallService',
            'security.firewall.map.context.sulu' => 'getSecurity_Firewall_Map_Context_SuluService',
            'security.rememberme.response_listener' => 'getSecurity_Rememberme_ResponseListenerService',
            'security.secure_random' => 'getSecurity_SecureRandomService',
            'security.validator.user_password' => 'getSecurity_Validator_UserPasswordService',
            'service_container' => 'getServiceContainerService',
            'session' => 'getSessionService',
            'session.handler' => 'getSession_HandlerService',
            'session.storage.filesystem' => 'getSession_Storage_FilesystemService',
            'session.storage.metadata_bag' => 'getSession_Storage_MetadataBagService',
            'session.storage.native' => 'getSession_Storage_NativeService',
            'session.storage.php_bridge' => 'getSession_Storage_PhpBridgeService',
            'session_listener' => 'getSessionListenerService',
            'stof_doctrine_extensions.uploadable.manager' => 'getStofDoctrineExtensions_Uploadable_ManagerService',
            'streamed_response_listener' => 'getStreamedResponseListenerService',
            'sulu.content.localization_finder' => 'getSulu_Content_LocalizationFinderService',
            'sulu.content.mapper' => 'getSulu_Content_MapperService',
            'sulu.content.path_cleaner' => 'getSulu_Content_PathCleanerService',
            'sulu.content.rlp.strategy.tree' => 'getSulu_Content_Rlp_Strategy_TreeService',
            'sulu.content.structure_manager' => 'getSulu_Content_StructureManagerService',
            'sulu.content.type.block' => 'getSulu_Content_Type_BlockService',
            'sulu.content.type.resource_locator' => 'getSulu_Content_Type_ResourceLocatorService',
            'sulu.content.type.text_area' => 'getSulu_Content_Type_TextAreaService',
            'sulu.content.type.text_editor' => 'getSulu_Content_Type_TextEditorService',
            'sulu.content.type.text_line' => 'getSulu_Content_Type_TextLineService',
            'sulu.content.type_manager' => 'getSulu_Content_TypeManagerService',
            'sulu.phpcr.session' => 'getSulu_Phpcr_SessionService',
            'sulu_admin.admin_pool' => 'getSuluAdmin_AdminPoolService',
            'sulu_admin.js_config_pool' => 'getSuluAdmin_JsConfigPoolService',
            'sulu_admin.widgets_handler' => 'getSuluAdmin_WidgetsHandlerService',
            'sulu_category.admin' => 'getSuluCategory_AdminService',
            'sulu_category.admin.content_navigation' => 'getSuluCategory_Admin_ContentNavigationService',
            'sulu_category.category_manager' => 'getSuluCategory_CategoryManagerService',
            'sulu_category.category_repository' => 'getSuluCategory_CategoryRepositoryService',
            'sulu_category.content.type.category_list' => 'getSuluCategory_Content_Type_CategoryListService',
            'sulu_contact.account_listener' => 'getSuluContact_AccountListenerService',
            'sulu_contact.account_manager' => 'getSuluContact_AccountManagerService',
            'sulu_contact.admin' => 'getSuluContact_AdminService',
            'sulu_contact.admin.content_navigation' => 'getSuluContact_Admin_ContentNavigationService',
            'sulu_contact.contact.widgets.account_info' => 'getSuluContact_Contact_Widgets_AccountInfoService',
            'sulu_contact.contact.widgets.contact_info' => 'getSuluContact_Contact_Widgets_ContactInfoService',
            'sulu_contact.contact.widgets.main_account' => 'getSuluContact_Contact_Widgets_MainAccountService',
            'sulu_contact.contact.widgets.main_contact' => 'getSuluContact_Contact_Widgets_MainContactService',
            'sulu_contact.contact.widgets.table' => 'getSuluContact_Contact_Widgets_TableService',
            'sulu_contact.contact.widgets.toolbar' => 'getSuluContact_Contact_Widgets_ToolbarService',
            'sulu_contact.contact_manager' => 'getSuluContact_ContactManagerService',
            'sulu_contact.import' => 'getSuluContact_ImportService',
            'sulu_contact.js_config' => 'getSuluContact_JsConfigService',
            'sulu_contact.twig' => 'getSuluContact_TwigService',
            'sulu_contact.twig.cache' => 'getSuluContact_Twig_CacheService',
            'sulu_contact.user_repository' => 'getSuluContact_UserRepositoryService',
            'sulu_core.doctrine_list_builder_factory' => 'getSuluCore_DoctrineListBuilderFactoryService',
            'sulu_core.doctrine_rest_helper' => 'getSuluCore_DoctrineRestHelperService',
            'sulu_core.list_rest_helper' => 'getSuluCore_ListRestHelperService',
            'sulu_core.rest_helper' => 'getSuluCore_RestHelperService',
            'sulu_core.webspace.loader.xml' => 'getSuluCore_Webspace_Loader_XmlService',
            'sulu_core.webspace.request_analyzer' => 'getSuluCore_Webspace_RequestAnalyzerService',
            'sulu_core.webspace.request_listener' => 'getSuluCore_Webspace_RequestListenerService',
            'sulu_core.webspace.webspace_manager' => 'getSuluCore_Webspace_WebspaceManagerService',
            'sulu_media.admin' => 'getSuluMedia_AdminService',
            'sulu_media.admin.content_navigation' => 'getSuluMedia_Admin_ContentNavigationService',
            'sulu_media.collection_manager' => 'getSuluMedia_CollectionManagerService',
            'sulu_media.collection_repository' => 'getSuluMedia_CollectionRepositoryService',
            'sulu_media.file_validator' => 'getSuluMedia_FileValidatorService',
            'sulu_media.format_cache' => 'getSuluMedia_FormatCacheService',
            'sulu_media.format_manager' => 'getSuluMedia_FormatManagerService',
            'sulu_media.image.command.resize' => 'getSuluMedia_Image_Command_ResizeService',
            'sulu_media.image.command.scale' => 'getSuluMedia_Image_Command_ScaleService',
            'sulu_media.image.command_manager' => 'getSuluMedia_Image_CommandManagerService',
            'sulu_media.image.converter' => 'getSuluMedia_Image_ConverterService',
            'sulu_media.media_manager' => 'getSuluMedia_MediaManagerService',
            'sulu_media.media_repository' => 'getSuluMedia_MediaRepositoryService',
            'sulu_media.storage' => 'getSuluMedia_StorageService',
            'sulu_media.type.media_selection' => 'getSuluMedia_Type_MediaSelectionService',
            'sulu_security.admin' => 'getSuluSecurity_AdminService',
            'sulu_security.admin.roles_navigation' => 'getSuluSecurity_Admin_RolesNavigationService',
            'sulu_security.content_navigation' => 'getSuluSecurity_ContentNavigationService',
            'sulu_security.mask_converter' => 'getSuluSecurity_MaskConverterService',
            'sulu_security.salt_generator' => 'getSuluSecurity_SaltGeneratorService',
            'sulu_security.user_manager' => 'getSuluSecurity_UserManagerService',
            'sulu_security.user_manager.current_user_data' => 'getSuluSecurity_UserManager_CurrentUserDataService',
            'sulu_security.user_repository' => 'getSuluSecurity_UserRepositoryService',
            'sulu_security.user_repository_factory' => 'getSuluSecurity_UserRepositoryFactoryService',
            'sulu_tag.admin' => 'getSuluTag_AdminService',
            'sulu_tag.content.type.tag_list' => 'getSuluTag_Content_Type_TagListService',
            'sulu_tag.tag_manager' => 'getSuluTag_TagManagerService',
            'sulu_tag.tag_repository' => 'getSuluTag_TagRepositoryService',
            'templating' => 'getTemplatingService',
            'templating.asset.package_factory' => 'getTemplating_Asset_PackageFactoryService',
            'templating.cache_warmer.template_paths' => 'getTemplating_CacheWarmer_TemplatePathsService',
            'templating.filename_parser' => 'getTemplating_FilenameParserService',
            'templating.finder' => 'getTemplating_FinderService',
            'templating.globals' => 'getTemplating_GlobalsService',
            'templating.helper.actions' => 'getTemplating_Helper_ActionsService',
            'templating.helper.assets' => 'getTemplating_Helper_AssetsService',
            'templating.helper.code' => 'getTemplating_Helper_CodeService',
            'templating.helper.form' => 'getTemplating_Helper_FormService',
            'templating.helper.logout_url' => 'getTemplating_Helper_LogoutUrlService',
            'templating.helper.request' => 'getTemplating_Helper_RequestService',
            'templating.helper.router' => 'getTemplating_Helper_RouterService',
            'templating.helper.security' => 'getTemplating_Helper_SecurityService',
            'templating.helper.session' => 'getTemplating_Helper_SessionService',
            'templating.helper.slots' => 'getTemplating_Helper_SlotsService',
            'templating.helper.stopwatch' => 'getTemplating_Helper_StopwatchService',
            'templating.helper.translator' => 'getTemplating_Helper_TranslatorService',
            'templating.loader' => 'getTemplating_LoaderService',
            'templating.locator' => 'getTemplating_LocatorService',
            'templating.name_parser' => 'getTemplating_NameParserService',
            'test.client' => 'getTest_ClientService',
            'test.client.cookiejar' => 'getTest_Client_CookiejarService',
            'test.client.history' => 'getTest_Client_HistoryService',
            'test.session.listener' => 'getTest_Session_ListenerService',
            'test_user_provider' => 'getTestUserProviderService',
            'translation.dumper.csv' => 'getTranslation_Dumper_CsvService',
            'translation.dumper.ini' => 'getTranslation_Dumper_IniService',
            'translation.dumper.json' => 'getTranslation_Dumper_JsonService',
            'translation.dumper.mo' => 'getTranslation_Dumper_MoService',
            'translation.dumper.php' => 'getTranslation_Dumper_PhpService',
            'translation.dumper.po' => 'getTranslation_Dumper_PoService',
            'translation.dumper.qt' => 'getTranslation_Dumper_QtService',
            'translation.dumper.res' => 'getTranslation_Dumper_ResService',
            'translation.dumper.xliff' => 'getTranslation_Dumper_XliffService',
            'translation.dumper.yml' => 'getTranslation_Dumper_YmlService',
            'translation.extractor' => 'getTranslation_ExtractorService',
            'translation.extractor.php' => 'getTranslation_Extractor_PhpService',
            'translation.loader' => 'getTranslation_LoaderService',
            'translation.loader.csv' => 'getTranslation_Loader_CsvService',
            'translation.loader.dat' => 'getTranslation_Loader_DatService',
            'translation.loader.ini' => 'getTranslation_Loader_IniService',
            'translation.loader.json' => 'getTranslation_Loader_JsonService',
            'translation.loader.mo' => 'getTranslation_Loader_MoService',
            'translation.loader.php' => 'getTranslation_Loader_PhpService',
            'translation.loader.po' => 'getTranslation_Loader_PoService',
            'translation.loader.qt' => 'getTranslation_Loader_QtService',
            'translation.loader.res' => 'getTranslation_Loader_ResService',
            'translation.loader.xliff' => 'getTranslation_Loader_XliffService',
            'translation.loader.yml' => 'getTranslation_Loader_YmlService',
            'translation.writer' => 'getTranslation_WriterService',
            'translator' => 'getTranslatorService',
            'translator.default' => 'getTranslator_DefaultService',
            'translator.selector' => 'getTranslator_SelectorService',
            'twig' => 'getTwigService',
            'twig.controller.exception' => 'getTwig_Controller_ExceptionService',
            'twig.exception_listener' => 'getTwig_ExceptionListenerService',
            'twig.loader' => 'getTwig_LoaderService',
            'twig.translation.extractor' => 'getTwig_Translation_ExtractorService',
            'uri_signer' => 'getUriSignerService',
        );
        $this->aliases = array(
            'database_connection' => 'doctrine.dbal.default_connection',
            'debug.templating.engine.twig' => 'templating',
            'doctrine.orm.entity_manager' => 'doctrine.orm.default_entity_manager',
            'doctrine_phpcr.odm.document_manager' => 'doctrine_phpcr.odm.default_document_manager',
            'doctrine_phpcr.session' => 'doctrine_phpcr.default_session',
            'event_dispatcher' => 'debug.event_dispatcher',
            'fos_rest.inflector' => 'fos_rest.inflector.doctrine',
            'fos_rest.router' => 'router',
            'fos_rest.serializer' => 'jms_serializer',
            'fos_rest.templating' => 'templating',
            'image.converter.prefix.resize' => 'sulu_media.image.command.resize',
            'image.converter.prefix.scale' => 'sulu_media.image.command.scale',
            'serializer' => 'jms_serializer',
            'session.storage' => 'session.storage.filesystem',
        );
    }

    /**
     * Gets the 'annotation_reader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Common\Annotations\FileCacheReader A Doctrine\Common\Annotations\FileCacheReader instance.
     */
    protected function getAnnotationReaderService()
    {
        return $this->services['annotation_reader'] = new \Doctrine\Common\Annotations\FileCacheReader(new \Doctrine\Common\Annotations\AnnotationReader(), '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/annotations', true);
    }

    /**
     * Gets the 'bazinga_hateoas.expression_language' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Bazinga\Bundle\HateoasBundle\ExpressionLanguage\ExpressionLanguage A Bazinga\Bundle\HateoasBundle\ExpressionLanguage\ExpressionLanguage instance.
     */
    protected function getBazingaHateoas_ExpressionLanguageService()
    {
        return $this->services['bazinga_hateoas.expression_language'] = new \Bazinga\Bundle\HateoasBundle\ExpressionLanguage\ExpressionLanguage();
    }

    /**
     * Gets the 'cache_clearer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer A Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer instance.
     */
    protected function getCacheClearerService()
    {
        return $this->services['cache_clearer'] = new \Symfony\Component\HttpKernel\CacheClearer\ChainCacheClearer(array());
    }

    /**
     * Gets the 'cache_warmer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate A Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate instance.
     */
    protected function getCacheWarmerService()
    {
        return $this->services['cache_warmer'] = new \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate(array(0 => $this->get('templating.cache_warmer.template_paths'), 1 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\RouterCacheWarmer($this->get('router')), 2 => new \Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheCacheWarmer($this, $this->get('templating.finder')), 3 => new \Symfony\Bridge\Doctrine\CacheWarmer\ProxyCacheWarmer($this->get('doctrine')), 4 => new \Symfony\Bridge\Doctrine\CacheWarmer\ProxyCacheWarmer($this->get('doctrine_phpcr'))));
    }

    /**
     * Gets the 'debug.controller_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\Controller\TraceableControllerResolver A Symfony\Component\HttpKernel\Controller\TraceableControllerResolver instance.
     */
    protected function getDebug_ControllerResolverService()
    {
        return $this->services['debug.controller_resolver'] = new \Symfony\Component\HttpKernel\Controller\TraceableControllerResolver(new \Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver($this, $this->get('controller_name_converter'), $this->get('monolog.logger.request', ContainerInterface::NULL_ON_INVALID_REFERENCE)), $this->get('debug.stopwatch'));
    }

    /**
     * Gets the 'debug.debug_handlers_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\DebugHandlersListener A Symfony\Component\HttpKernel\EventListener\DebugHandlersListener instance.
     */
    protected function getDebug_DebugHandlersListenerService()
    {
        return $this->services['debug.debug_handlers_listener'] = new \Symfony\Component\HttpKernel\EventListener\DebugHandlersListener(array(0 => $this->get('http_kernel', ContainerInterface::NULL_ON_INVALID_REFERENCE), 1 => 'terminateWithException'));
    }

    /**
     * Gets the 'debug.deprecation_logger_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener A Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener instance.
     */
    protected function getDebug_DeprecationLoggerListenerService()
    {
        return $this->services['debug.deprecation_logger_listener'] = new \Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener('deprecation', $this->get('monolog.logger.deprecation', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'debug.emergency_logger_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener A Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener instance.
     */
    protected function getDebug_EmergencyLoggerListenerService()
    {
        return $this->services['debug.emergency_logger_listener'] = new \Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener('emergency', $this->get('monolog.logger.emergency', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'debug.event_dispatcher' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher A Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher instance.
     */
    protected function getDebug_EventDispatcherService()
    {
        $this->services['debug.event_dispatcher'] = $instance = new \Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher(new \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher($this), $this->get('debug.stopwatch'), $this->get('monolog.logger.event', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        $instance->addListenerService('kernel.request', array(0 => 'sulu_core.webspace.request_listener', 1 => 'onKernelRequest'), 300);
        $instance->addListenerService('kernel.request', array(0 => 'fos_rest.body_listener', 1 => 'onKernelRequest'), 10);
        $instance->addSubscriberService('response_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ResponseListener');
        $instance->addSubscriberService('streamed_response_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\StreamedResponseListener');
        $instance->addSubscriberService('locale_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\LocaleListener');
        $instance->addSubscriberService('debug.emergency_logger_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ErrorsLoggerListener');
        $instance->addSubscriberService('debug.deprecation_logger_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ErrorsLoggerListener');
        $instance->addSubscriberService('debug.scream_logger_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ErrorsLoggerListener');
        $instance->addSubscriberService('debug.debug_handlers_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\DebugHandlersListener');
        $instance->addSubscriberService('test.session.listener', 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\TestSessionListener');
        $instance->addSubscriberService('session_listener', 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\SessionListener');
        $instance->addSubscriberService('router_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\RouterListener');
        $instance->addSubscriberService('twig.exception_listener', 'Symfony\\Component\\HttpKernel\\EventListener\\ExceptionListener');
        $instance->addSubscriberService('security.firewall', 'Symfony\\Component\\Security\\Http\\Firewall');
        $instance->addSubscriberService('security.rememberme.response_listener', 'Symfony\\Component\\Security\\Http\\RememberMe\\ResponseListener');

        return $instance;
    }

    /**
     * Gets the 'debug.scream_logger_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener A Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener instance.
     */
    protected function getDebug_ScreamLoggerListenerService()
    {
        return $this->services['debug.scream_logger_listener'] = new \Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener('scream', $this->get('monolog.logger.scream', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'debug.stopwatch' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Stopwatch\Stopwatch A Symfony\Component\Stopwatch\Stopwatch instance.
     */
    protected function getDebug_StopwatchService()
    {
        return $this->services['debug.stopwatch'] = new \Symfony\Component\Stopwatch\Stopwatch();
    }

    /**
     * Gets the 'debug.templating.engine.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\TimedPhpEngine A Symfony\Bundle\FrameworkBundle\Templating\TimedPhpEngine instance.
     */
    protected function getDebug_Templating_Engine_PhpService()
    {
        $this->services['debug.templating.engine.php'] = $instance = new \Symfony\Bundle\FrameworkBundle\Templating\TimedPhpEngine($this->get('templating.name_parser'), $this, $this->get('templating.loader'), $this->get('debug.stopwatch'), $this->get('templating.globals'));

        $instance->setCharset('UTF-8');
        $instance->setHelpers(array('slots' => 'templating.helper.slots', 'assets' => 'templating.helper.assets', 'request' => 'templating.helper.request', 'session' => 'templating.helper.session', 'router' => 'templating.helper.router', 'actions' => 'templating.helper.actions', 'code' => 'templating.helper.code', 'translator' => 'templating.helper.translator', 'form' => 'templating.helper.form', 'stopwatch' => 'templating.helper.stopwatch', 'jms_serializer' => 'jms_serializer.templating.helper.serializer', 'logout_url' => 'templating.helper.logout_url', 'security' => 'templating.helper.security'));

        return $instance;
    }

    /**
     * Gets the 'doctrine' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\DoctrineBundle\Registry A Doctrine\Bundle\DoctrineBundle\Registry instance.
     */
    protected function getDoctrineService()
    {
        return $this->services['doctrine'] = new \Doctrine\Bundle\DoctrineBundle\Registry($this, array('default' => 'doctrine.dbal.default_connection'), array('default' => 'doctrine.orm.default_entity_manager'), 'default', 'default');
    }

    /**
     * Gets the 'doctrine.dbal.connection_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\DoctrineBundle\ConnectionFactory A Doctrine\Bundle\DoctrineBundle\ConnectionFactory instance.
     */
    protected function getDoctrine_Dbal_ConnectionFactoryService()
    {
        return $this->services['doctrine.dbal.connection_factory'] = new \Doctrine\Bundle\DoctrineBundle\ConnectionFactory(array());
    }

    /**
     * Gets the 'doctrine.dbal.default_connection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return stdClass A stdClass instance.
     */
    protected function getDoctrine_Dbal_DefaultConnectionService()
    {
        $a = new \Doctrine\DBAL\Logging\LoggerChain();
        $a->addLogger(new \Symfony\Bridge\Doctrine\Logger\DbalLogger($this->get('monolog.logger.doctrine', ContainerInterface::NULL_ON_INVALID_REFERENCE), $this->get('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)));
        $a->addLogger(new \Doctrine\DBAL\Logging\DebugStack());

        $b = new \Doctrine\DBAL\Configuration();
        $b->setSQLLogger($a);

        $c = new \Gedmo\Tree\TreeListener();
        $c->setAnnotationReader($this->get('annotation_reader'));

        $d = new \Doctrine\ORM\Tools\ResolveTargetEntityListener();
        $d->addResolveTargetEntity('Sulu\\Component\\Security\\UserInterface', 'Sulu\\Bundle\\SecurityBundle\\Entity\\User', array());
        $d->addResolveTargetEntity('Sulu\\Bundle\\SecurityBundle\\Entity\\RoleInterface', 'Sulu\\Bundle\\SecurityBundle\\Entity\\Role', array());

        $e = new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this);
        $e->addEventSubscriber($c);
        $e->addEventListener(array(0 => 'loadClassMetadata'), $d);
        $e->addEventListener(array(0 => 'postPersist'), $this->get('sulu_contact.account_listener'));

        return $this->services['doctrine.dbal.default_connection'] = $this->get('doctrine.dbal.connection_factory')->createConnection(array('host' => 'localhost', 'dbname' => 'sulu_test', 'user' => 'root', 'password' => NULL, 'port' => NULL, 'driver' => 'pdo_mysql', 'driverOptions' => array()), $b, $e, array());
    }

    /**
     * Gets the 'doctrine.orm.default_entity_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\ORM\EntityManager A Doctrine\ORM\EntityManager instance.
     */
    protected function getDoctrine_Orm_DefaultEntityManagerService()
    {
        $a = new \Doctrine\Common\Cache\ArrayCache();
        $a->setNamespace('sf2orm_default_4507000dd94b347c82bad511f350090f03170ac183b6d860649f515234fa1541');

        $b = new \Doctrine\Common\Cache\ArrayCache();
        $b->setNamespace('sf2orm_default_4507000dd94b347c82bad511f350090f03170ac183b6d860649f515234fa1541');

        $c = new \Doctrine\Common\Cache\ArrayCache();
        $c->setNamespace('sf2orm_default_4507000dd94b347c82bad511f350090f03170ac183b6d860649f515234fa1541');

        $d = new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver(array('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity' => 'Gedmo\\Tree\\Entity', '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Resources/config/doctrine' => 'Sulu\\Bundle\\SecurityBundle\\Entity', '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/contact-bundle/Sulu/Bundle/ContactBundle/Resources/config/doctrine' => 'Sulu\\Bundle\\ContactBundle\\Entity', '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/test-bundle/Sulu/Bundle/TestBundle/Resources/config/doctrine' => 'Sulu\\Bundle\\TestBundle\\Entity', '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/tag-bundle/Sulu/Bundle/TagBundle/Resources/config/doctrine' => 'Sulu\\Bundle\\TagBundle\\Entity', '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/media-bundle/Sulu/Bundle/MediaBundle/Resources/config/doctrine' => 'Sulu\\Bundle\\MediaBundle\\Entity', '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/category-bundle/Sulu/Bundle/CategoryBundle/Resources/config/doctrine' => 'Sulu\\Bundle\\CategoryBundle\\Entity'));
        $d->setGlobalBasename('mapping');

        $e = new \Doctrine\ORM\Mapping\Driver\DriverChain();
        $e->addDriver($d, 'Gedmo\\Tree\\Entity');
        $e->addDriver($d, 'Sulu\\Bundle\\SecurityBundle\\Entity');
        $e->addDriver($d, 'Sulu\\Bundle\\ContactBundle\\Entity');
        $e->addDriver($d, 'Sulu\\Bundle\\TestBundle\\Entity');
        $e->addDriver($d, 'Sulu\\Bundle\\TagBundle\\Entity');
        $e->addDriver($d, 'Sulu\\Bundle\\MediaBundle\\Entity');
        $e->addDriver($d, 'Sulu\\Bundle\\CategoryBundle\\Entity');
        $e->addDriver(new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/sulu/src/Sulu/Bundle/CoreBundle/Entity')), 'Sulu\\Bundle\\CoreBundle\\Entity');

        $f = new \Doctrine\ORM\Configuration();
        $f->setEntityNamespaces(array('GedmoTree' => 'Gedmo\\Tree\\Entity', 'SuluCoreBundle' => 'Sulu\\Bundle\\CoreBundle\\Entity', 'SuluSecurityBundle' => 'Sulu\\Bundle\\SecurityBundle\\Entity', 'SuluContactBundle' => 'Sulu\\Bundle\\ContactBundle\\Entity', 'SuluTestBundle' => 'Sulu\\Bundle\\TestBundle\\Entity', 'SuluTagBundle' => 'Sulu\\Bundle\\TagBundle\\Entity', 'SuluMediaBundle' => 'Sulu\\Bundle\\MediaBundle\\Entity', 'SuluCategoryBundle' => 'Sulu\\Bundle\\CategoryBundle\\Entity'));
        $f->setMetadataCacheImpl($a);
        $f->setQueryCacheImpl($b);
        $f->setResultCacheImpl($c);
        $f->setMetadataDriverImpl($e);
        $f->setProxyDir('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/doctrine/orm/Proxies');
        $f->setProxyNamespace('Proxies');
        $f->setAutoGenerateProxyClasses(true);
        $f->setClassMetadataFactoryName('Doctrine\\ORM\\Mapping\\ClassMetadataFactory');
        $f->setDefaultRepositoryClassName('Doctrine\\ORM\\EntityRepository');
        $f->setNamingStrategy(new \Doctrine\ORM\Mapping\DefaultNamingStrategy());

        $this->services['doctrine.orm.default_entity_manager'] = $instance = \Doctrine\ORM\EntityManager::create($this->get('doctrine.dbal.default_connection'), $f);

        $this->get('doctrine.orm.default_manager_configurator')->configure($instance);

        return $instance;
    }

    /**
     * Gets the 'doctrine.orm.default_manager_configurator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\DoctrineBundle\ManagerConfigurator A Doctrine\Bundle\DoctrineBundle\ManagerConfigurator instance.
     */
    protected function getDoctrine_Orm_DefaultManagerConfiguratorService()
    {
        return $this->services['doctrine.orm.default_manager_configurator'] = new \Doctrine\Bundle\DoctrineBundle\ManagerConfigurator(array(), array());
    }

    /**
     * Gets the 'doctrine.orm.validator.unique' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator A Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator instance.
     */
    protected function getDoctrine_Orm_Validator_UniqueService()
    {
        return $this->services['doctrine.orm.validator.unique'] = new \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator($this->get('doctrine'));
    }

    /**
     * Gets the 'doctrine.orm.validator_initializer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Doctrine\Validator\DoctrineInitializer A Symfony\Bridge\Doctrine\Validator\DoctrineInitializer instance.
     */
    protected function getDoctrine_Orm_ValidatorInitializerService()
    {
        return $this->services['doctrine.orm.validator_initializer'] = new \Symfony\Bridge\Doctrine\Validator\DoctrineInitializer($this->get('doctrine'));
    }

    /**
     * Gets the 'doctrine_phpcr' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\ManagerRegistry A Doctrine\Bundle\PHPCRBundle\ManagerRegistry instance.
     */
    protected function getDoctrinePhpcrService()
    {
        $this->services['doctrine_phpcr'] = $instance = new \Doctrine\Bundle\PHPCRBundle\ManagerRegistry('PHPCR', array('default' => 'doctrine_phpcr.default_session'), array('default' => 'doctrine_phpcr.odm.default_document_manager'), 'default', 'default', 'Doctrine\\Common\\Proxy\\Proxy');

        $instance->setContainer($this);

        return $instance;
    }

    /**
     * Gets the 'doctrine_phpcr.console_dumper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return PHPCR\Util\Console\Helper\PhpcrConsoleDumperHelper A PHPCR\Util\Console\Helper\PhpcrConsoleDumperHelper instance.
     */
    protected function getDoctrinePhpcr_ConsoleDumperService()
    {
        return $this->services['doctrine_phpcr.console_dumper'] = new \PHPCR\Util\Console\Helper\PhpcrConsoleDumperHelper();
    }

    /**
     * Gets the 'doctrine_phpcr.default_session' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\Session A Jackalope\Session instance.
     */
    protected function getDoctrinePhpcr_DefaultSessionService()
    {
        return $this->services['doctrine_phpcr.default_session'] = $this->get('doctrine_phpcr.jackalope.repository.default')->login(new \PHPCR\SimpleCredentials('admin', 'admin'), 'test');
    }

    /**
     * Gets the 'doctrine_phpcr.initializer_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\Initializer\InitializerManager A Doctrine\Bundle\PHPCRBundle\Initializer\InitializerManager instance.
     */
    protected function getDoctrinePhpcr_InitializerManagerService()
    {
        return $this->services['doctrine_phpcr.initializer_manager'] = new \Doctrine\Bundle\PHPCRBundle\Initializer\InitializerManager($this->get('doctrine_phpcr'));
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope.repository.default' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\Repository A Jackalope\Repository instance.
     */
    protected function getDoctrinePhpcr_Jackalope_Repository_DefaultService()
    {
        return $this->services['doctrine_phpcr.jackalope.repository.default'] = $this->get('doctrine_phpcr.jackalope.repository.factory.service.jackrabbit')->getRepository(array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server/', 'jackalope.check_login_on_server' => false));
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope.repository.factory.doctrinedbal' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\Repository A Jackalope\Repository instance.
     */
    protected function getDoctrinePhpcr_Jackalope_Repository_Factory_DoctrinedbalService()
    {
        return $this->services['doctrine_phpcr.jackalope.repository.factory.doctrinedbal'] = $this->get('doctrine_phpcr.jackalope.repository.factory.service.doctrinedbal')->getRepository(array());
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope.repository.factory.jackrabbit' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\Repository A Jackalope\Repository instance.
     */
    protected function getDoctrinePhpcr_Jackalope_Repository_Factory_JackrabbitService()
    {
        return $this->services['doctrine_phpcr.jackalope.repository.factory.jackrabbit'] = $this->get('doctrine_phpcr.jackalope.repository.factory.service.jackrabbit')->getRepository(array('jackalope.jackrabbit_check_login_on_server' => false));
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope.repository.factory.prismic' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\Repository A Jackalope\Repository instance.
     */
    protected function getDoctrinePhpcr_Jackalope_Repository_Factory_PrismicService()
    {
        return $this->services['doctrine_phpcr.jackalope.repository.factory.prismic'] = $this->get('doctrine_phpcr.jackalope.repository.factory.service.prismic')->getRepository(array('jackalope.prismic_check_login_on_server' => false));
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope.repository.factory.service.doctrinedbal' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\RepositoryFactoryDoctrineDBAL A Jackalope\RepositoryFactoryDoctrineDBAL instance.
     */
    protected function getDoctrinePhpcr_Jackalope_Repository_Factory_Service_DoctrinedbalService()
    {
        return $this->services['doctrine_phpcr.jackalope.repository.factory.service.doctrinedbal'] = new \Jackalope\RepositoryFactoryDoctrineDBAL();
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope.repository.factory.service.jackrabbit' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\RepositoryFactoryJackrabbit A Jackalope\RepositoryFactoryJackrabbit instance.
     */
    protected function getDoctrinePhpcr_Jackalope_Repository_Factory_Service_JackrabbitService()
    {
        return $this->services['doctrine_phpcr.jackalope.repository.factory.service.jackrabbit'] = new \Jackalope\RepositoryFactoryJackrabbit();
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope.repository.factory.service.prismic' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Jackalope\RepositoryFactoryPrismic A Jackalope\RepositoryFactoryPrismic instance.
     */
    protected function getDoctrinePhpcr_Jackalope_Repository_Factory_Service_PrismicService()
    {
        return $this->services['doctrine_phpcr.jackalope.repository.factory.service.prismic'] = new \Jackalope\RepositoryFactoryPrismic();
    }

    /**
     * Gets the 'doctrine_phpcr.jackalope_doctrine_dbal.schema_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\EventListener\JackalopeDoctrineDbalSchemaListener A Doctrine\Bundle\PHPCRBundle\EventListener\JackalopeDoctrineDbalSchemaListener instance.
     */
    protected function getDoctrinePhpcr_JackalopeDoctrineDbal_SchemaListenerService()
    {
        return $this->services['doctrine_phpcr.jackalope_doctrine_dbal.schema_listener'] = new \Doctrine\Bundle\PHPCRBundle\EventListener\JackalopeDoctrineDbalSchemaListener(new \Jackalope\Transport\DoctrineDBAL\RepositorySchema());
    }

    /**
     * Gets the 'doctrine_phpcr.odm.default_document_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\ODM\PHPCR\DocumentManager A Doctrine\ODM\PHPCR\DocumentManager instance.
     */
    protected function getDoctrinePhpcr_Odm_DefaultDocumentManagerService()
    {
        $a = new \Doctrine\Common\Cache\ArrayCache();
        $a->setNamespace('sf2phpcr_default_4507000dd94b347c82bad511f350090f03170ac183b6d860649f515234fa1541');

        $b = new \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
        $b->addDriver(new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($this->get('annotation_reader'), array(0 => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/doctrine/phpcr-odm/lib/Doctrine/ODM/PHPCR/Document')), 'Doctrine\\ODM\\PHPCR\\Document');

        $c = new \Doctrine\ODM\PHPCR\Configuration();
        $c->setDocumentNamespaces(array('__PHPCRODM__' => 'Doctrine\\ODM\\PHPCR\\Document'));
        $c->setMetadataCacheImpl($a);
        $c->setMetadataDriverImpl($b, false);
        $c->setProxyDir('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/doctrine/PHPCRProxies');
        $c->setProxyNamespace('PHPCRProxies');
        $c->setAutoGenerateProxyClasses(false);
        $c->setClassMetadataFactoryName('Doctrine\\ODM\\PHPCR\\Mapping\\ClassMetadataFactory');
        $c->setDefaultRepositoryClassName('Doctrine\\ODM\\PHPCR\\DocumentRepository');

        return $this->services['doctrine_phpcr.odm.default_document_manager'] = new \Doctrine\ODM\PHPCR\DocumentManager($this->get('doctrine_phpcr.default_session'), $c, new \Symfony\Bridge\Doctrine\ContainerAwareEventManager($this));
    }

    /**
     * Gets the 'doctrine_phpcr.odm.form.type.path' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\Form\Type\PathType A Doctrine\Bundle\PHPCRBundle\Form\Type\PathType instance.
     */
    protected function getDoctrinePhpcr_Odm_Form_Type_PathService()
    {
        return $this->services['doctrine_phpcr.odm.form.type.path'] = new \Doctrine\Bundle\PHPCRBundle\Form\Type\PathType($this->get('doctrine_phpcr'));
    }

    /**
     * Gets the 'doctrine_phpcr.odm.validator.valid_phpcr_odm' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\Validator\Constraints\ValidPhpcrOdmValidator A Doctrine\Bundle\PHPCRBundle\Validator\Constraints\ValidPhpcrOdmValidator instance.
     */
    protected function getDoctrinePhpcr_Odm_Validator_ValidPhpcrOdmService()
    {
        return $this->services['doctrine_phpcr.odm.validator.valid_phpcr_odm'] = new \Doctrine\Bundle\PHPCRBundle\Validator\Constraints\ValidPhpcrOdmValidator($this->get('doctrine_phpcr'));
    }

    /**
     * Gets the 'file_locator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\Config\FileLocator A Symfony\Component\HttpKernel\Config\FileLocator instance.
     */
    protected function getFileLocatorService()
    {
        return $this->services['file_locator'] = new \Symfony\Component\HttpKernel\Config\FileLocator($this->get('kernel'), '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/Resources');
    }

    /**
     * Gets the 'filesystem' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Filesystem\Filesystem A Symfony\Component\Filesystem\Filesystem instance.
     */
    protected function getFilesystemService()
    {
        return $this->services['filesystem'] = new \Symfony\Component\Filesystem\Filesystem();
    }

    /**
     * Gets the 'form.type.entity' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Doctrine\Form\Type\EntityType A Symfony\Bridge\Doctrine\Form\Type\EntityType instance.
     */
    protected function getForm_Type_EntityService()
    {
        return $this->services['form.type.entity'] = new \Symfony\Bridge\Doctrine\Form\Type\EntityType($this->get('doctrine'));
    }

    /**
     * Gets the 'form.type.phpcr.document' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\Form\Type\DocumentType A Doctrine\Bundle\PHPCRBundle\Form\Type\DocumentType instance.
     */
    protected function getForm_Type_Phpcr_DocumentService()
    {
        return $this->services['form.type.phpcr.document'] = new \Doctrine\Bundle\PHPCRBundle\Form\Type\DocumentType($this->get('doctrine_phpcr'));
    }

    /**
     * Gets the 'form.type.phpcr.reference' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\Form\Type\PHPCRReferenceType A Doctrine\Bundle\PHPCRBundle\Form\Type\PHPCRReferenceType instance.
     */
    protected function getForm_Type_Phpcr_ReferenceService()
    {
        return $this->services['form.type.phpcr.reference'] = new \Doctrine\Bundle\PHPCRBundle\Form\Type\PHPCRReferenceType($this->get('doctrine_phpcr.default_session'));
    }

    /**
     * Gets the 'form.type.phpcr_odm.reference_collection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\Form\Type\PHPCRODMReferenceCollectionType A Doctrine\Bundle\PHPCRBundle\Form\Type\PHPCRODMReferenceCollectionType instance.
     */
    protected function getForm_Type_PhpcrOdm_ReferenceCollectionService()
    {
        return $this->services['form.type.phpcr_odm.reference_collection'] = new \Doctrine\Bundle\PHPCRBundle\Form\Type\PHPCRODMReferenceCollectionType($this->get('doctrine_phpcr.odm.default_document_manager'));
    }

    /**
     * Gets the 'form.type_guesser.doctrine' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser A Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser instance.
     */
    protected function getForm_TypeGuesser_DoctrineService()
    {
        return $this->services['form.type_guesser.doctrine'] = new \Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser($this->get('doctrine'));
    }

    /**
     * Gets the 'form.type_guesser.doctrine_phpcr' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Bundle\PHPCRBundle\Form\PHPCRTypeGuesser A Doctrine\Bundle\PHPCRBundle\Form\PHPCRTypeGuesser instance.
     */
    protected function getForm_TypeGuesser_DoctrinePhpcrService()
    {
        return $this->services['form.type_guesser.doctrine_phpcr'] = new \Doctrine\Bundle\PHPCRBundle\Form\PHPCRTypeGuesser($this->get('doctrine_phpcr'), array());
    }

    /**
     * Gets the 'fos_rest.body_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\EventListener\BodyListener A FOS\RestBundle\EventListener\BodyListener instance.
     */
    protected function getFosRest_BodyListenerService()
    {
        return $this->services['fos_rest.body_listener'] = new \FOS\RestBundle\EventListener\BodyListener($this->get('fos_rest.decoder_provider'), false);
    }

    /**
     * Gets the 'fos_rest.decoder.json' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Decoder\JsonDecoder A FOS\RestBundle\Decoder\JsonDecoder instance.
     */
    protected function getFosRest_Decoder_JsonService()
    {
        return $this->services['fos_rest.decoder.json'] = new \FOS\RestBundle\Decoder\JsonDecoder();
    }

    /**
     * Gets the 'fos_rest.decoder.jsontoform' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Decoder\JsonToFormDecoder A FOS\RestBundle\Decoder\JsonToFormDecoder instance.
     */
    protected function getFosRest_Decoder_JsontoformService()
    {
        return $this->services['fos_rest.decoder.jsontoform'] = new \FOS\RestBundle\Decoder\JsonToFormDecoder();
    }

    /**
     * Gets the 'fos_rest.decoder.xml' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Decoder\XmlDecoder A FOS\RestBundle\Decoder\XmlDecoder instance.
     */
    protected function getFosRest_Decoder_XmlService()
    {
        return $this->services['fos_rest.decoder.xml'] = new \FOS\RestBundle\Decoder\XmlDecoder();
    }

    /**
     * Gets the 'fos_rest.decoder_provider' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Decoder\ContainerDecoderProvider A FOS\RestBundle\Decoder\ContainerDecoderProvider instance.
     */
    protected function getFosRest_DecoderProviderService()
    {
        $this->services['fos_rest.decoder_provider'] = $instance = new \FOS\RestBundle\Decoder\ContainerDecoderProvider(array('json' => 'fos_rest.decoder.json', 'xml' => 'fos_rest.decoder.xml'));

        $instance->setContainer($this);

        return $instance;
    }

    /**
     * Gets the 'fos_rest.format_negotiator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Util\FormatNegotiator A FOS\RestBundle\Util\FormatNegotiator instance.
     */
    protected function getFosRest_FormatNegotiatorService()
    {
        return $this->services['fos_rest.format_negotiator'] = new \FOS\RestBundle\Util\FormatNegotiator();
    }

    /**
     * Gets the 'fos_rest.inflector.doctrine' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Util\Inflector\DoctrineInflector A FOS\RestBundle\Util\Inflector\DoctrineInflector instance.
     */
    protected function getFosRest_Inflector_DoctrineService()
    {
        return $this->services['fos_rest.inflector.doctrine'] = new \FOS\RestBundle\Util\Inflector\DoctrineInflector();
    }

    /**
     * Gets the 'fos_rest.normalizer.camel_keys' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Normalizer\CamelKeysNormalizer A FOS\RestBundle\Normalizer\CamelKeysNormalizer instance.
     */
    protected function getFosRest_Normalizer_CamelKeysService()
    {
        return $this->services['fos_rest.normalizer.camel_keys'] = new \FOS\RestBundle\Normalizer\CamelKeysNormalizer();
    }

    /**
     * Gets the 'fos_rest.request.param_fetcher' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Request\ParamFetcher A FOS\RestBundle\Request\ParamFetcher instance.
     * 
     * @throws InactiveScopeException when the 'fos_rest.request.param_fetcher' service is requested while the 'request' scope is not active
     */
    protected function getFosRest_Request_ParamFetcherService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('fos_rest.request.param_fetcher', 'request');
        }

        return $this->services['fos_rest.request.param_fetcher'] = $this->scopedServices['request']['fos_rest.request.param_fetcher'] = new \FOS\RestBundle\Request\ParamFetcher($this->get('fos_rest.request.param_fetcher.reader'), $this->get('request'), $this->get('fos_rest.violation_formatter'), NULL);
    }

    /**
     * Gets the 'fos_rest.request.param_fetcher.reader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Request\ParamReader A FOS\RestBundle\Request\ParamReader instance.
     */
    protected function getFosRest_Request_ParamFetcher_ReaderService()
    {
        return $this->services['fos_rest.request.param_fetcher.reader'] = new \FOS\RestBundle\Request\ParamReader($this->get('annotation_reader'));
    }

    /**
     * Gets the 'fos_rest.routing.loader.controller' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Routing\Loader\RestRouteLoader A FOS\RestBundle\Routing\Loader\RestRouteLoader instance.
     */
    protected function getFosRest_Routing_Loader_ControllerService()
    {
        return $this->services['fos_rest.routing.loader.controller'] = new \FOS\RestBundle\Routing\Loader\RestRouteLoader($this, $this->get('file_locator'), $this->get('controller_name_converter'), $this->get('fos_rest.routing.loader.reader.controller'), 'json');
    }

    /**
     * Gets the 'fos_rest.routing.loader.processor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Routing\Loader\RestRouteProcessor A FOS\RestBundle\Routing\Loader\RestRouteProcessor instance.
     */
    protected function getFosRest_Routing_Loader_ProcessorService()
    {
        return $this->services['fos_rest.routing.loader.processor'] = new \FOS\RestBundle\Routing\Loader\RestRouteProcessor();
    }

    /**
     * Gets the 'fos_rest.routing.loader.reader.action' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Routing\Loader\Reader\RestActionReader A FOS\RestBundle\Routing\Loader\Reader\RestActionReader instance.
     */
    protected function getFosRest_Routing_Loader_Reader_ActionService()
    {
        return $this->services['fos_rest.routing.loader.reader.action'] = new \FOS\RestBundle\Routing\Loader\Reader\RestActionReader($this->get('annotation_reader'), $this->get('fos_rest.request.param_fetcher.reader'), $this->get('fos_rest.inflector.doctrine'), true, array('json' => false, 'xml' => false, 'html' => true));
    }

    /**
     * Gets the 'fos_rest.routing.loader.reader.controller' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Routing\Loader\Reader\RestControllerReader A FOS\RestBundle\Routing\Loader\Reader\RestControllerReader instance.
     */
    protected function getFosRest_Routing_Loader_Reader_ControllerService()
    {
        return $this->services['fos_rest.routing.loader.reader.controller'] = new \FOS\RestBundle\Routing\Loader\Reader\RestControllerReader($this->get('fos_rest.routing.loader.reader.action'), $this->get('annotation_reader'));
    }

    /**
     * Gets the 'fos_rest.routing.loader.xml_collection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Routing\Loader\RestXmlCollectionLoader A FOS\RestBundle\Routing\Loader\RestXmlCollectionLoader instance.
     */
    protected function getFosRest_Routing_Loader_XmlCollectionService()
    {
        return $this->services['fos_rest.routing.loader.xml_collection'] = new \FOS\RestBundle\Routing\Loader\RestXmlCollectionLoader($this->get('file_locator'), $this->get('fos_rest.routing.loader.processor'), true, array('json' => false, 'xml' => false, 'html' => true), 'json');
    }

    /**
     * Gets the 'fos_rest.routing.loader.yaml_collection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Routing\Loader\RestYamlCollectionLoader A FOS\RestBundle\Routing\Loader\RestYamlCollectionLoader instance.
     */
    protected function getFosRest_Routing_Loader_YamlCollectionService()
    {
        return $this->services['fos_rest.routing.loader.yaml_collection'] = new \FOS\RestBundle\Routing\Loader\RestYamlCollectionLoader($this->get('file_locator'), $this->get('fos_rest.routing.loader.processor'), true, array('json' => false, 'xml' => false, 'html' => true), 'json');
    }

    /**
     * Gets the 'fos_rest.serializer.exception_wrapper_serialize_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Serializer\ExceptionWrapperSerializeHandler A FOS\RestBundle\Serializer\ExceptionWrapperSerializeHandler instance.
     */
    protected function getFosRest_Serializer_ExceptionWrapperSerializeHandlerService()
    {
        return $this->services['fos_rest.serializer.exception_wrapper_serialize_handler'] = new \FOS\RestBundle\Serializer\ExceptionWrapperSerializeHandler();
    }

    /**
     * Gets the 'fos_rest.view.exception_wrapper_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\View\ExceptionWrapperHandler A FOS\RestBundle\View\ExceptionWrapperHandler instance.
     */
    protected function getFosRest_View_ExceptionWrapperHandlerService()
    {
        return $this->services['fos_rest.view.exception_wrapper_handler'] = new \FOS\RestBundle\View\ExceptionWrapperHandler();
    }

    /**
     * Gets the 'fos_rest.view_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\View\ViewHandler A FOS\RestBundle\View\ViewHandler instance.
     */
    protected function getFosRest_ViewHandlerService()
    {
        $this->services['fos_rest.view_handler'] = $instance = new \FOS\RestBundle\View\ViewHandler(array('json' => false, 'xml' => false, 'html' => true), 400, 204, false, array('html' => 302), 'twig');

        $instance->setExclusionStrategyGroups('');
        $instance->setExclusionStrategyVersion('');
        $instance->setSerializeNullStrategy(false);
        $instance->setContainer($this);

        return $instance;
    }

    /**
     * Gets the 'fos_rest.violation_formatter' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return FOS\RestBundle\Util\ViolationFormatter A FOS\RestBundle\Util\ViolationFormatter instance.
     */
    protected function getFosRest_ViolationFormatterService()
    {
        return $this->services['fos_rest.violation_formatter'] = new \FOS\RestBundle\Util\ViolationFormatter();
    }

    /**
     * Gets the 'fragment.handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\Fragment\FragmentHandler A Symfony\Component\HttpKernel\Fragment\FragmentHandler instance.
     */
    protected function getFragment_HandlerService()
    {
        $this->services['fragment.handler'] = $instance = new \Symfony\Component\HttpKernel\Fragment\FragmentHandler(array(), true, $this->get('request_stack'));

        $instance->addRenderer($this->get('fragment.renderer.inline'));
        $instance->addRenderer($this->get('fragment.renderer.hinclude'));
        $instance->addRenderer($this->get('fragment.renderer.esi'));

        return $instance;
    }

    /**
     * Gets the 'fragment.renderer.esi' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer A Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer instance.
     */
    protected function getFragment_Renderer_EsiService()
    {
        $this->services['fragment.renderer.esi'] = $instance = new \Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer(NULL, $this->get('fragment.renderer.inline'));

        $instance->setFragmentPath('/_fragment');

        return $instance;
    }

    /**
     * Gets the 'fragment.renderer.hinclude' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Fragment\ContainerAwareHIncludeFragmentRenderer A Symfony\Bundle\FrameworkBundle\Fragment\ContainerAwareHIncludeFragmentRenderer instance.
     */
    protected function getFragment_Renderer_HincludeService()
    {
        $this->services['fragment.renderer.hinclude'] = $instance = new \Symfony\Bundle\FrameworkBundle\Fragment\ContainerAwareHIncludeFragmentRenderer($this, $this->get('uri_signer'), NULL);

        $instance->setFragmentPath('/_fragment');

        return $instance;
    }

    /**
     * Gets the 'fragment.renderer.inline' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer A Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer instance.
     */
    protected function getFragment_Renderer_InlineService()
    {
        $this->services['fragment.renderer.inline'] = $instance = new \Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer($this->get('http_kernel'), $this->get('debug.event_dispatcher'));

        $instance->setFragmentPath('/_fragment');

        return $instance;
    }

    /**
     * Gets the 'hateoas.configuration.provider.resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Hateoas\Configuration\Provider\Resolver\ChainResolver A Hateoas\Configuration\Provider\Resolver\ChainResolver instance.
     */
    protected function getHateoas_Configuration_Provider_ResolverService()
    {
        return $this->services['hateoas.configuration.provider.resolver'] = new \Hateoas\Configuration\Provider\Resolver\ChainResolver(array(0 => new \Hateoas\Configuration\Provider\Resolver\MethodResolver(), 1 => new \Hateoas\Configuration\Provider\Resolver\StaticMethodResolver(), 2 => new \Hateoas\Configuration\Provider\Resolver\SymfonyContainerResolver($this)));
    }

    /**
     * Gets the 'hateoas.event_subscriber.json' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Hateoas\Serializer\EventSubscriber\JsonEventSubscriber A Hateoas\Serializer\EventSubscriber\JsonEventSubscriber instance.
     */
    protected function getHateoas_EventSubscriber_JsonService()
    {
        $a = $this->get('jms_serializer.metadata_factory');

        return $this->services['hateoas.event_subscriber.json'] = new \Hateoas\Serializer\EventSubscriber\JsonEventSubscriber(new \Hateoas\Serializer\JsonHalSerializer(), $this->get('hateoas.links_factory'), $this->get('hateoas.embeds_factory'), new \Hateoas\Serializer\Metadata\InlineDeferrer($a), new \Hateoas\Serializer\Metadata\InlineDeferrer($a));
    }

    /**
     * Gets the 'hateoas.event_subscriber.xml' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Hateoas\Serializer\EventSubscriber\XmlEventSubscriber A Hateoas\Serializer\EventSubscriber\XmlEventSubscriber instance.
     */
    protected function getHateoas_EventSubscriber_XmlService()
    {
        $a = new \Hateoas\Serializer\XmlSerializer();
        $a->setMetadataFactory($this->get('jms_serializer.metadata_factory'));

        return $this->services['hateoas.event_subscriber.xml'] = new \Hateoas\Serializer\EventSubscriber\XmlEventSubscriber($a, $this->get('hateoas.links_factory'), $this->get('hateoas.embeds_factory'));
    }

    /**
     * Gets the 'hateoas.expression.evaluator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Hateoas\Expression\ExpressionEvaluator A Hateoas\Expression\ExpressionEvaluator instance.
     */
    protected function getHateoas_Expression_EvaluatorService()
    {
        $this->services['hateoas.expression.evaluator'] = $instance = new \Hateoas\Expression\ExpressionEvaluator($this->get('bazinga_hateoas.expression_language'));

        $instance->setContextVariable('container', $this);

        return $instance;
    }

    /**
     * Gets the 'hateoas.generator.registry' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Hateoas\UrlGenerator\UrlGeneratorRegistry A Hateoas\UrlGenerator\UrlGeneratorRegistry instance.
     */
    protected function getHateoas_Generator_RegistryService()
    {
        return $this->services['hateoas.generator.registry'] = new \Hateoas\UrlGenerator\UrlGeneratorRegistry($this->get('hateoas.generator.symfony'));
    }

    /**
     * Gets the 'hateoas.generator.symfony' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Hateoas\UrlGenerator\SymfonyUrlGenerator A Hateoas\UrlGenerator\SymfonyUrlGenerator instance.
     */
    protected function getHateoas_Generator_SymfonyService()
    {
        return $this->services['hateoas.generator.symfony'] = new \Hateoas\UrlGenerator\SymfonyUrlGenerator($this->get('router'));
    }

    /**
     * Gets the 'hateoas.serializer.exclusion_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Hateoas\Serializer\ExclusionManager A Hateoas\Serializer\ExclusionManager instance.
     */
    protected function getHateoas_Serializer_ExclusionManagerService()
    {
        return $this->services['hateoas.serializer.exclusion_manager'] = new \Hateoas\Serializer\ExclusionManager($this->get('hateoas.expression.evaluator'));
    }

    /**
     * Gets the 'http_kernel' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel A Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel instance.
     */
    protected function getHttpKernelService()
    {
        return $this->services['http_kernel'] = new \Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel($this->get('debug.event_dispatcher'), $this, $this->get('debug.controller_resolver'), $this->get('request_stack'));
    }

    /**
     * Gets the 'jms_serializer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Serializer A JMS\Serializer\Serializer instance.
     */
    protected function getJmsSerializerService()
    {
        $a = new \JMS\Serializer\EventDispatcher\LazyEventDispatcher($this);
        $a->setListeners(array('serializer.pre_serialize' => array(0 => array(0 => array(0 => 'jms_serializer.stopwatch_subscriber', 1 => 'onPreSerialize'), 1 => NULL, 2 => NULL), 1 => array(0 => array(0 => 'jms_serializer.doctrine_proxy_subscriber', 1 => 'onPreSerialize'), 1 => NULL, 2 => NULL)), 'serializer.post_serialize' => array(0 => array(0 => array(0 => 'hateoas.event_subscriber.xml', 1 => 'onPostSerialize'), 1 => NULL, 2 => 'xml'), 1 => array(0 => array(0 => 'hateoas.event_subscriber.json', 1 => 'onPostSerialize'), 1 => NULL, 2 => 'json'), 2 => array(0 => array(0 => 'jms_serializer.stopwatch_subscriber', 1 => 'onPostSerialize'), 1 => NULL, 2 => NULL))));

        return $this->services['jms_serializer'] = new \JMS\Serializer\Serializer($this->get('jms_serializer.metadata_factory'), $this->get('jms_serializer.handler_registry'), $this->get('jms_serializer.unserialize_object_constructor'), new \PhpCollection\Map(array('json' => $this->get('jms_serializer.json_serialization_visitor'), 'xml' => $this->get('jms_serializer.xml_serialization_visitor'), 'yml' => $this->get('jms_serializer.yaml_serialization_visitor'))), new \PhpCollection\Map(array('json' => $this->get('jms_serializer.json_deserialization_visitor'), 'xml' => $this->get('jms_serializer.xml_deserialization_visitor'))), $a);
    }

    /**
     * Gets the 'jms_serializer.array_collection_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Handler\ArrayCollectionHandler A JMS\Serializer\Handler\ArrayCollectionHandler instance.
     */
    protected function getJmsSerializer_ArrayCollectionHandlerService()
    {
        return $this->services['jms_serializer.array_collection_handler'] = new \JMS\Serializer\Handler\ArrayCollectionHandler();
    }

    /**
     * Gets the 'jms_serializer.constraint_violation_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Handler\ConstraintViolationHandler A JMS\Serializer\Handler\ConstraintViolationHandler instance.
     */
    protected function getJmsSerializer_ConstraintViolationHandlerService()
    {
        return $this->services['jms_serializer.constraint_violation_handler'] = new \JMS\Serializer\Handler\ConstraintViolationHandler();
    }

    /**
     * Gets the 'jms_serializer.datetime_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Handler\DateHandler A JMS\Serializer\Handler\DateHandler instance.
     */
    protected function getJmsSerializer_DatetimeHandlerService()
    {
        return $this->services['jms_serializer.datetime_handler'] = new \JMS\Serializer\Handler\DateHandler('Y-m-d\\TH:i:sO', 'Europe/Vienna', true);
    }

    /**
     * Gets the 'jms_serializer.doctrine_proxy_subscriber' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber A JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber instance.
     */
    protected function getJmsSerializer_DoctrineProxySubscriberService()
    {
        return $this->services['jms_serializer.doctrine_proxy_subscriber'] = new \JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber();
    }

    /**
     * Gets the 'jms_serializer.form_error_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Handler\FormErrorHandler A JMS\Serializer\Handler\FormErrorHandler instance.
     */
    protected function getJmsSerializer_FormErrorHandlerService()
    {
        return $this->services['jms_serializer.form_error_handler'] = new \JMS\Serializer\Handler\FormErrorHandler($this->get('translator'));
    }

    /**
     * Gets the 'jms_serializer.handler_registry' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Handler\LazyHandlerRegistry A JMS\Serializer\Handler\LazyHandlerRegistry instance.
     */
    protected function getJmsSerializer_HandlerRegistryService()
    {
        return $this->services['jms_serializer.handler_registry'] = new \JMS\Serializer\Handler\LazyHandlerRegistry($this, array(2 => array('DateTime' => array('json' => array(0 => 'jms_serializer.datetime_handler', 1 => 'deserializeDateTimeFromjson'), 'xml' => array(0 => 'jms_serializer.datetime_handler', 1 => 'deserializeDateTimeFromxml'), 'yml' => array(0 => 'jms_serializer.datetime_handler', 1 => 'deserializeDateTimeFromyml')), 'ArrayCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection')), 'Doctrine\\Common\\Collections\\ArrayCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection')), 'Doctrine\\ORM\\PersistentCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection')), 'Doctrine\\ODM\\MongoDB\\PersistentCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection')), 'Doctrine\\ODM\\PHPCR\\PersistentCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'deserializeCollection')), 'PhpCollection\\Sequence' => array('json' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'deserializeSequence'), 'xml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'deserializeSequence'), 'yml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'deserializeSequence')), 'PhpCollection\\Map' => array('json' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'deserializeMap'), 'xml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'deserializeMap'), 'yml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'deserializeMap'))), 1 => array('DateTime' => array('json' => array(0 => 'jms_serializer.datetime_handler', 1 => 'serializeDateTime'), 'xml' => array(0 => 'jms_serializer.datetime_handler', 1 => 'serializeDateTime'), 'yml' => array(0 => 'jms_serializer.datetime_handler', 1 => 'serializeDateTime')), 'DateInterval' => array('json' => array(0 => 'jms_serializer.datetime_handler', 1 => 'serializeDateInterval'), 'xml' => array(0 => 'jms_serializer.datetime_handler', 1 => 'serializeDateInterval'), 'yml' => array(0 => 'jms_serializer.datetime_handler', 1 => 'serializeDateInterval')), 'ArrayCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection')), 'Doctrine\\Common\\Collections\\ArrayCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection')), 'Doctrine\\ORM\\PersistentCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection')), 'Doctrine\\ODM\\MongoDB\\PersistentCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection')), 'Doctrine\\ODM\\PHPCR\\PersistentCollection' => array('json' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'xml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection'), 'yml' => array(0 => 'jms_serializer.array_collection_handler', 1 => 'serializeCollection')), 'PhpCollection\\Sequence' => array('json' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'serializeSequence'), 'xml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'serializeSequence'), 'yml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'serializeSequence')), 'PhpCollection\\Map' => array('json' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'serializeMap'), 'xml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'serializeMap'), 'yml' => array(0 => 'jms_serializer.php_collection_handler', 1 => 'serializeMap')), 'Symfony\\Component\\Form\\Form' => array('xml' => array(0 => 'jms_serializer.form_error_handler', 1 => 'serializeFormToxml'), 'json' => array(0 => 'jms_serializer.form_error_handler', 1 => 'serializeFormTojson'), 'yml' => array(0 => 'jms_serializer.form_error_handler', 1 => 'serializeFormToyml')), 'Symfony\\Component\\Form\\FormError' => array('xml' => array(0 => 'jms_serializer.form_error_handler', 1 => 'serializeFormErrorToxml'), 'json' => array(0 => 'jms_serializer.form_error_handler', 1 => 'serializeFormErrorTojson'), 'yml' => array(0 => 'jms_serializer.form_error_handler', 1 => 'serializeFormErrorToyml')), 'Symfony\\Component\\Validator\\ConstraintViolationList' => array('xml' => array(0 => 'jms_serializer.constraint_violation_handler', 1 => 'serializeListToxml'), 'json' => array(0 => 'jms_serializer.constraint_violation_handler', 1 => 'serializeListTojson'), 'yml' => array(0 => 'jms_serializer.constraint_violation_handler', 1 => 'serializeListToyml')), 'Symfony\\Component\\Validator\\ConstraintViolation' => array('xml' => array(0 => 'jms_serializer.constraint_violation_handler', 1 => 'serializeViolationToxml'), 'json' => array(0 => 'jms_serializer.constraint_violation_handler', 1 => 'serializeViolationTojson'), 'yml' => array(0 => 'jms_serializer.constraint_violation_handler', 1 => 'serializeViolationToyml')), 'FOS\\RestBundle\\Util\\ExceptionWrapper' => array('json' => array(0 => 'fos_rest.serializer.exception_wrapper_serialize_handler', 1 => 'serializeToJson'), 'xml' => array(0 => 'fos_rest.serializer.exception_wrapper_serialize_handler', 1 => 'serializeToXml')))));
    }

    /**
     * Gets the 'jms_serializer.json_deserialization_visitor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\JsonDeserializationVisitor A JMS\Serializer\JsonDeserializationVisitor instance.
     */
    protected function getJmsSerializer_JsonDeserializationVisitorService()
    {
        return $this->services['jms_serializer.json_deserialization_visitor'] = new \JMS\Serializer\JsonDeserializationVisitor($this->get('jms_serializer.naming_strategy'), $this->get('jms_serializer.unserialize_object_constructor'));
    }

    /**
     * Gets the 'jms_serializer.json_serialization_visitor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\JsonSerializationVisitor A JMS\Serializer\JsonSerializationVisitor instance.
     */
    protected function getJmsSerializer_JsonSerializationVisitorService()
    {
        $this->services['jms_serializer.json_serialization_visitor'] = $instance = new \JMS\Serializer\JsonSerializationVisitor($this->get('jms_serializer.naming_strategy'));

        $instance->setOptions(0);

        return $instance;
    }

    /**
     * Gets the 'jms_serializer.metadata_driver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Metadata\Driver\DoctrinePHPCRTypeDriver A JMS\Serializer\Metadata\Driver\DoctrinePHPCRTypeDriver instance.
     */
    protected function getJmsSerializer_MetadataDriverService()
    {
        $a = $this->get('jms_serializer.metadata.file_locator');

        return $this->services['jms_serializer.metadata_driver'] = new \JMS\Serializer\Metadata\Driver\DoctrinePHPCRTypeDriver(new \Metadata\Driver\DriverChain(array(0 => new \JMS\Serializer\Metadata\Driver\YamlDriver($a), 1 => new \JMS\Serializer\Metadata\Driver\XmlDriver($a), 2 => new \JMS\Serializer\Metadata\Driver\PhpDriver($a), 3 => new \JMS\Serializer\Metadata\Driver\AnnotationDriver($this->get('annotation_reader')))), $this->get('doctrine_phpcr'));
    }

    /**
     * Gets the 'jms_serializer.naming_strategy' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Naming\IdenticalPropertyNamingStrategy A JMS\Serializer\Naming\IdenticalPropertyNamingStrategy instance.
     */
    protected function getJmsSerializer_NamingStrategyService()
    {
        return $this->services['jms_serializer.naming_strategy'] = new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy(new \JMS\Serializer\Naming\SerializedNameAnnotationStrategy(new \JMS\Serializer\Naming\CamelCaseNamingStrategy('_', true)));
    }

    /**
     * Gets the 'jms_serializer.object_constructor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Construction\DoctrineObjectConstructor A JMS\Serializer\Construction\DoctrineObjectConstructor instance.
     */
    protected function getJmsSerializer_ObjectConstructorService()
    {
        return $this->services['jms_serializer.object_constructor'] = new \JMS\Serializer\Construction\DoctrineObjectConstructor($this->get('doctrine_phpcr'), $this->get('jms_serializer.unserialize_object_constructor'));
    }

    /**
     * Gets the 'jms_serializer.php_collection_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\Handler\PhpCollectionHandler A JMS\Serializer\Handler\PhpCollectionHandler instance.
     */
    protected function getJmsSerializer_PhpCollectionHandlerService()
    {
        return $this->services['jms_serializer.php_collection_handler'] = new \JMS\Serializer\Handler\PhpCollectionHandler();
    }

    /**
     * Gets the 'jms_serializer.stopwatch_subscriber' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\SerializerBundle\Serializer\StopwatchEventSubscriber A JMS\SerializerBundle\Serializer\StopwatchEventSubscriber instance.
     */
    protected function getJmsSerializer_StopwatchSubscriberService()
    {
        return $this->services['jms_serializer.stopwatch_subscriber'] = new \JMS\SerializerBundle\Serializer\StopwatchEventSubscriber($this->get('debug.stopwatch'));
    }

    /**
     * Gets the 'jms_serializer.templating.helper.serializer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\SerializerBundle\Templating\SerializerHelper A JMS\SerializerBundle\Templating\SerializerHelper instance.
     */
    protected function getJmsSerializer_Templating_Helper_SerializerService()
    {
        return $this->services['jms_serializer.templating.helper.serializer'] = new \JMS\SerializerBundle\Templating\SerializerHelper($this->get('jms_serializer'));
    }

    /**
     * Gets the 'jms_serializer.xml_deserialization_visitor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\XmlDeserializationVisitor A JMS\Serializer\XmlDeserializationVisitor instance.
     */
    protected function getJmsSerializer_XmlDeserializationVisitorService()
    {
        $this->services['jms_serializer.xml_deserialization_visitor'] = $instance = new \JMS\Serializer\XmlDeserializationVisitor($this->get('jms_serializer.naming_strategy'), $this->get('jms_serializer.unserialize_object_constructor'));

        $instance->setDoctypeWhitelist(array());

        return $instance;
    }

    /**
     * Gets the 'jms_serializer.xml_serialization_visitor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\XmlSerializationVisitor A JMS\Serializer\XmlSerializationVisitor instance.
     */
    protected function getJmsSerializer_XmlSerializationVisitorService()
    {
        return $this->services['jms_serializer.xml_serialization_visitor'] = new \JMS\Serializer\XmlSerializationVisitor($this->get('jms_serializer.naming_strategy'));
    }

    /**
     * Gets the 'jms_serializer.yaml_serialization_visitor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return JMS\Serializer\YamlSerializationVisitor A JMS\Serializer\YamlSerializationVisitor instance.
     */
    protected function getJmsSerializer_YamlSerializationVisitorService()
    {
        return $this->services['jms_serializer.yaml_serialization_visitor'] = new \JMS\Serializer\YamlSerializationVisitor($this->get('jms_serializer.naming_strategy'));
    }

    /**
     * Gets the 'kernel' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @throws RuntimeException always since this service is expected to be injected dynamically
     */
    protected function getKernelService()
    {
        throw new RuntimeException('You have requested a synthetic service ("kernel"). The DIC does not know how to construct this service.');
    }

    /**
     * Gets the 'liip_theme.active_theme' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Liip\ThemeBundle\ActiveTheme A Liip\ThemeBundle\ActiveTheme instance.
     */
    protected function getLiipTheme_ActiveThemeService()
    {
        return $this->services['liip_theme.active_theme'] = new \Liip\ThemeBundle\ActiveTheme(NULL, array());
    }

    /**
     * Gets the 'liip_theme.theme_controller' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Liip\ThemeBundle\Controller\ThemeController A Liip\ThemeBundle\Controller\ThemeController instance.
     */
    protected function getLiipTheme_ThemeControllerService()
    {
        return $this->services['liip_theme.theme_controller'] = new \Liip\ThemeBundle\Controller\ThemeController($this->get('liip_theme.active_theme'), array(), NULL);
    }

    /**
     * Gets the 'locale_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\LocaleListener A Symfony\Component\HttpKernel\EventListener\LocaleListener instance.
     */
    protected function getLocaleListenerService()
    {
        return $this->services['locale_listener'] = new \Symfony\Component\HttpKernel\EventListener\LocaleListener('en', $this->get('router', ContainerInterface::NULL_ON_INVALID_REFERENCE), $this->get('request_stack'));
    }

    /**
     * Gets the 'logger' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getLoggerService()
    {
        $this->services['logger'] = $instance = new \Symfony\Bridge\Monolog\Logger('app');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.handler.main' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Monolog\Handler\FingersCrossedHandler A Monolog\Handler\FingersCrossedHandler instance.
     */
    protected function getMonolog_Handler_MainService()
    {
        return $this->services['monolog.handler.main'] = new \Monolog\Handler\FingersCrossedHandler($this->get('monolog.handler.nested'), 400, 0, true, true);
    }

    /**
     * Gets the 'monolog.handler.nested' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Monolog\Handler\StreamHandler A Monolog\Handler\StreamHandler instance.
     */
    protected function getMonolog_Handler_NestedService()
    {
        return $this->services['monolog.handler.nested'] = new \Monolog\Handler\StreamHandler('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/logs/test.log', 100, true);
    }

    /**
     * Gets the 'monolog.logger.deprecation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_DeprecationService()
    {
        $this->services['monolog.logger.deprecation'] = $instance = new \Symfony\Bridge\Monolog\Logger('deprecation');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.doctrine' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_DoctrineService()
    {
        $this->services['monolog.logger.doctrine'] = $instance = new \Symfony\Bridge\Monolog\Logger('doctrine');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.doctrine_phpcr' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_DoctrinePhpcrService()
    {
        $this->services['monolog.logger.doctrine_phpcr'] = $instance = new \Symfony\Bridge\Monolog\Logger('doctrine_phpcr');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.emergency' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_EmergencyService()
    {
        $this->services['monolog.logger.emergency'] = $instance = new \Symfony\Bridge\Monolog\Logger('emergency');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.event' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_EventService()
    {
        $this->services['monolog.logger.event'] = $instance = new \Symfony\Bridge\Monolog\Logger('event');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.request' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_RequestService()
    {
        $this->services['monolog.logger.request'] = $instance = new \Symfony\Bridge\Monolog\Logger('request');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.router' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_RouterService()
    {
        $this->services['monolog.logger.router'] = $instance = new \Symfony\Bridge\Monolog\Logger('router');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.scream' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_ScreamService()
    {
        $this->services['monolog.logger.scream'] = $instance = new \Symfony\Bridge\Monolog\Logger('scream');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.security' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_SecurityService()
    {
        $this->services['monolog.logger.security'] = $instance = new \Symfony\Bridge\Monolog\Logger('security');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'monolog.logger.templating' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Monolog\Logger A Symfony\Bridge\Monolog\Logger instance.
     */
    protected function getMonolog_Logger_TemplatingService()
    {
        $this->services['monolog.logger.templating'] = $instance = new \Symfony\Bridge\Monolog\Logger('templating');

        $instance->pushHandler($this->get('monolog.handler.main'));

        return $instance;
    }

    /**
     * Gets the 'property_accessor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\PropertyAccess\PropertyAccessor A Symfony\Component\PropertyAccess\PropertyAccessor instance.
     */
    protected function getPropertyAccessorService()
    {
        return $this->services['property_accessor'] = new \Symfony\Component\PropertyAccess\PropertyAccessor();
    }

    /**
     * Gets the 'request' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @throws RuntimeException always since this service is expected to be injected dynamically
     * @throws InactiveScopeException when the 'request' service is requested while the 'request' scope is not active
     */
    protected function getRequestService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('request', 'request');
        }

        throw new RuntimeException('You have requested a synthetic service ("request"). The DIC does not know how to construct this service.');
    }

    /**
     * Gets the 'request_stack' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpFoundation\RequestStack A Symfony\Component\HttpFoundation\RequestStack instance.
     */
    protected function getRequestStackService()
    {
        return $this->services['request_stack'] = new \Symfony\Component\HttpFoundation\RequestStack();
    }

    /**
     * Gets the 'response_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\ResponseListener A Symfony\Component\HttpKernel\EventListener\ResponseListener instance.
     */
    protected function getResponseListenerService()
    {
        return $this->services['response_listener'] = new \Symfony\Component\HttpKernel\EventListener\ResponseListener('UTF-8');
    }

    /**
     * Gets the 'router' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Routing\Router A Symfony\Bundle\FrameworkBundle\Routing\Router instance.
     */
    protected function getRouterService()
    {
        return $this->services['router'] = new \Symfony\Bundle\FrameworkBundle\Routing\Router($this, '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/config/routing.yml', array('cache_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test', 'debug' => true, 'generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper', 'generator_cache_class' => 'appTestUrlGenerator', 'matcher_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher', 'matcher_base_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher', 'matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper', 'matcher_cache_class' => 'appTestUrlMatcher', 'strict_requirements' => true), $this->get('router.request_context', ContainerInterface::NULL_ON_INVALID_REFERENCE), $this->get('monolog.logger.router', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'router_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\RouterListener A Symfony\Component\HttpKernel\EventListener\RouterListener instance.
     */
    protected function getRouterListenerService()
    {
        return $this->services['router_listener'] = new \Symfony\Component\HttpKernel\EventListener\RouterListener($this->get('router'), $this->get('router.request_context', ContainerInterface::NULL_ON_INVALID_REFERENCE), $this->get('monolog.logger.request', ContainerInterface::NULL_ON_INVALID_REFERENCE), $this->get('request_stack'));
    }

    /**
     * Gets the 'routing.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader A Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader instance.
     */
    protected function getRouting_LoaderService()
    {
        $a = $this->get('file_locator');

        $b = new \Symfony\Component\Config\Loader\LoaderResolver();
        $b->addLoader(new \Symfony\Component\Routing\Loader\XmlFileLoader($a));
        $b->addLoader(new \Symfony\Component\Routing\Loader\YamlFileLoader($a));
        $b->addLoader(new \Symfony\Component\Routing\Loader\PhpFileLoader($a));
        $b->addLoader($this->get('fos_rest.routing.loader.controller'));
        $b->addLoader($this->get('fos_rest.routing.loader.yaml_collection'));
        $b->addLoader($this->get('fos_rest.routing.loader.xml_collection'));

        return $this->services['routing.loader'] = new \Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader($this->get('controller_name_converter'), $this->get('monolog.logger.router', ContainerInterface::NULL_ON_INVALID_REFERENCE), $b);
    }

    /**
     * Gets the 'security.context' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Security\Core\SecurityContext A Symfony\Component\Security\Core\SecurityContext instance.
     */
    protected function getSecurity_ContextService()
    {
        return $this->services['security.context'] = new \Symfony\Component\Security\Core\SecurityContext($this->get('security.authentication.manager'), $this->get('security.access.decision_manager'), false);
    }

    /**
     * Gets the 'security.encoder_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Security\Core\Encoder\EncoderFactory A Symfony\Component\Security\Core\Encoder\EncoderFactory instance.
     */
    protected function getSecurity_EncoderFactoryService()
    {
        return $this->services['security.encoder_factory'] = new \Symfony\Component\Security\Core\Encoder\EncoderFactory(array('Sulu\\Bundle\\SecurityBundle\\Entity\\User' => array('class' => 'Symfony\\Component\\Security\\Core\\Encoder\\MessageDigestPasswordEncoder', 'arguments' => array(0 => 'sha512', 1 => false, 2 => 5000))));
    }

    /**
     * Gets the 'security.firewall' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Security\Http\Firewall A Symfony\Component\Security\Http\Firewall instance.
     */
    protected function getSecurity_FirewallService()
    {
        return $this->services['security.firewall'] = new \Symfony\Component\Security\Http\Firewall(new \Symfony\Bundle\SecurityBundle\Security\FirewallMap($this, array('security.firewall.map.context.sulu' => new \Symfony\Component\HttpFoundation\RequestMatcher('^/admin'))), $this->get('debug.event_dispatcher'));
    }

    /**
     * Gets the 'security.firewall.map.context.sulu' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\SecurityBundle\Security\FirewallContext A Symfony\Bundle\SecurityBundle\Security\FirewallContext instance.
     */
    protected function getSecurity_Firewall_Map_Context_SuluService()
    {
        $a = $this->get('monolog.logger.security', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $b = $this->get('security.context');
        $c = $this->get('router', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        $d = new \Symfony\Component\Security\Http\AccessMap();

        return $this->services['security.firewall.map.context.sulu'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(array(0 => new \Symfony\Component\Security\Http\Firewall\ChannelListener($d, new \Symfony\Component\Security\Http\EntryPoint\RetryAuthenticationEntryPoint(80, 443), $a), 1 => new \Symfony\Component\Security\Http\Firewall\ContextListener($b, array(0 => new \Symfony\Bridge\Doctrine\Security\User\EntityUserProvider($this->get('doctrine'), 'SuluSecurityBundle:User', 'username', NULL)), 'sulu', $a, $this->get('debug.event_dispatcher', ContainerInterface::NULL_ON_INVALID_REFERENCE)), 2 => new \Symfony\Component\Security\Http\Firewall\AnonymousAuthenticationListener($b, '53eb5f53882b4', $a), 3 => new \Symfony\Component\Security\Http\Firewall\AccessListener($b, $this->get('security.access.decision_manager'), $d, $this->get('security.authentication.manager'))), new \Symfony\Component\Security\Http\Firewall\ExceptionListener($b, $this->get('security.authentication.trust_resolver'), new \Symfony\Component\Security\Http\HttpUtils($c, $c), 'sulu', NULL, NULL, NULL, $a));
    }

    /**
     * Gets the 'security.rememberme.response_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Security\Http\RememberMe\ResponseListener A Symfony\Component\Security\Http\RememberMe\ResponseListener instance.
     */
    protected function getSecurity_Rememberme_ResponseListenerService()
    {
        return $this->services['security.rememberme.response_listener'] = new \Symfony\Component\Security\Http\RememberMe\ResponseListener();
    }

    /**
     * Gets the 'security.secure_random' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Security\Core\Util\SecureRandom A Symfony\Component\Security\Core\Util\SecureRandom instance.
     */
    protected function getSecurity_SecureRandomService()
    {
        return $this->services['security.secure_random'] = new \Symfony\Component\Security\Core\Util\SecureRandom('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/secure_random.seed', $this->get('monolog.logger.security', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'security.validator.user_password' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator A Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator instance.
     */
    protected function getSecurity_Validator_UserPasswordService()
    {
        return $this->services['security.validator.user_password'] = new \Symfony\Component\Security\Core\Validator\Constraints\UserPasswordValidator($this->get('security.context'), $this->get('security.encoder_factory'));
    }

    /**
     * Gets the 'service_container' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @throws RuntimeException always since this service is expected to be injected dynamically
     */
    protected function getServiceContainerService()
    {
        throw new RuntimeException('You have requested a synthetic service ("service_container"). The DIC does not know how to construct this service.');
    }

    /**
     * Gets the 'session' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpFoundation\Session\Session A Symfony\Component\HttpFoundation\Session\Session instance.
     */
    protected function getSessionService()
    {
        return $this->services['session'] = new \Symfony\Component\HttpFoundation\Session\Session($this->get('session.storage.filesystem'), new \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag(), new \Symfony\Component\HttpFoundation\Session\Flash\FlashBag());
    }

    /**
     * Gets the 'session.handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler A Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler instance.
     */
    protected function getSession_HandlerService()
    {
        return $this->services['session.handler'] = new \Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/sessions');
    }

    /**
     * Gets the 'session.storage.filesystem' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage A Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage instance.
     */
    protected function getSession_Storage_FilesystemService()
    {
        return $this->services['session.storage.filesystem'] = new \Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/sessions', 'MOCKSESSID', $this->get('session.storage.metadata_bag'));
    }

    /**
     * Gets the 'session.storage.native' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage A Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage instance.
     */
    protected function getSession_Storage_NativeService()
    {
        return $this->services['session.storage.native'] = new \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage(array('gc_probability' => 1), $this->get('session.handler'), $this->get('session.storage.metadata_bag'));
    }

    /**
     * Gets the 'session.storage.php_bridge' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage A Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage instance.
     */
    protected function getSession_Storage_PhpBridgeService()
    {
        return $this->services['session.storage.php_bridge'] = new \Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage($this->get('session.handler'), $this->get('session.storage.metadata_bag'));
    }

    /**
     * Gets the 'session_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\EventListener\SessionListener A Symfony\Bundle\FrameworkBundle\EventListener\SessionListener instance.
     */
    protected function getSessionListenerService()
    {
        return $this->services['session_listener'] = new \Symfony\Bundle\FrameworkBundle\EventListener\SessionListener($this);
    }

    /**
     * Gets the 'stof_doctrine_extensions.uploadable.manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager A Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager instance.
     */
    protected function getStofDoctrineExtensions_Uploadable_ManagerService()
    {
        $a = new \Gedmo\Uploadable\UploadableListener(new \Stof\DoctrineExtensionsBundle\Uploadable\MimeTypeGuesserAdapter());
        $a->setAnnotationReader($this->get('annotation_reader'));
        $a->setDefaultFileInfoClass('Stof\\DoctrineExtensionsBundle\\Uploadable\\UploadedFileInfo');

        return $this->services['stof_doctrine_extensions.uploadable.manager'] = new \Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager($a, 'Stof\\DoctrineExtensionsBundle\\Uploadable\\UploadedFileInfo');
    }

    /**
     * Gets the 'streamed_response_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\StreamedResponseListener A Symfony\Component\HttpKernel\EventListener\StreamedResponseListener instance.
     */
    protected function getStreamedResponseListenerService()
    {
        return $this->services['streamed_response_listener'] = new \Symfony\Component\HttpKernel\EventListener\StreamedResponseListener();
    }

    /**
     * Gets the 'sulu.content.localization_finder' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Mapper\LocalizationFinder\ParentChildAnyFinder A Sulu\Component\Content\Mapper\LocalizationFinder\ParentChildAnyFinder instance.
     */
    protected function getSulu_Content_LocalizationFinderService()
    {
        return $this->services['sulu.content.localization_finder'] = new \Sulu\Component\Content\Mapper\LocalizationFinder\ParentChildAnyFinder($this->get('sulu_core.webspace.webspace_manager'), 'i18n', '');
    }

    /**
     * Gets the 'sulu.content.mapper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Mapper\ContentMapper A Sulu\Component\Content\Mapper\ContentMapper instance.
     */
    protected function getSulu_Content_MapperService()
    {
        return $this->services['sulu.content.mapper'] = new \Sulu\Component\Content\Mapper\ContentMapper($this->get('sulu.content.type_manager'), $this->get('sulu.content.structure_manager'), $this->get('sulu.phpcr.session'), $this->get('debug.event_dispatcher'), $this->get('sulu.content.localization_finder'), $this->get('sulu.content.path_cleaner'), $this->get('sulu_core.webspace.webspace_manager'), 'en', 'default', 'i18n', '', array(0 => 'main', 1 => 'footer'), $this->get('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'sulu.content.rlp.strategy.tree' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy A Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy instance.
     */
    protected function getSulu_Content_Rlp_Strategy_TreeService()
    {
        return $this->services['sulu.content.rlp.strategy.tree'] = new \Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy(new \Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper($this->get('sulu.phpcr.session')), $this->get('sulu.content.path_cleaner'));
    }

    /**
     * Gets the 'sulu.content.structure_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\StructureManager A Sulu\Component\Content\StructureManager instance.
     */
    protected function getSulu_Content_StructureManagerService()
    {
        $this->services['sulu.content.structure_manager'] = $instance = new \Sulu\Component\Content\StructureManager(new \Sulu\Component\Content\Template\TemplateReader(), new \Sulu\Component\Content\Template\Dumper\PHPTemplateDumper('../Resources/Skeleton', true), $this->get('logger'), array('template_dir' => array(), 'cache_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test', 'debug' => true));

        $instance->setContainer($this);

        return $instance;
    }

    /**
     * Gets the 'sulu.content.type.block' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Block\BlockContentType A Sulu\Component\Content\Block\BlockContentType instance.
     */
    protected function getSulu_Content_Type_BlockService()
    {
        return $this->services['sulu.content.type.block'] = new \Sulu\Component\Content\Block\BlockContentType($this->get('sulu.content.type_manager'), 'SuluContentBundle:Template:content-types/block.html.twig', 'i18n');
    }

    /**
     * Gets the 'sulu.content.type.resource_locator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Types\ResourceLocator A Sulu\Component\Content\Types\ResourceLocator instance.
     */
    protected function getSulu_Content_Type_ResourceLocatorService()
    {
        return $this->services['sulu.content.type.resource_locator'] = new \Sulu\Component\Content\Types\ResourceLocator($this->get('sulu.content.rlp.strategy.tree'), 'SuluContentBundle:Template:content-types/resource_locator.html.twig');
    }

    /**
     * Gets the 'sulu.content.type.text_area' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Types\TextLine A Sulu\Component\Content\Types\TextLine instance.
     */
    protected function getSulu_Content_Type_TextAreaService()
    {
        return $this->services['sulu.content.type.text_area'] = new \Sulu\Component\Content\Types\TextLine('SuluContentBundle:Template:content-types/text_area.html.twig');
    }

    /**
     * Gets the 'sulu.content.type.text_editor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Types\TextEditor A Sulu\Component\Content\Types\TextEditor instance.
     */
    protected function getSulu_Content_Type_TextEditorService()
    {
        return $this->services['sulu.content.type.text_editor'] = new \Sulu\Component\Content\Types\TextEditor('SuluContentBundle:Template:content-types/text_editor.html.twig');
    }

    /**
     * Gets the 'sulu.content.type.text_line' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\Types\TextLine A Sulu\Component\Content\Types\TextLine instance.
     */
    protected function getSulu_Content_Type_TextLineService()
    {
        return $this->services['sulu.content.type.text_line'] = new \Sulu\Component\Content\Types\TextLine('SuluContentBundle:Template:content-types/text_line.html.twig');
    }

    /**
     * Gets the 'sulu.content.type_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Content\ContentTypeManager A Sulu\Component\Content\ContentTypeManager instance.
     */
    protected function getSulu_Content_TypeManagerService()
    {
        $this->services['sulu.content.type_manager'] = $instance = new \Sulu\Component\Content\ContentTypeManager($this);

        $instance->mapAliasToServiceId('text_line', 'sulu.content.type.text_line');
        $instance->mapAliasToServiceId('text_area', 'sulu.content.type.text_area');
        $instance->mapAliasToServiceId('text_editor', 'sulu.content.type.text_editor');
        $instance->mapAliasToServiceId('resource_locator', 'sulu.content.type.resource_locator');
        $instance->mapAliasToServiceId('block', 'sulu.content.type.block');
        $instance->mapAliasToServiceId('tag_list', 'sulu_tag.content.type.tag_list');
        $instance->mapAliasToServiceId('media_selection', 'sulu_media.type.media_selection');
        $instance->mapAliasToServiceId('category_list', 'sulu_category.content.type.category_list');

        return $instance;
    }

    /**
     * Gets the 'sulu.phpcr.session' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\PHPCR\SessionManager\SessionManager A Sulu\Component\PHPCR\SessionManager\SessionManager instance.
     */
    protected function getSulu_Phpcr_SessionService()
    {
        return $this->services['sulu.phpcr.session'] = new \Sulu\Component\PHPCR\SessionManager\SessionManager($this->get('doctrine_phpcr.default_session'), array('base' => 'cmf', 'content' => 'contents', 'route' => 'routes'));
    }

    /**
     * Gets the 'sulu_admin.admin_pool' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\AdminBundle\Admin\AdminPool A Sulu\Bundle\AdminBundle\Admin\AdminPool instance.
     */
    protected function getSuluAdmin_AdminPoolService()
    {
        $this->services['sulu_admin.admin_pool'] = $instance = new \Sulu\Bundle\AdminBundle\Admin\AdminPool();

        $instance->addAdmin(new \Sulu\Bundle\SecurityBundle\Admin\SuluSecurityAdmin('SULU 2.0'));
        $instance->addAdmin(new \Sulu\Bundle\ContactBundle\Admin\SuluContactAdmin('SULU 2.0'));
        $instance->addAdmin(new \Sulu\Bundle\TagBundle\Admin\SuluTagAdmin('SULU 2.0'));
        $instance->addAdmin(new \Sulu\Bundle\MediaBundle\Admin\SuluMediaAdmin('SULU 2.0'));
        $instance->addAdmin(new \Sulu\Bundle\CategoryBundle\Admin\SuluCategoryAdmin('SULU 2.0'));

        return $instance;
    }

    /**
     * Gets the 'sulu_admin.js_config_pool' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\AdminBundle\Admin\JsConfigPool A Sulu\Bundle\AdminBundle\Admin\JsConfigPool instance.
     */
    protected function getSuluAdmin_JsConfigPoolService()
    {
        $this->services['sulu_admin.js_config_pool'] = $instance = new \Sulu\Bundle\AdminBundle\Admin\JsConfigPool();

        $instance->addConfigParams(new \Sulu\Bundle\AdminBundle\Admin\JsConfig('sulu-contact', array('accountTypes' => array())));

        return $instance;
    }

    /**
     * Gets the 'sulu_admin.widgets_handler' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\AdminBundle\Widgets\WidgetsHandler A Sulu\Bundle\AdminBundle\Widgets\WidgetsHandler instance.
     */
    protected function getSuluAdmin_WidgetsHandlerService()
    {
        $this->services['sulu_admin.widgets_handler'] = $instance = new \Sulu\Bundle\AdminBundle\Widgets\WidgetsHandler($this->get('templating'));

        $instance->addWidget($this->get('sulu_contact.contact.widgets.main_account'), 'sulu-contact-main-account');
        $instance->addWidget($this->get('sulu_contact.contact.widgets.account_info'), 'sulu-contact-account-info');
        $instance->addWidget($this->get('sulu_contact.contact.widgets.contact_info'), 'sulu-contact-contact-info');
        $instance->addWidget($this->get('sulu_contact.contact.widgets.main_contact'), 'sulu-contact-main-contact');
        $instance->addWidget($this->get('sulu_contact.contact.widgets.table'), 'sulu-contact-table');
        $instance->addWidget($this->get('sulu_contact.contact.widgets.toolbar'), 'sulu-contact-toolbar');

        return $instance;
    }

    /**
     * Gets the 'sulu_category.admin' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\CategoryBundle\Admin\SuluCategoryAdmin A Sulu\Bundle\CategoryBundle\Admin\SuluCategoryAdmin instance.
     */
    protected function getSuluCategory_AdminService()
    {
        return $this->services['sulu_category.admin'] = new \Sulu\Bundle\CategoryBundle\Admin\SuluCategoryAdmin('SULU 2.0');
    }

    /**
     * Gets the 'sulu_category.admin.content_navigation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\CategoryBundle\Admin\SuluCategoryContentNavigation A Sulu\Bundle\CategoryBundle\Admin\SuluCategoryContentNavigation instance.
     */
    protected function getSuluCategory_Admin_ContentNavigationService()
    {
        return $this->services['sulu_category.admin.content_navigation'] = new \Sulu\Bundle\CategoryBundle\Admin\SuluCategoryContentNavigation();
    }

    /**
     * Gets the 'sulu_category.category_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\CategoryBundle\Category\CategoryManager A Sulu\Bundle\CategoryBundle\Category\CategoryManager instance.
     */
    protected function getSuluCategory_CategoryManagerService()
    {
        return $this->services['sulu_category.category_manager'] = new \Sulu\Bundle\CategoryBundle\Category\CategoryManager($this->get('sulu_category.category_repository'), $this->get('sulu_security.user_repository'), $this->get('doctrine.orm.default_entity_manager'), $this->get('debug.event_dispatcher'));
    }

    /**
     * Gets the 'sulu_category.category_repository' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\CategoryBundle\Entity\CategoryRepository A Sulu\Bundle\CategoryBundle\Entity\CategoryRepository instance.
     */
    protected function getSuluCategory_CategoryRepositoryService()
    {
        return $this->services['sulu_category.category_repository'] = $this->get('doctrine.orm.default_entity_manager')->getRepository('SuluCategoryBundle:Category');
    }

    /**
     * Gets the 'sulu_category.content.type.category_list' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\CategoryBundle\Content\Types\CategoryList A Sulu\Bundle\CategoryBundle\Content\Types\CategoryList instance.
     */
    protected function getSuluCategory_Content_Type_CategoryListService()
    {
        return $this->services['sulu_category.content.type.category_list'] = new \Sulu\Bundle\CategoryBundle\Content\Types\CategoryList($this->get('sulu_category.category_manager'), 'SuluCategoryBundle:Template:content-types/category_list.html.twig');
    }

    /**
     * Gets the 'sulu_contact.account_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\EventListener\AccountListener A Sulu\Bundle\ContactBundle\EventListener\AccountListener instance.
     */
    protected function getSuluContact_AccountListenerService()
    {
        return $this->services['sulu_contact.account_listener'] = new \Sulu\Bundle\ContactBundle\EventListener\AccountListener();
    }

    /**
     * Gets the 'sulu_contact.account_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Contact\AccountManager A Sulu\Bundle\ContactBundle\Contact\AccountManager instance.
     */
    protected function getSuluContact_AccountManagerService()
    {
        return $this->services['sulu_contact.account_manager'] = new \Sulu\Bundle\ContactBundle\Contact\AccountManager($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_contact.admin' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Admin\SuluContactAdmin A Sulu\Bundle\ContactBundle\Admin\SuluContactAdmin instance.
     */
    protected function getSuluContact_AdminService()
    {
        return $this->services['sulu_contact.admin'] = new \Sulu\Bundle\ContactBundle\Admin\SuluContactAdmin('SULU 2.0');
    }

    /**
     * Gets the 'sulu_contact.admin.content_navigation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Admin\SuluContactContentNavigation A Sulu\Bundle\ContactBundle\Admin\SuluContactContentNavigation instance.
     */
    protected function getSuluContact_Admin_ContentNavigationService()
    {
        $this->services['sulu_contact.admin.content_navigation'] = $instance = new \Sulu\Bundle\ContactBundle\Admin\SuluContactContentNavigation();

        $instance->addNavigation(new \Sulu\Bundle\SecurityBundle\Admin\SuluSecurityContentNavigation());

        return $instance;
    }

    /**
     * Gets the 'sulu_contact.contact.widgets.account_info' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Widgets\AccountInfo A Sulu\Bundle\ContactBundle\Widgets\AccountInfo instance.
     */
    protected function getSuluContact_Contact_Widgets_AccountInfoService()
    {
        return $this->services['sulu_contact.contact.widgets.account_info'] = new \Sulu\Bundle\ContactBundle\Widgets\AccountInfo($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_contact.contact.widgets.contact_info' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Widgets\ContactInfo A Sulu\Bundle\ContactBundle\Widgets\ContactInfo instance.
     */
    protected function getSuluContact_Contact_Widgets_ContactInfoService()
    {
        return $this->services['sulu_contact.contact.widgets.contact_info'] = new \Sulu\Bundle\ContactBundle\Widgets\ContactInfo($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_contact.contact.widgets.main_account' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Widgets\MainAccount A Sulu\Bundle\ContactBundle\Widgets\MainAccount instance.
     */
    protected function getSuluContact_Contact_Widgets_MainAccountService()
    {
        return $this->services['sulu_contact.contact.widgets.main_account'] = new \Sulu\Bundle\ContactBundle\Widgets\MainAccount($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_contact.contact.widgets.main_contact' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Widgets\MainContact A Sulu\Bundle\ContactBundle\Widgets\MainContact instance.
     */
    protected function getSuluContact_Contact_Widgets_MainContactService()
    {
        return $this->services['sulu_contact.contact.widgets.main_contact'] = new \Sulu\Bundle\ContactBundle\Widgets\MainContact($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_contact.contact.widgets.table' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Widgets\Table A Sulu\Bundle\ContactBundle\Widgets\Table instance.
     */
    protected function getSuluContact_Contact_Widgets_TableService()
    {
        return $this->services['sulu_contact.contact.widgets.table'] = new \Sulu\Bundle\ContactBundle\Widgets\Table();
    }

    /**
     * Gets the 'sulu_contact.contact.widgets.toolbar' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Widgets\Toolbar A Sulu\Bundle\ContactBundle\Widgets\Toolbar instance.
     */
    protected function getSuluContact_Contact_Widgets_ToolbarService()
    {
        return $this->services['sulu_contact.contact.widgets.toolbar'] = new \Sulu\Bundle\ContactBundle\Widgets\Toolbar($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_contact.contact_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Contact\ContactManager A Sulu\Bundle\ContactBundle\Contact\ContactManager instance.
     */
    protected function getSuluContact_ContactManagerService()
    {
        return $this->services['sulu_contact.contact_manager'] = new \Sulu\Bundle\ContactBundle\Contact\ContactManager($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_contact.import' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Import\Import A Sulu\Bundle\ContactBundle\Import\Import instance.
     */
    protected function getSuluContact_ImportService()
    {
        return $this->services['sulu_contact.import'] = new \Sulu\Bundle\ContactBundle\Import\Import($this->get('doctrine.orm.default_entity_manager'), $this->get('sulu_contact.account_manager'), $this->get('sulu_contact.contact_manager'), array('phoneType' => '1', 'phoneTypeMobile' => '3', 'phoneTypeIsdn' => '4', 'emailType' => '1', 'addressType' => '1', 'urlType' => '1', 'faxType' => '1', 'country' => '15'), array(), array());
    }

    /**
     * Gets the 'sulu_contact.js_config' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\AdminBundle\Admin\JsConfig A Sulu\Bundle\AdminBundle\Admin\JsConfig instance.
     */
    protected function getSuluContact_JsConfigService()
    {
        return $this->services['sulu_contact.js_config'] = new \Sulu\Bundle\AdminBundle\Admin\JsConfig('sulu-contact', array('accountTypes' => array()));
    }

    /**
     * Gets the 'sulu_contact.twig' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\ContactBundle\Twig\ContactTwigExtension A Sulu\Bundle\ContactBundle\Twig\ContactTwigExtension instance.
     */
    protected function getSuluContact_TwigService()
    {
        return $this->services['sulu_contact.twig'] = new \Sulu\Bundle\ContactBundle\Twig\ContactTwigExtension($this->get('sulu_contact.twig.cache'), $this->get('sulu_contact.user_repository'));
    }

    /**
     * Gets the 'sulu_contact.twig.cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Doctrine\Common\Cache\ArrayCache A Doctrine\Common\Cache\ArrayCache instance.
     */
    protected function getSuluContact_Twig_CacheService()
    {
        return $this->services['sulu_contact.twig.cache'] = new \Doctrine\Common\Cache\ArrayCache();
    }

    /**
     * Gets the 'sulu_contact.user_repository' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\Entity\UserRepository A Sulu\Bundle\SecurityBundle\Entity\UserRepository instance.
     */
    protected function getSuluContact_UserRepositoryService()
    {
        return $this->services['sulu_contact.user_repository'] = $this->get('doctrine')->getRepository('SuluSecurityBundle:User');
    }

    /**
     * Gets the 'sulu_core.doctrine_list_builder_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory A Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory instance.
     */
    protected function getSuluCore_DoctrineListBuilderFactoryService()
    {
        return $this->services['sulu_core.doctrine_list_builder_factory'] = new \Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'sulu_core.doctrine_rest_helper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Rest\DoctrineRestHelper A Sulu\Component\Rest\DoctrineRestHelper instance.
     * 
     * @throws InactiveScopeException when the 'sulu_core.doctrine_rest_helper' service is requested while the 'request' scope is not active
     */
    protected function getSuluCore_DoctrineRestHelperService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('sulu_core.doctrine_rest_helper', 'request');
        }

        return $this->services['sulu_core.doctrine_rest_helper'] = $this->scopedServices['request']['sulu_core.doctrine_rest_helper'] = new \Sulu\Component\Rest\DoctrineRestHelper($this->get('sulu_core.list_rest_helper'));
    }

    /**
     * Gets the 'sulu_core.list_rest_helper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Rest\ListBuilder\ListRestHelper A Sulu\Component\Rest\ListBuilder\ListRestHelper instance.
     * 
     * @throws InactiveScopeException when the 'sulu_core.list_rest_helper' service is requested while the 'request' scope is not active
     */
    protected function getSuluCore_ListRestHelperService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('sulu_core.list_rest_helper', 'request');
        }

        return $this->services['sulu_core.list_rest_helper'] = $this->scopedServices['request']['sulu_core.list_rest_helper'] = new \Sulu\Component\Rest\ListBuilder\ListRestHelper($this->get('request'));
    }

    /**
     * Gets the 'sulu_core.rest_helper' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Rest\RestHelper A Sulu\Component\Rest\RestHelper instance.
     * 
     * @throws InactiveScopeException when the 'sulu_core.rest_helper' service is requested while the 'request' scope is not active
     */
    protected function getSuluCore_RestHelperService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('sulu_core.rest_helper', 'request');
        }

        return $this->services['sulu_core.rest_helper'] = $this->scopedServices['request']['sulu_core.rest_helper'] = new \Sulu\Component\Rest\RestHelper($this->get('sulu_core.list_rest_helper'));
    }

    /**
     * Gets the 'sulu_core.webspace.loader.xml' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Webspace\Loader\XmlFileLoader A Sulu\Component\Webspace\Loader\XmlFileLoader instance.
     */
    protected function getSuluCore_Webspace_Loader_XmlService()
    {
        return $this->services['sulu_core.webspace.loader.xml'] = new \Sulu\Component\Webspace\Loader\XmlFileLoader($this->get('file_locator'));
    }

    /**
     * Gets the 'sulu_core.webspace.request_analyzer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Webspace\Analyzer\RequestAnalyzer A Sulu\Component\Webspace\Analyzer\RequestAnalyzer instance.
     */
    protected function getSuluCore_Webspace_RequestAnalyzerService()
    {
        return $this->services['sulu_core.webspace.request_analyzer'] = new \Sulu\Component\Webspace\Analyzer\RequestAnalyzer($this->get('sulu_core.webspace.webspace_manager'), 'test');
    }

    /**
     * Gets the 'sulu_core.webspace.request_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Webspace\EventListener\RequestListener A Sulu\Component\Webspace\EventListener\RequestListener instance.
     */
    protected function getSuluCore_Webspace_RequestListenerService()
    {
        return $this->services['sulu_core.webspace.request_listener'] = new \Sulu\Component\Webspace\EventListener\RequestListener($this->get('sulu_core.webspace.request_analyzer'));
    }

    /**
     * Gets the 'sulu_core.webspace.webspace_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Component\Webspace\Manager\WebspaceManager A Sulu\Component\Webspace\Manager\WebspaceManager instance.
     */
    protected function getSuluCore_Webspace_WebspaceManagerService()
    {
        return $this->services['sulu_core.webspace.webspace_manager'] = new \Sulu\Component\Webspace\Manager\WebspaceManager($this->get('sulu_core.webspace.loader.xml'), $this->get('logger'), array('config_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/Resources/webspaces', 'cache_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test', 'debug' => true, 'cache_class' => 'WebspaceCollectionCache', 'base_class' => 'WebspaceCollection'));
    }

    /**
     * Gets the 'sulu_media.admin' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Admin\SuluMediaAdmin A Sulu\Bundle\MediaBundle\Admin\SuluMediaAdmin instance.
     */
    protected function getSuluMedia_AdminService()
    {
        return $this->services['sulu_media.admin'] = new \Sulu\Bundle\MediaBundle\Admin\SuluMediaAdmin('SULU 2.0');
    }

    /**
     * Gets the 'sulu_media.admin.content_navigation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Admin\SuluMediaContentNavigation A Sulu\Bundle\MediaBundle\Admin\SuluMediaContentNavigation instance.
     */
    protected function getSuluMedia_Admin_ContentNavigationService()
    {
        return $this->services['sulu_media.admin.content_navigation'] = new \Sulu\Bundle\MediaBundle\Admin\SuluMediaContentNavigation();
    }

    /**
     * Gets the 'sulu_media.collection_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Collection\Manager\DefaultCollectionManager A Sulu\Bundle\MediaBundle\Collection\Manager\DefaultCollectionManager instance.
     */
    protected function getSuluMedia_CollectionManagerService()
    {
        return $this->services['sulu_media.collection_manager'] = new \Sulu\Bundle\MediaBundle\Collection\Manager\DefaultCollectionManager($this->get('sulu_media.collection_repository'), $this->get('sulu_media.media_repository'), $this->get('sulu_media.format_manager'), $this->get('sulu_security.user_repository'), $this->get('doctrine.orm.default_entity_manager'), 3, '150x100');
    }

    /**
     * Gets the 'sulu_media.collection_repository' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Entity\CollectionRepository A Sulu\Bundle\MediaBundle\Entity\CollectionRepository instance.
     */
    protected function getSuluMedia_CollectionRepositoryService()
    {
        return $this->services['sulu_media.collection_repository'] = $this->get('doctrine.orm.default_entity_manager')->getRepository('SuluMediaBundle:Collection');
    }

    /**
     * Gets the 'sulu_media.file_validator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\FileValidator\DefaultFileValidator A Sulu\Bundle\MediaBundle\Media\FileValidator\DefaultFileValidator instance.
     */
    protected function getSuluMedia_FileValidatorService()
    {
        return $this->services['sulu_media.file_validator'] = new \Sulu\Bundle\MediaBundle\Media\FileValidator\DefaultFileValidator();
    }

    /**
     * Gets the 'sulu_media.format_cache' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\FormatCache\LocalFormatCache A Sulu\Bundle\MediaBundle\Media\FormatCache\LocalFormatCache instance.
     */
    protected function getSuluMedia_FormatCacheService()
    {
        return $this->services['sulu_media.format_cache'] = new \Sulu\Bundle\MediaBundle\Media\FormatCache\LocalFormatCache($this->get('filesystem'), '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/../web/uploads/media', $this->get("router")->getRouteCollection()->get("sulu_media.website.image.proxy")->getPath(), '10', array(0 => array('name' => '170x170', 'commands' => array(0 => array('action' => 'scale', 'parameters' => array('x' => '170', 'y' => '170')))), 1 => array('name' => '50x50', 'commands' => array(0 => array('action' => 'scale', 'parameters' => array('x' => '50', 'y' => '50')))), 2 => array('name' => '150x100', 'commands' => array(0 => array('action' => 'scale', 'parameters' => array('x' => '150', 'y' => '100'))))));
    }

    /**
     * Gets the 'sulu_media.format_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\FormatManager\DefaultFormatManager A Sulu\Bundle\MediaBundle\Media\FormatManager\DefaultFormatManager instance.
     */
    protected function getSuluMedia_FormatManagerService()
    {
        return $this->services['sulu_media.format_manager'] = new \Sulu\Bundle\MediaBundle\Media\FormatManager\DefaultFormatManager($this->get('sulu_media.media_repository'), $this->get('sulu_media.storage'), $this->get('sulu_media.format_cache'), $this->get('sulu_media.image.converter'), '/usr/local/bin/gs', 'true', array(0 => 'jpeg', 1 => 'jpg', 2 => 'gif', 3 => 'png', 4 => 'bmp', 5 => 'svg', 6 => 'psd', 7 => 'pdf'));
    }

    /**
     * Gets the 'sulu_media.image.command.resize' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\ResizeCommand A Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\ResizeCommand instance.
     */
    protected function getSuluMedia_Image_Command_ResizeService()
    {
        return $this->services['sulu_media.image.command.resize'] = new \Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\ResizeCommand();
    }

    /**
     * Gets the 'sulu_media.image.command.scale' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\ScaleCommand A Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\ScaleCommand instance.
     */
    protected function getSuluMedia_Image_Command_ScaleService()
    {
        return $this->services['sulu_media.image.command.scale'] = new \Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\ScaleCommand();
    }

    /**
     * Gets the 'sulu_media.image.command_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager\DefaultCommandManager A Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager\DefaultCommandManager instance.
     */
    protected function getSuluMedia_Image_CommandManagerService()
    {
        $this->services['sulu_media.image.command_manager'] = $instance = new \Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager\DefaultCommandManager('image.converter.prefix.');

        $instance->setContainer($this);

        return $instance;
    }

    /**
     * Gets the 'sulu_media.image.converter' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\ImageConverter\ImagineImageConverter A Sulu\Bundle\MediaBundle\Media\ImageConverter\ImagineImageConverter instance.
     */
    protected function getSuluMedia_Image_ConverterService()
    {
        return $this->services['sulu_media.image.converter'] = new \Sulu\Bundle\MediaBundle\Media\ImageConverter\ImagineImageConverter(array(0 => array('name' => '170x170', 'commands' => array(0 => array('action' => 'scale', 'parameters' => array('x' => '170', 'y' => '170')))), 1 => array('name' => '50x50', 'commands' => array(0 => array('action' => 'scale', 'parameters' => array('x' => '50', 'y' => '50')))), 2 => array('name' => '150x100', 'commands' => array(0 => array('action' => 'scale', 'parameters' => array('x' => '150', 'y' => '100'))))), $this->get('sulu_media.image.command_manager'));
    }

    /**
     * Gets the 'sulu_media.media_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\Manager\DefaultMediaManager A Sulu\Bundle\MediaBundle\Media\Manager\DefaultMediaManager instance.
     */
    protected function getSuluMedia_MediaManagerService()
    {
        return $this->services['sulu_media.media_manager'] = new \Sulu\Bundle\MediaBundle\Media\Manager\DefaultMediaManager($this->get('sulu_media.media_repository'), $this->get('sulu_media.collection_repository'), $this->get('sulu_security.user_repository'), $this->get('doctrine.orm.default_entity_manager'), $this->get('sulu_media.storage'), $this->get('sulu_media.file_validator'), $this->get('sulu_media.format_manager'), $this->get('sulu_tag.tag_manager'), $this->get("router")->getRouteCollection()->get("sulu_media.website.media.download")->getPath(), '16MB', array(0 => 'file/exe'), array(0 => array('id' => 1, 'type' => 'default', 'mimeTypes' => array(0 => '*')), 1 => array('id' => 2, 'type' => 'image', 'mimeTypes' => array(0 => 'image/jpg', 1 => 'image/jpeg', 2 => 'image/png', 3 => 'image/gif', 4 => 'image/svg+xml', 5 => 'image/vnd.adobe.photoshop')), 2 => array('id' => 3, 'type' => 'video', 'mimeTypes' => array(0 => 'video/mp4')), 3 => array('id' => 4, 'type' => 'audio', 'mimeTypes' => array(0 => 'audio/mpeg'))));
    }

    /**
     * Gets the 'sulu_media.media_repository' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Entity\MediaRepository A Sulu\Bundle\MediaBundle\Entity\MediaRepository instance.
     */
    protected function getSuluMedia_MediaRepositoryService()
    {
        return $this->services['sulu_media.media_repository'] = $this->get('doctrine.orm.default_entity_manager')->getRepository('SuluMediaBundle:Media');
    }

    /**
     * Gets the 'sulu_media.storage' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage A Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage instance.
     */
    protected function getSuluMedia_StorageService()
    {
        return $this->services['sulu_media.storage'] = new \Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/../uploads/media', '10');
    }

    /**
     * Gets the 'sulu_media.type.media_selection' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType A Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType instance.
     */
    protected function getSuluMedia_Type_MediaSelectionService()
    {
        return $this->services['sulu_media.type.media_selection'] = new \Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType($this->get('sulu_media.media_manager'), 'SuluMediaBundle:Template:content-types/media-selection.html.twig');
    }

    /**
     * Gets the 'sulu_security.admin' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\Admin\SuluSecurityAdmin A Sulu\Bundle\SecurityBundle\Admin\SuluSecurityAdmin instance.
     */
    protected function getSuluSecurity_AdminService()
    {
        return $this->services['sulu_security.admin'] = new \Sulu\Bundle\SecurityBundle\Admin\SuluSecurityAdmin('SULU 2.0');
    }

    /**
     * Gets the 'sulu_security.admin.roles_navigation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\Admin\SuluSecurityRolesContentNavigation A Sulu\Bundle\SecurityBundle\Admin\SuluSecurityRolesContentNavigation instance.
     */
    protected function getSuluSecurity_Admin_RolesNavigationService()
    {
        return $this->services['sulu_security.admin.roles_navigation'] = new \Sulu\Bundle\SecurityBundle\Admin\SuluSecurityRolesContentNavigation();
    }

    /**
     * Gets the 'sulu_security.content_navigation' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\Admin\SuluSecurityContentNavigation A Sulu\Bundle\SecurityBundle\Admin\SuluSecurityContentNavigation instance.
     */
    protected function getSuluSecurity_ContentNavigationService()
    {
        return $this->services['sulu_security.content_navigation'] = new \Sulu\Bundle\SecurityBundle\Admin\SuluSecurityContentNavigation();
    }

    /**
     * Gets the 'sulu_security.mask_converter' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\Permission\MaskConverter A Sulu\Bundle\SecurityBundle\Permission\MaskConverter instance.
     */
    protected function getSuluSecurity_MaskConverterService()
    {
        return $this->services['sulu_security.mask_converter'] = new \Sulu\Bundle\SecurityBundle\Permission\MaskConverter(array('view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8, 'archive' => 4, 'live' => 2, 'security' => 1));
    }

    /**
     * Gets the 'sulu_security.salt_generator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\Permission\SaltGenerator A Sulu\Bundle\SecurityBundle\Permission\SaltGenerator instance.
     */
    protected function getSuluSecurity_SaltGeneratorService()
    {
        return $this->services['sulu_security.salt_generator'] = new \Sulu\Bundle\SecurityBundle\Permission\SaltGenerator();
    }

    /**
     * Gets the 'sulu_security.user_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\UserManager\UserManager A Sulu\Bundle\SecurityBundle\UserManager\UserManager instance.
     */
    protected function getSuluSecurity_UserManagerService()
    {
        return $this->services['sulu_security.user_manager'] = new \Sulu\Bundle\SecurityBundle\UserManager\UserManager($this->get('doctrine'), $this->get('sulu_security.user_manager.current_user_data'));
    }

    /**
     * Gets the 'sulu_security.user_manager.current_user_data' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\UserManager\CurrentUserData A Sulu\Bundle\SecurityBundle\UserManager\CurrentUserData instance.
     */
    protected function getSuluSecurity_UserManager_CurrentUserDataService()
    {
        return $this->services['sulu_security.user_manager.current_user_data'] = new \Sulu\Bundle\SecurityBundle\UserManager\CurrentUserData($this->get('security.context'), $this->get('router'), $this->get('doctrine'));
    }

    /**
     * Gets the 'sulu_security.user_repository' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\TestBundle\Entity\TestUserRepository A Sulu\Bundle\TestBundle\Entity\TestUserRepository instance.
     */
    protected function getSuluSecurity_UserRepositoryService()
    {
        return $this->services['sulu_security.user_repository'] = $this->get('doctrine.orm.default_entity_manager')->getRepository('SuluTestBundle:TestUser');
    }

    /**
     * Gets the 'sulu_security.user_repository_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\SecurityBundle\Factory\UserRepositoryFactory A Sulu\Bundle\SecurityBundle\Factory\UserRepositoryFactory instance.
     */
    protected function getSuluSecurity_UserRepositoryFactoryService()
    {
        return $this->services['sulu_security.user_repository_factory'] = new \Sulu\Bundle\SecurityBundle\Factory\UserRepositoryFactory($this->get('doctrine.orm.default_entity_manager'), 'Sulu', $this->get('sulu_core.webspace.request_analyzer', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'sulu_tag.admin' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\TagBundle\Admin\SuluTagAdmin A Sulu\Bundle\TagBundle\Admin\SuluTagAdmin instance.
     */
    protected function getSuluTag_AdminService()
    {
        return $this->services['sulu_tag.admin'] = new \Sulu\Bundle\TagBundle\Admin\SuluTagAdmin('SULU 2.0');
    }

    /**
     * Gets the 'sulu_tag.content.type.tag_list' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\TagBundle\Content\Types\TagList A Sulu\Bundle\TagBundle\Content\Types\TagList instance.
     */
    protected function getSuluTag_Content_Type_TagListService()
    {
        return $this->services['sulu_tag.content.type.tag_list'] = new \Sulu\Bundle\TagBundle\Content\Types\TagList($this->get('sulu_tag.tag_manager'), 'SuluTagBundle:Template:content-types/tag_list.html.twig');
    }

    /**
     * Gets the 'sulu_tag.tag_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\TagBundle\Tag\TagManager A Sulu\Bundle\TagBundle\Tag\TagManager instance.
     */
    protected function getSuluTag_TagManagerService()
    {
        return $this->services['sulu_tag.tag_manager'] = new \Sulu\Bundle\TagBundle\Tag\TagManager($this->get('sulu_tag.tag_repository'), $this->get('sulu_security.user_repository'), $this->get('doctrine.orm.default_entity_manager'), $this->get('debug.event_dispatcher'));
    }

    /**
     * Gets the 'sulu_tag.tag_repository' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\TagBundle\Entity\TagRepository A Sulu\Bundle\TagBundle\Entity\TagRepository instance.
     */
    protected function getSuluTag_TagRepositoryService()
    {
        return $this->services['sulu_tag.tag_repository'] = $this->get('doctrine.orm.default_entity_manager')->getRepository('SuluTagBundle:Tag');
    }

    /**
     * Gets the 'templating' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine A Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine instance.
     */
    protected function getTemplatingService()
    {
        $this->services['templating'] = $instance = new \Symfony\Bundle\TwigBundle\Debug\TimedTwigEngine($this->get('twig'), $this->get('templating.name_parser'), $this->get('templating.locator'), $this->get('debug.stopwatch'));

        $instance->setDefaultEscapingStrategy(array(0 => $instance, 1 => 'guessDefaultEscapingStrategy'));

        return $instance;
    }

    /**
     * Gets the 'templating.asset.package_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Asset\PackageFactory A Symfony\Bundle\FrameworkBundle\Templating\Asset\PackageFactory instance.
     */
    protected function getTemplating_Asset_PackageFactoryService()
    {
        return $this->services['templating.asset.package_factory'] = new \Symfony\Bundle\FrameworkBundle\Templating\Asset\PackageFactory($this);
    }

    /**
     * Gets the 'templating.cache_warmer.template_paths' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Liip\ThemeBundle\CacheWarmer\TemplatePathsCacheWarmer A Liip\ThemeBundle\CacheWarmer\TemplatePathsCacheWarmer instance.
     */
    protected function getTemplating_CacheWarmer_TemplatePathsService()
    {
        return $this->services['templating.cache_warmer.template_paths'] = new \Liip\ThemeBundle\CacheWarmer\TemplatePathsCacheWarmer($this->get('templating.finder'), $this->get('templating.locator'), $this->get('liip_theme.active_theme'));
    }

    /**
     * Gets the 'templating.filename_parser' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser A Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser instance.
     */
    protected function getTemplating_FilenameParserService()
    {
        return $this->services['templating.filename_parser'] = new \Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser();
    }

    /**
     * Gets the 'templating.globals' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables A Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables instance.
     */
    protected function getTemplating_GlobalsService()
    {
        return $this->services['templating.globals'] = new \Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables($this);
    }

    /**
     * Gets the 'templating.helper.actions' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper instance.
     */
    protected function getTemplating_Helper_ActionsService()
    {
        return $this->services['templating.helper.actions'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\ActionsHelper($this->get('fragment.handler'));
    }

    /**
     * Gets the 'templating.helper.assets' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Templating\Helper\CoreAssetsHelper A Symfony\Component\Templating\Helper\CoreAssetsHelper instance.
     * 
     * @throws InactiveScopeException when the 'templating.helper.assets' service is requested while the 'request' scope is not active
     */
    protected function getTemplating_Helper_AssetsService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new InactiveScopeException('templating.helper.assets', 'request');
        }

        return $this->services['templating.helper.assets'] = $this->scopedServices['request']['templating.helper.assets'] = new \Symfony\Component\Templating\Helper\CoreAssetsHelper(new \Symfony\Bundle\FrameworkBundle\Templating\Asset\PathPackage($this->get('request'), NULL, '%s?%s'), array());
    }

    /**
     * Gets the 'templating.helper.code' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\CodeHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\CodeHelper instance.
     */
    protected function getTemplating_Helper_CodeService()
    {
        return $this->services['templating.helper.code'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\CodeHelper(NULL, '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app', 'UTF-8');
    }

    /**
     * Gets the 'templating.helper.form' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper instance.
     */
    protected function getTemplating_Helper_FormService()
    {
        return $this->services['templating.helper.form'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\FormHelper(new \Symfony\Component\Form\FormRenderer(new \Symfony\Component\Form\Extension\Templating\TemplatingRendererEngine($this->get('debug.templating.engine.php'), array(0 => 'FrameworkBundle:Form')), NULL));
    }

    /**
     * Gets the 'templating.helper.logout_url' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\SecurityBundle\Templating\Helper\LogoutUrlHelper A Symfony\Bundle\SecurityBundle\Templating\Helper\LogoutUrlHelper instance.
     */
    protected function getTemplating_Helper_LogoutUrlService()
    {
        return $this->services['templating.helper.logout_url'] = new \Symfony\Bundle\SecurityBundle\Templating\Helper\LogoutUrlHelper($this, $this->get('router'));
    }

    /**
     * Gets the 'templating.helper.request' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\RequestHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\RequestHelper instance.
     */
    protected function getTemplating_Helper_RequestService()
    {
        return $this->services['templating.helper.request'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\RequestHelper($this->get('request_stack'));
    }

    /**
     * Gets the 'templating.helper.router' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper instance.
     */
    protected function getTemplating_Helper_RouterService()
    {
        return $this->services['templating.helper.router'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\RouterHelper($this->get('router'));
    }

    /**
     * Gets the 'templating.helper.security' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\SecurityBundle\Templating\Helper\SecurityHelper A Symfony\Bundle\SecurityBundle\Templating\Helper\SecurityHelper instance.
     */
    protected function getTemplating_Helper_SecurityService()
    {
        return $this->services['templating.helper.security'] = new \Symfony\Bundle\SecurityBundle\Templating\Helper\SecurityHelper($this->get('security.context', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'templating.helper.session' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\SessionHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\SessionHelper instance.
     */
    protected function getTemplating_Helper_SessionService()
    {
        return $this->services['templating.helper.session'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\SessionHelper($this->get('request_stack'));
    }

    /**
     * Gets the 'templating.helper.slots' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Templating\Helper\SlotsHelper A Symfony\Component\Templating\Helper\SlotsHelper instance.
     */
    protected function getTemplating_Helper_SlotsService()
    {
        return $this->services['templating.helper.slots'] = new \Symfony\Component\Templating\Helper\SlotsHelper();
    }

    /**
     * Gets the 'templating.helper.stopwatch' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\StopwatchHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\StopwatchHelper instance.
     */
    protected function getTemplating_Helper_StopwatchService()
    {
        return $this->services['templating.helper.stopwatch'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\StopwatchHelper($this->get('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'templating.helper.translator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper A Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper instance.
     */
    protected function getTemplating_Helper_TranslatorService()
    {
        return $this->services['templating.helper.translator'] = new \Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper($this->get('translator'));
    }

    /**
     * Gets the 'templating.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader A Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader instance.
     */
    protected function getTemplating_LoaderService()
    {
        return $this->services['templating.loader'] = new \Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader($this->get('templating.locator'));
    }

    /**
     * Gets the 'templating.locator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Liip\ThemeBundle\Locator\TemplateLocator A Liip\ThemeBundle\Locator\TemplateLocator instance.
     */
    protected function getTemplating_LocatorService()
    {
        $a = $this->get('liip_theme.active_theme');

        return $this->services['templating.locator'] = new \Liip\ThemeBundle\Locator\TemplateLocator(new \Liip\ThemeBundle\Locator\FileLocator($this->get('kernel'), $a, '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/Resources', array(), array('app_resource' => array(), 'bundle_resource' => array(), 'bundle_resource_dir' => array())), '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test', $a);
    }

    /**
     * Gets the 'templating.name_parser' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser A Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser instance.
     */
    protected function getTemplating_NameParserService()
    {
        return $this->services['templating.name_parser'] = new \Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser($this->get('kernel'));
    }

    /**
     * Gets the 'test.client' service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Client A Symfony\Bundle\FrameworkBundle\Client instance.
     */
    protected function getTest_ClientService()
    {
        return new \Symfony\Bundle\FrameworkBundle\Client($this->get('kernel'), array(), new \Symfony\Component\BrowserKit\History(), new \Symfony\Component\BrowserKit\CookieJar());
    }

    /**
     * Gets the 'test.client.cookiejar' service.
     *
     * @return Symfony\Component\BrowserKit\CookieJar A Symfony\Component\BrowserKit\CookieJar instance.
     */
    protected function getTest_Client_CookiejarService()
    {
        return new \Symfony\Component\BrowserKit\CookieJar();
    }

    /**
     * Gets the 'test.client.history' service.
     *
     * @return Symfony\Component\BrowserKit\History A Symfony\Component\BrowserKit\History instance.
     */
    protected function getTest_Client_HistoryService()
    {
        return new \Symfony\Component\BrowserKit\History();
    }

    /**
     * Gets the 'test.session.listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\EventListener\TestSessionListener A Symfony\Bundle\FrameworkBundle\EventListener\TestSessionListener instance.
     */
    protected function getTest_Session_ListenerService()
    {
        return $this->services['test.session.listener'] = new \Symfony\Bundle\FrameworkBundle\EventListener\TestSessionListener($this);
    }

    /**
     * Gets the 'test_user_provider' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Sulu\Bundle\TestBundle\Testing\TestUserProvider A Sulu\Bundle\TestBundle\Testing\TestUserProvider instance.
     */
    protected function getTestUserProviderService()
    {
        return $this->services['test_user_provider'] = new \Sulu\Bundle\TestBundle\Testing\TestUserProvider($this->get('doctrine.orm.default_entity_manager'));
    }

    /**
     * Gets the 'translation.dumper.csv' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\CsvFileDumper A Symfony\Component\Translation\Dumper\CsvFileDumper instance.
     */
    protected function getTranslation_Dumper_CsvService()
    {
        return $this->services['translation.dumper.csv'] = new \Symfony\Component\Translation\Dumper\CsvFileDumper();
    }

    /**
     * Gets the 'translation.dumper.ini' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\IniFileDumper A Symfony\Component\Translation\Dumper\IniFileDumper instance.
     */
    protected function getTranslation_Dumper_IniService()
    {
        return $this->services['translation.dumper.ini'] = new \Symfony\Component\Translation\Dumper\IniFileDumper();
    }

    /**
     * Gets the 'translation.dumper.json' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\JsonFileDumper A Symfony\Component\Translation\Dumper\JsonFileDumper instance.
     */
    protected function getTranslation_Dumper_JsonService()
    {
        return $this->services['translation.dumper.json'] = new \Symfony\Component\Translation\Dumper\JsonFileDumper();
    }

    /**
     * Gets the 'translation.dumper.mo' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\MoFileDumper A Symfony\Component\Translation\Dumper\MoFileDumper instance.
     */
    protected function getTranslation_Dumper_MoService()
    {
        return $this->services['translation.dumper.mo'] = new \Symfony\Component\Translation\Dumper\MoFileDumper();
    }

    /**
     * Gets the 'translation.dumper.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\PhpFileDumper A Symfony\Component\Translation\Dumper\PhpFileDumper instance.
     */
    protected function getTranslation_Dumper_PhpService()
    {
        return $this->services['translation.dumper.php'] = new \Symfony\Component\Translation\Dumper\PhpFileDumper();
    }

    /**
     * Gets the 'translation.dumper.po' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\PoFileDumper A Symfony\Component\Translation\Dumper\PoFileDumper instance.
     */
    protected function getTranslation_Dumper_PoService()
    {
        return $this->services['translation.dumper.po'] = new \Symfony\Component\Translation\Dumper\PoFileDumper();
    }

    /**
     * Gets the 'translation.dumper.qt' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\QtFileDumper A Symfony\Component\Translation\Dumper\QtFileDumper instance.
     */
    protected function getTranslation_Dumper_QtService()
    {
        return $this->services['translation.dumper.qt'] = new \Symfony\Component\Translation\Dumper\QtFileDumper();
    }

    /**
     * Gets the 'translation.dumper.res' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\IcuResFileDumper A Symfony\Component\Translation\Dumper\IcuResFileDumper instance.
     */
    protected function getTranslation_Dumper_ResService()
    {
        return $this->services['translation.dumper.res'] = new \Symfony\Component\Translation\Dumper\IcuResFileDumper();
    }

    /**
     * Gets the 'translation.dumper.xliff' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\XliffFileDumper A Symfony\Component\Translation\Dumper\XliffFileDumper instance.
     */
    protected function getTranslation_Dumper_XliffService()
    {
        return $this->services['translation.dumper.xliff'] = new \Symfony\Component\Translation\Dumper\XliffFileDumper();
    }

    /**
     * Gets the 'translation.dumper.yml' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Dumper\YamlFileDumper A Symfony\Component\Translation\Dumper\YamlFileDumper instance.
     */
    protected function getTranslation_Dumper_YmlService()
    {
        return $this->services['translation.dumper.yml'] = new \Symfony\Component\Translation\Dumper\YamlFileDumper();
    }

    /**
     * Gets the 'translation.extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Extractor\ChainExtractor A Symfony\Component\Translation\Extractor\ChainExtractor instance.
     */
    protected function getTranslation_ExtractorService()
    {
        $this->services['translation.extractor'] = $instance = new \Symfony\Component\Translation\Extractor\ChainExtractor();

        $instance->addExtractor('php', $this->get('translation.extractor.php'));
        $instance->addExtractor('twig', $this->get('twig.translation.extractor'));

        return $instance;
    }

    /**
     * Gets the 'translation.extractor.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor A Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor instance.
     */
    protected function getTranslation_Extractor_PhpService()
    {
        return $this->services['translation.extractor.php'] = new \Symfony\Bundle\FrameworkBundle\Translation\PhpExtractor();
    }

    /**
     * Gets the 'translation.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader A Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader instance.
     */
    protected function getTranslation_LoaderService()
    {
        $a = $this->get('translation.loader.xliff');

        $this->services['translation.loader'] = $instance = new \Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader();

        $instance->addLoader('php', $this->get('translation.loader.php'));
        $instance->addLoader('yml', $this->get('translation.loader.yml'));
        $instance->addLoader('xlf', $a);
        $instance->addLoader('xliff', $a);
        $instance->addLoader('po', $this->get('translation.loader.po'));
        $instance->addLoader('mo', $this->get('translation.loader.mo'));
        $instance->addLoader('ts', $this->get('translation.loader.qt'));
        $instance->addLoader('csv', $this->get('translation.loader.csv'));
        $instance->addLoader('res', $this->get('translation.loader.res'));
        $instance->addLoader('dat', $this->get('translation.loader.dat'));
        $instance->addLoader('ini', $this->get('translation.loader.ini'));
        $instance->addLoader('json', $this->get('translation.loader.json'));

        return $instance;
    }

    /**
     * Gets the 'translation.loader.csv' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\CsvFileLoader A Symfony\Component\Translation\Loader\CsvFileLoader instance.
     */
    protected function getTranslation_Loader_CsvService()
    {
        return $this->services['translation.loader.csv'] = new \Symfony\Component\Translation\Loader\CsvFileLoader();
    }

    /**
     * Gets the 'translation.loader.dat' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\IcuDatFileLoader A Symfony\Component\Translation\Loader\IcuDatFileLoader instance.
     */
    protected function getTranslation_Loader_DatService()
    {
        return $this->services['translation.loader.dat'] = new \Symfony\Component\Translation\Loader\IcuDatFileLoader();
    }

    /**
     * Gets the 'translation.loader.ini' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\IniFileLoader A Symfony\Component\Translation\Loader\IniFileLoader instance.
     */
    protected function getTranslation_Loader_IniService()
    {
        return $this->services['translation.loader.ini'] = new \Symfony\Component\Translation\Loader\IniFileLoader();
    }

    /**
     * Gets the 'translation.loader.json' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\JsonFileLoader A Symfony\Component\Translation\Loader\JsonFileLoader instance.
     */
    protected function getTranslation_Loader_JsonService()
    {
        return $this->services['translation.loader.json'] = new \Symfony\Component\Translation\Loader\JsonFileLoader();
    }

    /**
     * Gets the 'translation.loader.mo' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\MoFileLoader A Symfony\Component\Translation\Loader\MoFileLoader instance.
     */
    protected function getTranslation_Loader_MoService()
    {
        return $this->services['translation.loader.mo'] = new \Symfony\Component\Translation\Loader\MoFileLoader();
    }

    /**
     * Gets the 'translation.loader.php' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\PhpFileLoader A Symfony\Component\Translation\Loader\PhpFileLoader instance.
     */
    protected function getTranslation_Loader_PhpService()
    {
        return $this->services['translation.loader.php'] = new \Symfony\Component\Translation\Loader\PhpFileLoader();
    }

    /**
     * Gets the 'translation.loader.po' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\PoFileLoader A Symfony\Component\Translation\Loader\PoFileLoader instance.
     */
    protected function getTranslation_Loader_PoService()
    {
        return $this->services['translation.loader.po'] = new \Symfony\Component\Translation\Loader\PoFileLoader();
    }

    /**
     * Gets the 'translation.loader.qt' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\QtFileLoader A Symfony\Component\Translation\Loader\QtFileLoader instance.
     */
    protected function getTranslation_Loader_QtService()
    {
        return $this->services['translation.loader.qt'] = new \Symfony\Component\Translation\Loader\QtFileLoader();
    }

    /**
     * Gets the 'translation.loader.res' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\IcuResFileLoader A Symfony\Component\Translation\Loader\IcuResFileLoader instance.
     */
    protected function getTranslation_Loader_ResService()
    {
        return $this->services['translation.loader.res'] = new \Symfony\Component\Translation\Loader\IcuResFileLoader();
    }

    /**
     * Gets the 'translation.loader.xliff' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\XliffFileLoader A Symfony\Component\Translation\Loader\XliffFileLoader instance.
     */
    protected function getTranslation_Loader_XliffService()
    {
        return $this->services['translation.loader.xliff'] = new \Symfony\Component\Translation\Loader\XliffFileLoader();
    }

    /**
     * Gets the 'translation.loader.yml' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Loader\YamlFileLoader A Symfony\Component\Translation\Loader\YamlFileLoader instance.
     */
    protected function getTranslation_Loader_YmlService()
    {
        return $this->services['translation.loader.yml'] = new \Symfony\Component\Translation\Loader\YamlFileLoader();
    }

    /**
     * Gets the 'translation.writer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\Writer\TranslationWriter A Symfony\Component\Translation\Writer\TranslationWriter instance.
     */
    protected function getTranslation_WriterService()
    {
        $this->services['translation.writer'] = $instance = new \Symfony\Component\Translation\Writer\TranslationWriter();

        $instance->addDumper('php', $this->get('translation.dumper.php'));
        $instance->addDumper('xlf', $this->get('translation.dumper.xliff'));
        $instance->addDumper('po', $this->get('translation.dumper.po'));
        $instance->addDumper('mo', $this->get('translation.dumper.mo'));
        $instance->addDumper('yml', $this->get('translation.dumper.yml'));
        $instance->addDumper('ts', $this->get('translation.dumper.qt'));
        $instance->addDumper('csv', $this->get('translation.dumper.csv'));
        $instance->addDumper('ini', $this->get('translation.dumper.ini'));
        $instance->addDumper('json', $this->get('translation.dumper.json'));
        $instance->addDumper('res', $this->get('translation.dumper.res'));

        return $instance;
    }

    /**
     * Gets the 'translator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\Translation\IdentityTranslator A Symfony\Component\Translation\IdentityTranslator instance.
     */
    protected function getTranslatorService()
    {
        return $this->services['translator'] = new \Symfony\Component\Translation\IdentityTranslator($this->get('translator.selector'));
    }

    /**
     * Gets the 'translator.default' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\FrameworkBundle\Translation\Translator A Symfony\Bundle\FrameworkBundle\Translation\Translator instance.
     */
    protected function getTranslator_DefaultService()
    {
        return $this->services['translator.default'] = new \Symfony\Bundle\FrameworkBundle\Translation\Translator($this, $this->get('translator.selector'), array('translation.loader.php' => array(0 => 'php'), 'translation.loader.yml' => array(0 => 'yml'), 'translation.loader.xliff' => array(0 => 'xlf', 1 => 'xliff'), 'translation.loader.po' => array(0 => 'po'), 'translation.loader.mo' => array(0 => 'mo'), 'translation.loader.qt' => array(0 => 'ts'), 'translation.loader.csv' => array(0 => 'csv'), 'translation.loader.res' => array(0 => 'res'), 'translation.loader.dat' => array(0 => 'dat'), 'translation.loader.ini' => array(0 => 'ini'), 'translation.loader.json' => array(0 => 'json')), array('cache_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/translations', 'debug' => true));
    }

    /**
     * Gets the 'twig' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Twig_Environment A Twig_Environment instance.
     */
    protected function getTwigService()
    {
        $this->services['twig'] = $instance = new \Twig_Environment($this->get('twig.loader'), array('exception_controller' => 'twig.controller.exception:showAction', 'autoescape_service' => NULL, 'autoescape_service_method' => NULL, 'cache' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/twig', 'charset' => 'UTF-8', 'debug' => true, 'paths' => array()));

        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($this->get('translator')));
        $instance->addExtension(new \Symfony\Bundle\TwigBundle\Extension\AssetsExtension($this, $this->get('router.request_context')));
        $instance->addExtension(new \Symfony\Bundle\TwigBundle\Extension\ActionsExtension($this));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\CodeExtension(NULL, '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app', 'UTF-8'));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\RoutingExtension($this->get('router')));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\YamlExtension());
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\StopwatchExtension($this->get('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\ExpressionExtension());
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\HttpKernelExtension($this->get('fragment.handler')));
        $instance->addExtension(new \Twig_Extension_Debug());
        $instance->addExtension(new \Doctrine\Bundle\DoctrineBundle\Twig\DoctrineExtension());
        $instance->addExtension(new \JMS\Serializer\Twig\SerializerExtension($this->get('jms_serializer')));
        $instance->addExtension(new \Symfony\Bundle\SecurityBundle\Twig\Extension\LogoutUrlExtension($this->get('templating.helper.logout_url')));
        $instance->addExtension(new \Symfony\Bridge\Twig\Extension\SecurityExtension($this->get('security.context', ContainerInterface::NULL_ON_INVALID_REFERENCE)));
        $instance->addExtension($this->get('sulu_contact.twig'));
        $instance->addGlobal('app', $this->get('templating.globals'));

        return $instance;
    }

    /**
     * Gets the 'twig.controller.exception' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\TwigBundle\Controller\ExceptionController A Symfony\Bundle\TwigBundle\Controller\ExceptionController instance.
     */
    protected function getTwig_Controller_ExceptionService()
    {
        return $this->services['twig.controller.exception'] = new \Symfony\Bundle\TwigBundle\Controller\ExceptionController($this->get('twig'), true);
    }

    /**
     * Gets the 'twig.exception_listener' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\EventListener\ExceptionListener A Symfony\Component\HttpKernel\EventListener\ExceptionListener instance.
     */
    protected function getTwig_ExceptionListenerService()
    {
        return $this->services['twig.exception_listener'] = new \Symfony\Component\HttpKernel\EventListener\ExceptionListener('twig.controller.exception:showAction', $this->get('monolog.logger.request', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    }

    /**
     * Gets the 'twig.loader' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bundle\TwigBundle\Loader\FilesystemLoader A Symfony\Bundle\TwigBundle\Loader\FilesystemLoader instance.
     */
    protected function getTwig_LoaderService()
    {
        $this->services['twig.loader'] = $instance = new \Symfony\Bundle\TwigBundle\Loader\FilesystemLoader($this->get('templating.locator'), $this->get('templating.name_parser'));

        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/views', 'Framework');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Resources/views', 'Twig');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/Resources/views', 'Doctrine');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/symfony/symfony/src/Symfony/Bundle/SecurityBundle/Resources/views', 'Security');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/admin-bundle/Sulu/Bundle/AdminBundle/Resources/views', 'SuluAdmin');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Resources/views', 'SuluSecurity');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/contact-bundle/Sulu/Bundle/ContactBundle/Resources/views', 'SuluContact');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/tag-bundle/Sulu/Bundle/TagBundle/Resources/views', 'SuluTag');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/media-bundle/Sulu/Bundle/MediaBundle/Resources/views', 'SuluMedia');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/category-bundle/Sulu/Bundle/CategoryBundle/Resources/views', 'SuluCategory');
        $instance->addPath('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/doctrine/phpcr-bundle/Doctrine/Bundle/PHPCRBundle/Resources/views', 'DoctrinePHPCR');

        return $instance;
    }

    /**
     * Gets the 'twig.translation.extractor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Bridge\Twig\Translation\TwigExtractor A Symfony\Bridge\Twig\Translation\TwigExtractor instance.
     */
    protected function getTwig_Translation_ExtractorService()
    {
        return $this->services['twig.translation.extractor'] = new \Symfony\Bridge\Twig\Translation\TwigExtractor($this->get('twig'));
    }

    /**
     * Gets the 'uri_signer' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * @return Symfony\Component\HttpKernel\UriSigner A Symfony\Component\HttpKernel\UriSigner instance.
     */
    protected function getUriSignerService()
    {
        return $this->services['uri_signer'] = new \Symfony\Component\HttpKernel\UriSigner('secret');
    }

    /**
     * Gets the 'controller_name_converter' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser A Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser instance.
     */
    protected function getControllerNameConverterService()
    {
        return $this->services['controller_name_converter'] = new \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser($this->get('kernel'));
    }

    /**
     * Gets the 'hateoas.configuration.relations_repository' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Hateoas\Configuration\RelationsRepository A Hateoas\Configuration\RelationsRepository instance.
     */
    protected function getHateoas_Configuration_RelationsRepositoryService()
    {
        $a = $this->get('jms_serializer.metadata.file_locator');
        $b = $this->get('annotation_reader');

        $c = new \Hateoas\Configuration\Metadata\Driver\YamlDriver($a);

        $d = new \Hateoas\Configuration\Metadata\Driver\XmlDriver($a);

        $e = new \Hateoas\Configuration\Metadata\Driver\AnnotationDriver($b);

        $f = new \Metadata\Driver\DriverChain(array(0 => $c, 1 => $d, 2 => $e));

        $g = new \Metadata\Cache\FileCache('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/hateoas');

        $h = new \Metadata\MetadataFactory($f, 'Metadata\\ClassHierarchyMetadata', true);
        $h->setCache($g);

        return $this->services['hateoas.configuration.relations_repository'] = new \Hateoas\Configuration\RelationsRepository($h, new \Hateoas\Configuration\Provider\RelationProvider($h, $this->get('hateoas.configuration.provider.resolver')));
    }

    /**
     * Gets the 'hateoas.embeds_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Hateoas\Factory\EmbeddedsFactory A Hateoas\Factory\EmbeddedsFactory instance.
     */
    protected function getHateoas_EmbedsFactoryService()
    {
        return $this->services['hateoas.embeds_factory'] = new \Hateoas\Factory\EmbeddedsFactory($this->get('hateoas.configuration.relations_repository'), $this->get('hateoas.expression.evaluator'), $this->get('hateoas.serializer.exclusion_manager'));
    }

    /**
     * Gets the 'hateoas.links_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Hateoas\Factory\LinksFactory A Hateoas\Factory\LinksFactory instance.
     */
    protected function getHateoas_LinksFactoryService()
    {
        return $this->services['hateoas.links_factory'] = new \Hateoas\Factory\LinksFactory($this->get('hateoas.configuration.relations_repository'), new \Hateoas\Factory\LinkFactory($this->get('hateoas.expression.evaluator'), $this->get('hateoas.generator.registry')), $this->get('hateoas.serializer.exclusion_manager'));
    }

    /**
     * Gets the 'jms_serializer.metadata.file_locator' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Metadata\Driver\FileLocator A Metadata\Driver\FileLocator instance.
     */
    protected function getJmsSerializer_Metadata_FileLocatorService()
    {
        return $this->services['jms_serializer.metadata.file_locator'] = new \Metadata\Driver\FileLocator(array('Symfony\\Bundle\\FrameworkBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/config/serializer', 'Symfony\\Bundle\\TwigBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/symfony/symfony/src/Symfony/Bundle/TwigBundle/Resources/config/serializer', 'Symfony\\Bundle\\MonologBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/symfony/monolog-bundle/Symfony/Bundle/MonologBundle/Resources/config/serializer', 'Doctrine\\Bundle\\DoctrineBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/doctrine/doctrine-bundle/Doctrine/Bundle/DoctrineBundle/Resources/config/serializer', 'JMS\\SerializerBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/jms/serializer-bundle/JMS/SerializerBundle/Resources/config/serializer', 'FOS\\RestBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/friendsofsymfony/rest-bundle/FOS/RestBundle/Resources/config/serializer', 'Symfony\\Bundle\\SecurityBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/symfony/symfony/src/Symfony/Bundle/SecurityBundle/Resources/config/serializer', 'Stof\\DoctrineExtensionsBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/stof/doctrine-extensions-bundle/Stof/DoctrineExtensionsBundle/Resources/config/serializer', 'Bazinga\\Bundle\\HateoasBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/willdurand/hateoas-bundle/Bazinga/Bundle/HateoasBundle/Resources/config/serializer', 'Liip\\ThemeBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/liip/theme-bundle/Liip/ThemeBundle/Resources/config/serializer', 'Sulu\\Bundle\\CoreBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/sulu/src/Sulu/Bundle/CoreBundle/Resources/config/serializer', 'Sulu\\Bundle\\AdminBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/admin-bundle/Sulu/Bundle/AdminBundle/Resources/config/serializer', 'Sulu\\Bundle\\SecurityBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Resources/config/serializer', 'Sulu\\Bundle\\ContactBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/contact-bundle/Sulu/Bundle/ContactBundle/Resources/config/serializer', 'Sulu\\Bundle\\TestBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/test-bundle/Sulu/Bundle/TestBundle/Resources/config/serializer', 'Sulu\\Bundle\\TagBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/tag-bundle/Sulu/Bundle/TagBundle/Resources/config/serializer', 'Sulu\\Bundle\\MediaBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/media-bundle/Sulu/Bundle/MediaBundle/Resources/config/serializer', 'Sulu\\Bundle\\CategoryBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/sulu/category-bundle/Sulu/Bundle/CategoryBundle/Resources/config/serializer', 'Doctrine\\Bundle\\PHPCRBundle' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/vendor/doctrine/phpcr-bundle/Doctrine/Bundle/PHPCRBundle/Resources/config/serializer'));
    }

    /**
     * Gets the 'jms_serializer.metadata_factory' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Metadata\MetadataFactory A Metadata\MetadataFactory instance.
     */
    protected function getJmsSerializer_MetadataFactoryService()
    {
        $this->services['jms_serializer.metadata_factory'] = $instance = new \Metadata\MetadataFactory(new \Metadata\Driver\LazyLoadingDriver($this, 'jms_serializer.metadata_driver'), 'Metadata\\ClassHierarchyMetadata', true);

        $instance->setCache(new \Metadata\Cache\FileCache('/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/jms_serializer'));

        return $instance;
    }

    /**
     * Gets the 'jms_serializer.unserialize_object_constructor' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return JMS\Serializer\Construction\UnserializeObjectConstructor A JMS\Serializer\Construction\UnserializeObjectConstructor instance.
     */
    protected function getJmsSerializer_UnserializeObjectConstructorService()
    {
        return $this->services['jms_serializer.unserialize_object_constructor'] = new \JMS\Serializer\Construction\UnserializeObjectConstructor();
    }

    /**
     * Gets the 'router.request_context' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Component\Routing\RequestContext A Symfony\Component\Routing\RequestContext instance.
     */
    protected function getRouter_RequestContextService()
    {
        return $this->services['router.request_context'] = new \Symfony\Component\Routing\RequestContext('', 'GET', 'localhost', 'http', 80, 443);
    }

    /**
     * Gets the 'security.access.decision_manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Component\Security\Core\Authorization\AccessDecisionManager A Symfony\Component\Security\Core\Authorization\AccessDecisionManager instance.
     */
    protected function getSecurity_Access_DecisionManagerService()
    {
        $a = $this->get('security.authentication.trust_resolver');

        $b = new \Symfony\Component\Security\Core\Role\RoleHierarchy(array());

        return $this->services['security.access.decision_manager'] = new \Symfony\Component\Security\Core\Authorization\AccessDecisionManager(array(0 => new \Sulu\Bundle\TestBundle\Testing\TestVoter(), 1 => new \Sulu\Bundle\SecurityBundle\Permission\PermissionVoter(array('view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8, 'archive' => 4, 'live' => 2, 'security' => 1)), 2 => new \Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter(new \Symfony\Component\Security\Core\Authorization\ExpressionLanguage(), $a, $b), 3 => new \Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter($b), 4 => new \Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter($a)), 'affirmative', false, true);
    }

    /**
     * Gets the 'security.authentication.manager' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager A Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager instance.
     */
    protected function getSecurity_Authentication_ManagerService()
    {
        $this->services['security.authentication.manager'] = $instance = new \Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager(array(0 => new \Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider('53eb5f53882b4')), true);

        $instance->setEventDispatcher($this->get('debug.event_dispatcher'));

        return $instance;
    }

    /**
     * Gets the 'security.authentication.trust_resolver' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver A Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver instance.
     */
    protected function getSecurity_Authentication_TrustResolverService()
    {
        return $this->services['security.authentication.trust_resolver'] = new \Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver('Symfony\\Component\\Security\\Core\\Authentication\\Token\\AnonymousToken', 'Symfony\\Component\\Security\\Core\\Authentication\\Token\\RememberMeToken');
    }

    /**
     * Gets the 'session.storage.metadata_bag' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Component\HttpFoundation\Session\Storage\MetadataBag A Symfony\Component\HttpFoundation\Session\Storage\MetadataBag instance.
     */
    protected function getSession_Storage_MetadataBagService()
    {
        return $this->services['session.storage.metadata_bag'] = new \Symfony\Component\HttpFoundation\Session\Storage\MetadataBag('_sf2_meta', '0');
    }

    /**
     * Gets the 'sulu.content.path_cleaner' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Sulu\Component\PHPCR\PathCleanup A Sulu\Component\PHPCR\PathCleanup instance.
     */
    protected function getSulu_Content_PathCleanerService()
    {
        return $this->services['sulu.content.path_cleaner'] = new \Sulu\Component\PHPCR\PathCleanup();
    }

    /**
     * Gets the 'templating.finder' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinder A Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinder instance.
     */
    protected function getTemplating_FinderService()
    {
        return $this->services['templating.finder'] = new \Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinder($this->get('kernel'), $this->get('templating.filename_parser'), '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/Resources');
    }

    /**
     * Gets the 'translator.selector' service.
     *
     * This service is shared.
     * This method always returns the same instance of the service.
     *
     * This service is private.
     * If you want to be able to request this service from the container directly,
     * make it public, otherwise you might end up with broken code.
     *
     * @return Symfony\Component\Translation\MessageSelector A Symfony\Component\Translation\MessageSelector instance.
     */
    protected function getTranslator_SelectorService()
    {
        return $this->services['translator.selector'] = new \Symfony\Component\Translation\MessageSelector();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter($name)
    {
        $name = strtolower($name);

        if (!(isset($this->parameters[$name]) || array_key_exists($name, $this->parameters))) {
            throw new InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }

        return $this->parameters[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameter($name)
    {
        $name = strtolower($name);

        return isset($this->parameters[$name]) || array_key_exists($name, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($name, $value)
    {
        throw new LogicException('Impossible to call set() on a frozen ParameterBag.');
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterBag()
    {
        if (null === $this->parameterBag) {
            $this->parameterBag = new FrozenParameterBag($this->parameters);
        }

        return $this->parameterBag;
    }
    /**
     * Gets the default parameters.
     *
     * @return array An array of the default parameters
     */
    protected function getDefaultParameters()
    {
        return array(
            'kernel.root_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app',
            'kernel.environment' => 'test',
            'kernel.debug' => true,
            'kernel.name' => 'app',
            'kernel.cache_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test',
            'kernel.logs_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/logs',
            'kernel.bundles' => array(
                'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'TwigBundle' => 'Symfony\\Bundle\\TwigBundle\\TwigBundle',
                'MonologBundle' => 'Symfony\\Bundle\\MonologBundle\\MonologBundle',
                'DoctrineBundle' => 'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle',
                'JMSSerializerBundle' => 'JMS\\SerializerBundle\\JMSSerializerBundle',
                'FOSRestBundle' => 'FOS\\RestBundle\\FOSRestBundle',
                'SecurityBundle' => 'Symfony\\Bundle\\SecurityBundle\\SecurityBundle',
                'StofDoctrineExtensionsBundle' => 'Stof\\DoctrineExtensionsBundle\\StofDoctrineExtensionsBundle',
                'BazingaHateoasBundle' => 'Bazinga\\Bundle\\HateoasBundle\\BazingaHateoasBundle',
                'LiipThemeBundle' => 'Liip\\ThemeBundle\\LiipThemeBundle',
                'SuluCoreBundle' => 'Sulu\\Bundle\\CoreBundle\\SuluCoreBundle',
                'SuluAdminBundle' => 'Sulu\\Bundle\\AdminBundle\\SuluAdminBundle',
                'SuluSecurityBundle' => 'Sulu\\Bundle\\SecurityBundle\\SuluSecurityBundle',
                'SuluContactBundle' => 'Sulu\\Bundle\\ContactBundle\\SuluContactBundle',
                'SuluTestBundle' => 'Sulu\\Bundle\\TestBundle\\SuluTestBundle',
                'SuluTagBundle' => 'Sulu\\Bundle\\TagBundle\\SuluTagBundle',
                'SuluMediaBundle' => 'Sulu\\Bundle\\MediaBundle\\SuluMediaBundle',
                'SuluCategoryBundle' => 'Sulu\\Bundle\\CategoryBundle\\SuluCategoryBundle',
                'DoctrinePHPCRBundle' => 'Doctrine\\Bundle\\PHPCRBundle\\DoctrinePHPCRBundle',
            ),
            'kernel.charset' => 'UTF-8',
            'kernel.container_class' => 'appTestDebugProjectContainer',
            'jms_serializer.cache_naming_strategy.class' => 'JMS\\Serializer\\Naming\\IdenticalPropertyNamingStrategy',
            'controller_resolver.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerResolver',
            'controller_name_converter.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerNameParser',
            'response_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\ResponseListener',
            'streamed_response_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\StreamedResponseListener',
            'locale_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\LocaleListener',
            'event_dispatcher.class' => 'Symfony\\Component\\EventDispatcher\\ContainerAwareEventDispatcher',
            'http_kernel.class' => 'Symfony\\Component\\HttpKernel\\DependencyInjection\\ContainerAwareHttpKernel',
            'filesystem.class' => 'Symfony\\Component\\Filesystem\\Filesystem',
            'cache_warmer.class' => 'Symfony\\Component\\HttpKernel\\CacheWarmer\\CacheWarmerAggregate',
            'cache_clearer.class' => 'Symfony\\Component\\HttpKernel\\CacheClearer\\ChainCacheClearer',
            'file_locator.class' => 'Symfony\\Component\\HttpKernel\\Config\\FileLocator',
            'uri_signer.class' => 'Symfony\\Component\\HttpKernel\\UriSigner',
            'request_stack.class' => 'Symfony\\Component\\HttpFoundation\\RequestStack',
            'fragment.handler.class' => 'Symfony\\Component\\HttpKernel\\Fragment\\FragmentHandler',
            'fragment.renderer.inline.class' => 'Symfony\\Component\\HttpKernel\\Fragment\\InlineFragmentRenderer',
            'fragment.renderer.hinclude.class' => 'Symfony\\Bundle\\FrameworkBundle\\Fragment\\ContainerAwareHIncludeFragmentRenderer',
            'fragment.renderer.hinclude.global_template' => NULL,
            'fragment.renderer.esi.class' => 'Symfony\\Component\\HttpKernel\\Fragment\\EsiFragmentRenderer',
            'fragment.path' => '/_fragment',
            'translator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\Translator',
            'translator.identity.class' => 'Symfony\\Component\\Translation\\IdentityTranslator',
            'translator.selector.class' => 'Symfony\\Component\\Translation\\MessageSelector',
            'translation.loader.php.class' => 'Symfony\\Component\\Translation\\Loader\\PhpFileLoader',
            'translation.loader.yml.class' => 'Symfony\\Component\\Translation\\Loader\\YamlFileLoader',
            'translation.loader.xliff.class' => 'Symfony\\Component\\Translation\\Loader\\XliffFileLoader',
            'translation.loader.po.class' => 'Symfony\\Component\\Translation\\Loader\\PoFileLoader',
            'translation.loader.mo.class' => 'Symfony\\Component\\Translation\\Loader\\MoFileLoader',
            'translation.loader.qt.class' => 'Symfony\\Component\\Translation\\Loader\\QtFileLoader',
            'translation.loader.csv.class' => 'Symfony\\Component\\Translation\\Loader\\CsvFileLoader',
            'translation.loader.res.class' => 'Symfony\\Component\\Translation\\Loader\\IcuResFileLoader',
            'translation.loader.dat.class' => 'Symfony\\Component\\Translation\\Loader\\IcuDatFileLoader',
            'translation.loader.ini.class' => 'Symfony\\Component\\Translation\\Loader\\IniFileLoader',
            'translation.loader.json.class' => 'Symfony\\Component\\Translation\\Loader\\JsonFileLoader',
            'translation.dumper.php.class' => 'Symfony\\Component\\Translation\\Dumper\\PhpFileDumper',
            'translation.dumper.xliff.class' => 'Symfony\\Component\\Translation\\Dumper\\XliffFileDumper',
            'translation.dumper.po.class' => 'Symfony\\Component\\Translation\\Dumper\\PoFileDumper',
            'translation.dumper.mo.class' => 'Symfony\\Component\\Translation\\Dumper\\MoFileDumper',
            'translation.dumper.yml.class' => 'Symfony\\Component\\Translation\\Dumper\\YamlFileDumper',
            'translation.dumper.qt.class' => 'Symfony\\Component\\Translation\\Dumper\\QtFileDumper',
            'translation.dumper.csv.class' => 'Symfony\\Component\\Translation\\Dumper\\CsvFileDumper',
            'translation.dumper.ini.class' => 'Symfony\\Component\\Translation\\Dumper\\IniFileDumper',
            'translation.dumper.json.class' => 'Symfony\\Component\\Translation\\Dumper\\JsonFileDumper',
            'translation.dumper.res.class' => 'Symfony\\Component\\Translation\\Dumper\\IcuResFileDumper',
            'translation.extractor.php.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\PhpExtractor',
            'translation.loader.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\TranslationLoader',
            'translation.extractor.class' => 'Symfony\\Component\\Translation\\Extractor\\ChainExtractor',
            'translation.writer.class' => 'Symfony\\Component\\Translation\\Writer\\TranslationWriter',
            'property_accessor.class' => 'Symfony\\Component\\PropertyAccess\\PropertyAccessor',
            'debug.errors_logger_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\ErrorsLoggerListener',
            'debug.event_dispatcher.class' => 'Symfony\\Component\\HttpKernel\\Debug\\TraceableEventDispatcher',
            'debug.stopwatch.class' => 'Symfony\\Component\\Stopwatch\\Stopwatch',
            'debug.container.dump' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/appTestDebugProjectContainer.xml',
            'debug.controller_resolver.class' => 'Symfony\\Component\\HttpKernel\\Controller\\TraceableControllerResolver',
            'debug.debug_handlers_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\DebugHandlersListener',
            'kernel.secret' => 'secret',
            'kernel.http_method_override' => true,
            'kernel.trusted_hosts' => array(

            ),
            'kernel.trusted_proxies' => array(

            ),
            'kernel.default_locale' => 'en',
            'test.client.class' => 'Symfony\\Bundle\\FrameworkBundle\\Client',
            'test.client.parameters' => array(

            ),
            'test.client.history.class' => 'Symfony\\Component\\BrowserKit\\History',
            'test.client.cookiejar.class' => 'Symfony\\Component\\BrowserKit\\CookieJar',
            'test.session.listener.class' => 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\TestSessionListener',
            'session.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Session',
            'session.flashbag.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Flash\\FlashBag',
            'session.attribute_bag.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Attribute\\AttributeBag',
            'session.storage.metadata_bag.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\MetadataBag',
            'session.metadata.storage_key' => '_sf2_meta',
            'session.storage.native.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\NativeSessionStorage',
            'session.storage.php_bridge.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\PhpBridgeSessionStorage',
            'session.storage.mock_file.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\MockFileSessionStorage',
            'session.handler.native_file.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\Handler\\NativeFileSessionHandler',
            'session.handler.write_check.class' => 'Symfony\\Component\\HttpFoundation\\Session\\Storage\\Handler\\WriteCheckSessionHandler',
            'session_listener.class' => 'Symfony\\Bundle\\FrameworkBundle\\EventListener\\SessionListener',
            'session.storage.options' => array(
                'gc_probability' => 1,
            ),
            'session.save_path' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/sessions',
            'session.metadata.update_threshold' => '0',
            'security.secure_random.class' => 'Symfony\\Component\\Security\\Core\\Util\\SecureRandom',
            'templating.engine.delegating.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\DelegatingEngine',
            'templating.name_parser.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\TemplateNameParser',
            'templating.filename_parser.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\TemplateFilenameParser',
            'templating.cache_warmer.template_paths.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\TemplatePathsCacheWarmer',
            'templating.locator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Loader\\TemplateLocator',
            'templating.loader.filesystem.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Loader\\FilesystemLoader',
            'templating.loader.cache.class' => 'Symfony\\Component\\Templating\\Loader\\CacheLoader',
            'templating.loader.chain.class' => 'Symfony\\Component\\Templating\\Loader\\ChainLoader',
            'templating.finder.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\TemplateFinder',
            'templating.engine.php.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\PhpEngine',
            'templating.helper.slots.class' => 'Symfony\\Component\\Templating\\Helper\\SlotsHelper',
            'templating.helper.assets.class' => 'Symfony\\Component\\Templating\\Helper\\CoreAssetsHelper',
            'templating.helper.actions.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\ActionsHelper',
            'templating.helper.router.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\RouterHelper',
            'templating.helper.request.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\RequestHelper',
            'templating.helper.session.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\SessionHelper',
            'templating.helper.code.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\CodeHelper',
            'templating.helper.translator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\TranslatorHelper',
            'templating.helper.form.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\FormHelper',
            'templating.helper.stopwatch.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Helper\\StopwatchHelper',
            'templating.form.engine.class' => 'Symfony\\Component\\Form\\Extension\\Templating\\TemplatingRendererEngine',
            'templating.form.renderer.class' => 'Symfony\\Component\\Form\\FormRenderer',
            'templating.globals.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\GlobalVariables',
            'templating.asset.path_package.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Asset\\PathPackage',
            'templating.asset.url_package.class' => 'Symfony\\Component\\Templating\\Asset\\UrlPackage',
            'templating.asset.package_factory.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\Asset\\PackageFactory',
            'templating.helper.code.file_link_format' => NULL,
            'templating.helper.form.resources' => array(
                0 => 'FrameworkBundle:Form',
            ),
            'debug.templating.engine.php.class' => 'Symfony\\Bundle\\FrameworkBundle\\Templating\\TimedPhpEngine',
            'templating.loader.cache.path' => NULL,
            'templating.engines' => array(
                0 => 'twig',
            ),
            'data_collector.templates' => array(

            ),
            'router.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\Router',
            'router.request_context.class' => 'Symfony\\Component\\Routing\\RequestContext',
            'routing.loader.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\DelegatingLoader',
            'routing.resolver.class' => 'Symfony\\Component\\Config\\Loader\\LoaderResolver',
            'routing.loader.xml.class' => 'Symfony\\Component\\Routing\\Loader\\XmlFileLoader',
            'routing.loader.yml.class' => 'Symfony\\Component\\Routing\\Loader\\YamlFileLoader',
            'routing.loader.php.class' => 'Symfony\\Component\\Routing\\Loader\\PhpFileLoader',
            'router.options.generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper',
            'router.options.matcher_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher',
            'router.options.matcher_base_class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\RedirectableUrlMatcher',
            'router.options.matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper',
            'router.cache_warmer.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\RouterCacheWarmer',
            'router.options.matcher.cache_class' => 'appTestUrlMatcher',
            'router.options.generator.cache_class' => 'appTestUrlGenerator',
            'router_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\RouterListener',
            'router.request_context.host' => 'localhost',
            'router.request_context.scheme' => 'http',
            'router.request_context.base_url' => '',
            'router.resource' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/config/routing.yml',
            'router.cache_class_prefix' => 'appTest',
            'request_listener.http_port' => 80,
            'request_listener.https_port' => 443,
            'annotations.reader.class' => 'Doctrine\\Common\\Annotations\\AnnotationReader',
            'annotations.cached_reader.class' => 'Doctrine\\Common\\Annotations\\CachedReader',
            'annotations.file_cache_reader.class' => 'Doctrine\\Common\\Annotations\\FileCacheReader',
            'twig.class' => 'Twig_Environment',
            'twig.loader.filesystem.class' => 'Symfony\\Bundle\\TwigBundle\\Loader\\FilesystemLoader',
            'twig.loader.chain.class' => 'Twig_Loader_Chain',
            'templating.engine.twig.class' => 'Symfony\\Bundle\\TwigBundle\\TwigEngine',
            'twig.cache_warmer.class' => 'Symfony\\Bundle\\TwigBundle\\CacheWarmer\\TemplateCacheCacheWarmer',
            'twig.extension.trans.class' => 'Symfony\\Bridge\\Twig\\Extension\\TranslationExtension',
            'twig.extension.assets.class' => 'Symfony\\Bundle\\TwigBundle\\Extension\\AssetsExtension',
            'twig.extension.actions.class' => 'Symfony\\Bundle\\TwigBundle\\Extension\\ActionsExtension',
            'twig.extension.code.class' => 'Symfony\\Bridge\\Twig\\Extension\\CodeExtension',
            'twig.extension.routing.class' => 'Symfony\\Bridge\\Twig\\Extension\\RoutingExtension',
            'twig.extension.yaml.class' => 'Symfony\\Bridge\\Twig\\Extension\\YamlExtension',
            'twig.extension.form.class' => 'Symfony\\Bridge\\Twig\\Extension\\FormExtension',
            'twig.extension.httpkernel.class' => 'Symfony\\Bridge\\Twig\\Extension\\HttpKernelExtension',
            'twig.extension.debug.stopwatch.class' => 'Symfony\\Bridge\\Twig\\Extension\\StopwatchExtension',
            'twig.extension.expression.class' => 'Symfony\\Bridge\\Twig\\Extension\\ExpressionExtension',
            'twig.form.engine.class' => 'Symfony\\Bridge\\Twig\\Form\\TwigRendererEngine',
            'twig.form.renderer.class' => 'Symfony\\Bridge\\Twig\\Form\\TwigRenderer',
            'twig.translation.extractor.class' => 'Symfony\\Bridge\\Twig\\Translation\\TwigExtractor',
            'twig.exception_listener.class' => 'Symfony\\Component\\HttpKernel\\EventListener\\ExceptionListener',
            'twig.controller.exception.class' => 'Symfony\\Bundle\\TwigBundle\\Controller\\ExceptionController',
            'twig.exception_listener.controller' => 'twig.controller.exception:showAction',
            'twig.form.resources' => array(
                0 => 'form_div_layout.html.twig',
            ),
            'debug.templating.engine.twig.class' => 'Symfony\\Bundle\\TwigBundle\\Debug\\TimedTwigEngine',
            'twig.options' => array(
                'exception_controller' => 'twig.controller.exception:showAction',
                'autoescape_service' => NULL,
                'autoescape_service_method' => NULL,
                'cache' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/twig',
                'charset' => 'UTF-8',
                'debug' => true,
                'paths' => array(

                ),
            ),
            'monolog.logger.class' => 'Symfony\\Bridge\\Monolog\\Logger',
            'monolog.gelf.publisher.class' => 'Gelf\\MessagePublisher',
            'monolog.handler.stream.class' => 'Monolog\\Handler\\StreamHandler',
            'monolog.handler.console.class' => 'Symfony\\Bridge\\Monolog\\Handler\\ConsoleHandler',
            'monolog.handler.group.class' => 'Monolog\\Handler\\GroupHandler',
            'monolog.handler.buffer.class' => 'Monolog\\Handler\\BufferHandler',
            'monolog.handler.rotating_file.class' => 'Monolog\\Handler\\RotatingFileHandler',
            'monolog.handler.syslog.class' => 'Monolog\\Handler\\SyslogHandler',
            'monolog.handler.null.class' => 'Monolog\\Handler\\NullHandler',
            'monolog.handler.test.class' => 'Monolog\\Handler\\TestHandler',
            'monolog.handler.gelf.class' => 'Monolog\\Handler\\GelfHandler',
            'monolog.handler.firephp.class' => 'Symfony\\Bridge\\Monolog\\Handler\\FirePHPHandler',
            'monolog.handler.chromephp.class' => 'Symfony\\Bridge\\Monolog\\Handler\\ChromePhpHandler',
            'monolog.handler.debug.class' => 'Symfony\\Bridge\\Monolog\\Handler\\DebugHandler',
            'monolog.handler.swift_mailer.class' => 'Symfony\\Bridge\\Monolog\\Handler\\SwiftMailerHandler',
            'monolog.handler.native_mailer.class' => 'Monolog\\Handler\\NativeMailerHandler',
            'monolog.handler.socket.class' => 'Monolog\\Handler\\SocketHandler',
            'monolog.handler.pushover.class' => 'Monolog\\Handler\\PushoverHandler',
            'monolog.handler.raven.class' => 'Monolog\\Handler\\RavenHandler',
            'monolog.handler.newrelic.class' => 'Monolog\\Handler\\NewRelicHandler',
            'monolog.handler.hipchat.class' => 'Monolog\\Handler\\HipChatHandler',
            'monolog.handler.cube.class' => 'Monolog\\Handler\\CubeHandler',
            'monolog.handler.amqp.class' => 'Monolog\\Handler\\AmqpHandler',
            'monolog.handler.error_log.class' => 'Monolog\\Handler\\ErrorLogHandler',
            'monolog.activation_strategy.not_found.class' => 'Symfony\\Bundle\\MonologBundle\\NotFoundActivationStrategy',
            'monolog.handler.fingers_crossed.class' => 'Monolog\\Handler\\FingersCrossedHandler',
            'monolog.handler.fingers_crossed.error_level_activation_strategy.class' => 'Monolog\\Handler\\FingersCrossed\\ErrorLevelActivationStrategy',
            'monolog.handlers_to_channels' => array(
                'monolog.handler.main' => NULL,
            ),
            'doctrine.dbal.logger.chain.class' => 'Doctrine\\DBAL\\Logging\\LoggerChain',
            'doctrine.dbal.logger.profiling.class' => 'Doctrine\\DBAL\\Logging\\DebugStack',
            'doctrine.dbal.logger.class' => 'Symfony\\Bridge\\Doctrine\\Logger\\DbalLogger',
            'doctrine.dbal.configuration.class' => 'Doctrine\\DBAL\\Configuration',
            'doctrine.data_collector.class' => 'Doctrine\\Bundle\\DoctrineBundle\\DataCollector\\DoctrineDataCollector',
            'doctrine.dbal.connection.event_manager.class' => 'Symfony\\Bridge\\Doctrine\\ContainerAwareEventManager',
            'doctrine.dbal.connection_factory.class' => 'Doctrine\\Bundle\\DoctrineBundle\\ConnectionFactory',
            'doctrine.dbal.events.mysql_session_init.class' => 'Doctrine\\DBAL\\Event\\Listeners\\MysqlSessionInit',
            'doctrine.dbal.events.oracle_session_init.class' => 'Doctrine\\DBAL\\Event\\Listeners\\OracleSessionInit',
            'doctrine.class' => 'Doctrine\\Bundle\\DoctrineBundle\\Registry',
            'doctrine.entity_managers' => array(
                'default' => 'doctrine.orm.default_entity_manager',
            ),
            'doctrine.default_entity_manager' => 'default',
            'doctrine.dbal.connection_factory.types' => array(

            ),
            'doctrine.connections' => array(
                'default' => 'doctrine.dbal.default_connection',
            ),
            'doctrine.default_connection' => 'default',
            'doctrine.orm.configuration.class' => 'Doctrine\\ORM\\Configuration',
            'doctrine.orm.entity_manager.class' => 'Doctrine\\ORM\\EntityManager',
            'doctrine.orm.manager_configurator.class' => 'Doctrine\\Bundle\\DoctrineBundle\\ManagerConfigurator',
            'doctrine.orm.cache.array.class' => 'Doctrine\\Common\\Cache\\ArrayCache',
            'doctrine.orm.cache.apc.class' => 'Doctrine\\Common\\Cache\\ApcCache',
            'doctrine.orm.cache.memcache.class' => 'Doctrine\\Common\\Cache\\MemcacheCache',
            'doctrine.orm.cache.memcache_host' => 'localhost',
            'doctrine.orm.cache.memcache_port' => 11211,
            'doctrine.orm.cache.memcache_instance.class' => 'Memcache',
            'doctrine.orm.cache.memcached.class' => 'Doctrine\\Common\\Cache\\MemcachedCache',
            'doctrine.orm.cache.memcached_host' => 'localhost',
            'doctrine.orm.cache.memcached_port' => 11211,
            'doctrine.orm.cache.memcached_instance.class' => 'Memcached',
            'doctrine.orm.cache.redis.class' => 'Doctrine\\Common\\Cache\\RedisCache',
            'doctrine.orm.cache.redis_host' => 'localhost',
            'doctrine.orm.cache.redis_port' => 6379,
            'doctrine.orm.cache.redis_instance.class' => 'Redis',
            'doctrine.orm.cache.xcache.class' => 'Doctrine\\Common\\Cache\\XcacheCache',
            'doctrine.orm.cache.wincache.class' => 'Doctrine\\Common\\Cache\\WinCacheCache',
            'doctrine.orm.cache.zenddata.class' => 'Doctrine\\Common\\Cache\\ZendDataCache',
            'doctrine.orm.metadata.driver_chain.class' => 'Doctrine\\ORM\\Mapping\\Driver\\DriverChain',
            'doctrine.orm.metadata.annotation.class' => 'Doctrine\\ORM\\Mapping\\Driver\\AnnotationDriver',
            'doctrine.orm.metadata.xml.class' => 'Doctrine\\ORM\\Mapping\\Driver\\SimplifiedXmlDriver',
            'doctrine.orm.metadata.yml.class' => 'Doctrine\\ORM\\Mapping\\Driver\\SimplifiedYamlDriver',
            'doctrine.orm.metadata.php.class' => 'Doctrine\\ORM\\Mapping\\Driver\\PHPDriver',
            'doctrine.orm.metadata.staticphp.class' => 'Doctrine\\ORM\\Mapping\\Driver\\StaticPHPDriver',
            'doctrine.orm.proxy_cache_warmer.class' => 'Symfony\\Bridge\\Doctrine\\CacheWarmer\\ProxyCacheWarmer',
            'form.type_guesser.doctrine.class' => 'Symfony\\Bridge\\Doctrine\\Form\\DoctrineOrmTypeGuesser',
            'doctrine.orm.validator.unique.class' => 'Symfony\\Bridge\\Doctrine\\Validator\\Constraints\\UniqueEntityValidator',
            'doctrine.orm.validator_initializer.class' => 'Symfony\\Bridge\\Doctrine\\Validator\\DoctrineInitializer',
            'doctrine.orm.security.user.provider.class' => 'Symfony\\Bridge\\Doctrine\\Security\\User\\EntityUserProvider',
            'doctrine.orm.listeners.resolve_target_entity.class' => 'Doctrine\\ORM\\Tools\\ResolveTargetEntityListener',
            'doctrine.orm.naming_strategy.default.class' => 'Doctrine\\ORM\\Mapping\\DefaultNamingStrategy',
            'doctrine.orm.naming_strategy.underscore.class' => 'Doctrine\\ORM\\Mapping\\UnderscoreNamingStrategy',
            'doctrine.orm.auto_generate_proxy_classes' => true,
            'doctrine.orm.proxy_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/doctrine/orm/Proxies',
            'doctrine.orm.proxy_namespace' => 'Proxies',
            'jms_serializer.metadata.file_locator.class' => 'Metadata\\Driver\\FileLocator',
            'jms_serializer.metadata.annotation_driver.class' => 'JMS\\Serializer\\Metadata\\Driver\\AnnotationDriver',
            'jms_serializer.metadata.chain_driver.class' => 'Metadata\\Driver\\DriverChain',
            'jms_serializer.metadata.yaml_driver.class' => 'JMS\\Serializer\\Metadata\\Driver\\YamlDriver',
            'jms_serializer.metadata.xml_driver.class' => 'JMS\\Serializer\\Metadata\\Driver\\XmlDriver',
            'jms_serializer.metadata.php_driver.class' => 'JMS\\Serializer\\Metadata\\Driver\\PhpDriver',
            'jms_serializer.metadata.doctrine_type_driver.class' => 'JMS\\Serializer\\Metadata\\Driver\\DoctrineTypeDriver',
            'jms_serializer.metadata.doctrine_phpcr_type_driver.class' => 'JMS\\Serializer\\Metadata\\Driver\\DoctrinePHPCRTypeDriver',
            'jms_serializer.metadata.lazy_loading_driver.class' => 'Metadata\\Driver\\LazyLoadingDriver',
            'jms_serializer.metadata.metadata_factory.class' => 'Metadata\\MetadataFactory',
            'jms_serializer.metadata.cache.file_cache.class' => 'Metadata\\Cache\\FileCache',
            'jms_serializer.event_dispatcher.class' => 'JMS\\Serializer\\EventDispatcher\\LazyEventDispatcher',
            'jms_serializer.camel_case_naming_strategy.class' => 'JMS\\Serializer\\Naming\\CamelCaseNamingStrategy',
            'jms_serializer.serialized_name_annotation_strategy.class' => 'JMS\\Serializer\\Naming\\SerializedNameAnnotationStrategy',
            'jms_serializer.doctrine_object_constructor.class' => 'JMS\\Serializer\\Construction\\DoctrineObjectConstructor',
            'jms_serializer.unserialize_object_constructor.class' => 'JMS\\Serializer\\Construction\\UnserializeObjectConstructor',
            'jms_serializer.version_exclusion_strategy.class' => 'JMS\\Serializer\\Exclusion\\VersionExclusionStrategy',
            'jms_serializer.serializer.class' => 'JMS\\Serializer\\Serializer',
            'jms_serializer.twig_extension.class' => 'JMS\\Serializer\\Twig\\SerializerExtension',
            'jms_serializer.templating.helper.class' => 'JMS\\SerializerBundle\\Templating\\SerializerHelper',
            'jms_serializer.json_serialization_visitor.class' => 'JMS\\Serializer\\JsonSerializationVisitor',
            'jms_serializer.json_serialization_visitor.options' => 0,
            'jms_serializer.json_deserialization_visitor.class' => 'JMS\\Serializer\\JsonDeserializationVisitor',
            'jms_serializer.xml_serialization_visitor.class' => 'JMS\\Serializer\\XmlSerializationVisitor',
            'jms_serializer.xml_deserialization_visitor.class' => 'JMS\\Serializer\\XmlDeserializationVisitor',
            'jms_serializer.xml_deserialization_visitor.doctype_whitelist' => array(

            ),
            'jms_serializer.yaml_serialization_visitor.class' => 'JMS\\Serializer\\YamlSerializationVisitor',
            'jms_serializer.handler_registry.class' => 'JMS\\Serializer\\Handler\\LazyHandlerRegistry',
            'jms_serializer.datetime_handler.class' => 'JMS\\Serializer\\Handler\\DateHandler',
            'jms_serializer.array_collection_handler.class' => 'JMS\\Serializer\\Handler\\ArrayCollectionHandler',
            'jms_serializer.php_collection_handler.class' => 'JMS\\Serializer\\Handler\\PhpCollectionHandler',
            'jms_serializer.form_error_handler.class' => 'JMS\\Serializer\\Handler\\FormErrorHandler',
            'jms_serializer.constraint_violation_handler.class' => 'JMS\\Serializer\\Handler\\ConstraintViolationHandler',
            'jms_serializer.doctrine_proxy_subscriber.class' => 'JMS\\Serializer\\EventDispatcher\\Subscriber\\DoctrineProxySubscriber',
            'jms_serializer.stopwatch_subscriber.class' => 'JMS\\SerializerBundle\\Serializer\\StopwatchEventSubscriber',
            'jms_serializer.infer_types_from_doctrine_metadata' => true,
            'fos_rest.serializer.exclusion_strategy.version' => '',
            'fos_rest.serializer.exclusion_strategy.groups' => '',
            'fos_rest.view_handler.jsonp.callback_param' => '',
            'fos_rest.view.exception_wrapper_handler' => 'FOS\\RestBundle\\View\\ExceptionWrapperHandler',
            'fos_rest.view_handler.default.class' => 'FOS\\RestBundle\\View\\ViewHandler',
            'fos_rest.view_handler.jsonp.class' => 'FOS\\RestBundle\\View\\JsonpHandler',
            'fos_rest.serializer.exception_wrapper_serialize_handler.class' => 'FOS\\RestBundle\\Serializer\\ExceptionWrapperSerializeHandler',
            'fos_rest.routing.loader.controller.class' => 'FOS\\RestBundle\\Routing\\Loader\\RestRouteLoader',
            'fos_rest.routing.loader.yaml_collection.class' => 'FOS\\RestBundle\\Routing\\Loader\\RestYamlCollectionLoader',
            'fos_rest.routing.loader.xml_collection.class' => 'FOS\\RestBundle\\Routing\\Loader\\RestXmlCollectionLoader',
            'fos_rest.routing.loader.processor.class' => 'FOS\\RestBundle\\Routing\\Loader\\RestRouteProcessor',
            'fos_rest.routing.loader.reader.controller.class' => 'FOS\\RestBundle\\Routing\\Loader\\Reader\\RestControllerReader',
            'fos_rest.routing.loader.reader.action.class' => 'FOS\\RestBundle\\Routing\\Loader\\Reader\\RestActionReader',
            'fos_rest.format_negotiator.class' => 'FOS\\RestBundle\\Util\\FormatNegotiator',
            'fos_rest.inflector.class' => 'FOS\\RestBundle\\Util\\Inflector\\DoctrineInflector',
            'fos_rest.request_matcher.class' => 'Symfony\\Component\\HttpFoundation\\RequestMatcher',
            'fos_rest.violation_formatter.class' => 'FOS\\RestBundle\\Util\\ViolationFormatter',
            'fos_rest.request.param_fetcher.class' => 'FOS\\RestBundle\\Request\\ParamFetcher',
            'fos_rest.request.param_fetcher.reader.class' => 'FOS\\RestBundle\\Request\\ParamReader',
            'fos_rest.cache_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/fos_rest',
            'fos_rest.serializer.serialize_null' => false,
            'fos_rest.formats' => array(
                'json' => false,
                'xml' => false,
                'html' => true,
            ),
            'fos_rest.default_engine' => 'twig',
            'fos_rest.force_redirects' => array(
                'html' => 302,
            ),
            'fos_rest.failed_validation' => 400,
            'fos_rest.empty_content' => 204,
            'fos_rest.serialize_null' => false,
            'fos_rest.routing.loader.default_format' => 'json',
            'fos_rest.routing.loader.include_format' => true,
            'fos_rest.exception.codes' => array(

            ),
            'fos_rest.exception.messages' => array(

            ),
            'fos_rest.normalizer.camel_keys.class' => 'FOS\\RestBundle\\Normalizer\\CamelKeysNormalizer',
            'fos_rest.decoder.json.class' => 'FOS\\RestBundle\\Decoder\\JsonDecoder',
            'fos_rest.decoder.jsontoform.class' => 'FOS\\RestBundle\\Decoder\\JsonToFormDecoder',
            'fos_rest.decoder.xml.class' => 'FOS\\RestBundle\\Decoder\\XmlDecoder',
            'fos_rest.decoder_provider.class' => 'FOS\\RestBundle\\Decoder\\ContainerDecoderProvider',
            'fos_rest.body_listener.class' => 'FOS\\RestBundle\\EventListener\\BodyListener',
            'fos_rest.throw_exception_on_unsupported_content_type' => false,
            'fos_rest.decoders' => array(
                'json' => 'fos_rest.decoder.json',
                'xml' => 'fos_rest.decoder.xml',
            ),
            'fos_rest.mime_types' => array(

            ),
            'fos_rest.converter.request_body.validation_errors_argument' => 'validationErrors',
            'security.context.class' => 'Symfony\\Component\\Security\\Core\\SecurityContext',
            'security.user_checker.class' => 'Symfony\\Component\\Security\\Core\\User\\UserChecker',
            'security.encoder_factory.generic.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\EncoderFactory',
            'security.encoder.digest.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\MessageDigestPasswordEncoder',
            'security.encoder.plain.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\PlaintextPasswordEncoder',
            'security.encoder.pbkdf2.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\Pbkdf2PasswordEncoder',
            'security.encoder.bcrypt.class' => 'Symfony\\Component\\Security\\Core\\Encoder\\BCryptPasswordEncoder',
            'security.user.provider.in_memory.class' => 'Symfony\\Component\\Security\\Core\\User\\InMemoryUserProvider',
            'security.user.provider.in_memory.user.class' => 'Symfony\\Component\\Security\\Core\\User\\User',
            'security.user.provider.chain.class' => 'Symfony\\Component\\Security\\Core\\User\\ChainUserProvider',
            'security.authentication.trust_resolver.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\AuthenticationTrustResolver',
            'security.authentication.trust_resolver.anonymous_class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Token\\AnonymousToken',
            'security.authentication.trust_resolver.rememberme_class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Token\\RememberMeToken',
            'security.authentication.manager.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\AuthenticationProviderManager',
            'security.authentication.session_strategy.class' => 'Symfony\\Component\\Security\\Http\\Session\\SessionAuthenticationStrategy',
            'security.access.decision_manager.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\AccessDecisionManager',
            'security.access.simple_role_voter.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\RoleVoter',
            'security.access.authenticated_voter.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\AuthenticatedVoter',
            'security.access.role_hierarchy_voter.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\RoleHierarchyVoter',
            'security.access.expression_voter.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\ExpressionVoter',
            'security.firewall.class' => 'Symfony\\Component\\Security\\Http\\Firewall',
            'security.firewall.map.class' => 'Symfony\\Bundle\\SecurityBundle\\Security\\FirewallMap',
            'security.firewall.context.class' => 'Symfony\\Bundle\\SecurityBundle\\Security\\FirewallContext',
            'security.matcher.class' => 'Symfony\\Component\\HttpFoundation\\RequestMatcher',
            'security.expression_matcher.class' => 'Symfony\\Component\\HttpFoundation\\ExpressionRequestMatcher',
            'security.role_hierarchy.class' => 'Symfony\\Component\\Security\\Core\\Role\\RoleHierarchy',
            'security.http_utils.class' => 'Symfony\\Component\\Security\\Http\\HttpUtils',
            'security.validator.user_password.class' => 'Symfony\\Component\\Security\\Core\\Validator\\Constraints\\UserPasswordValidator',
            'security.expression_language.class' => 'Symfony\\Component\\Security\\Core\\Authorization\\ExpressionLanguage',
            'security.authentication.retry_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\RetryAuthenticationEntryPoint',
            'security.channel_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\ChannelListener',
            'security.authentication.form_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\FormAuthenticationEntryPoint',
            'security.authentication.listener.form.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\UsernamePasswordFormAuthenticationListener',
            'security.authentication.listener.simple_form.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\SimpleFormAuthenticationListener',
            'security.authentication.listener.simple_preauth.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\SimplePreAuthenticationListener',
            'security.authentication.listener.basic.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\BasicAuthenticationListener',
            'security.authentication.basic_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\BasicAuthenticationEntryPoint',
            'security.authentication.listener.digest.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\DigestAuthenticationListener',
            'security.authentication.digest_entry_point.class' => 'Symfony\\Component\\Security\\Http\\EntryPoint\\DigestAuthenticationEntryPoint',
            'security.authentication.listener.x509.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\X509AuthenticationListener',
            'security.authentication.listener.anonymous.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\AnonymousAuthenticationListener',
            'security.authentication.switchuser_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\SwitchUserListener',
            'security.logout_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\LogoutListener',
            'security.logout.handler.session.class' => 'Symfony\\Component\\Security\\Http\\Logout\\SessionLogoutHandler',
            'security.logout.handler.cookie_clearing.class' => 'Symfony\\Component\\Security\\Http\\Logout\\CookieClearingLogoutHandler',
            'security.logout.success_handler.class' => 'Symfony\\Component\\Security\\Http\\Logout\\DefaultLogoutSuccessHandler',
            'security.access_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\AccessListener',
            'security.access_map.class' => 'Symfony\\Component\\Security\\Http\\AccessMap',
            'security.exception_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\ExceptionListener',
            'security.context_listener.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\ContextListener',
            'security.authentication.provider.dao.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\DaoAuthenticationProvider',
            'security.authentication.provider.simple.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\SimpleAuthenticationProvider',
            'security.authentication.provider.pre_authenticated.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\PreAuthenticatedAuthenticationProvider',
            'security.authentication.provider.anonymous.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\AnonymousAuthenticationProvider',
            'security.authentication.success_handler.class' => 'Symfony\\Component\\Security\\Http\\Authentication\\DefaultAuthenticationSuccessHandler',
            'security.authentication.failure_handler.class' => 'Symfony\\Component\\Security\\Http\\Authentication\\DefaultAuthenticationFailureHandler',
            'security.authentication.simple_success_failure_handler.class' => 'Symfony\\Component\\Security\\Http\\Authentication\\SimpleAuthenticationHandler',
            'security.authentication.provider.rememberme.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\Provider\\RememberMeAuthenticationProvider',
            'security.authentication.listener.rememberme.class' => 'Symfony\\Component\\Security\\Http\\Firewall\\RememberMeListener',
            'security.rememberme.token.provider.in_memory.class' => 'Symfony\\Component\\Security\\Core\\Authentication\\RememberMe\\InMemoryTokenProvider',
            'security.authentication.rememberme.services.persistent.class' => 'Symfony\\Component\\Security\\Http\\RememberMe\\PersistentTokenBasedRememberMeServices',
            'security.authentication.rememberme.services.simplehash.class' => 'Symfony\\Component\\Security\\Http\\RememberMe\\TokenBasedRememberMeServices',
            'security.rememberme.response_listener.class' => 'Symfony\\Component\\Security\\Http\\RememberMe\\ResponseListener',
            'templating.helper.logout_url.class' => 'Symfony\\Bundle\\SecurityBundle\\Templating\\Helper\\LogoutUrlHelper',
            'templating.helper.security.class' => 'Symfony\\Bundle\\SecurityBundle\\Templating\\Helper\\SecurityHelper',
            'twig.extension.logout_url.class' => 'Symfony\\Bundle\\SecurityBundle\\Twig\\Extension\\LogoutUrlExtension',
            'twig.extension.security.class' => 'Symfony\\Bridge\\Twig\\Extension\\SecurityExtension',
            'data_collector.security.class' => 'Symfony\\Bundle\\SecurityBundle\\DataCollector\\SecurityDataCollector',
            'security.access.denied_url' => NULL,
            'security.authentication.manager.erase_credentials' => true,
            'security.authentication.session_strategy.strategy' => 'migrate',
            'security.access.always_authenticate_before_granting' => false,
            'security.authentication.hide_user_not_found' => true,
            'security.role_hierarchy.roles' => array(

            ),
            'stof_doctrine_extensions.event_listener.locale.class' => 'Stof\\DoctrineExtensionsBundle\\EventListener\\LocaleListener',
            'stof_doctrine_extensions.event_listener.logger.class' => 'Stof\\DoctrineExtensionsBundle\\EventListener\\LoggerListener',
            'stof_doctrine_extensions.event_listener.blame.class' => 'Stof\\DoctrineExtensionsBundle\\EventListener\\BlameListener',
            'stof_doctrine_extensions.uploadable.manager.class' => 'Stof\\DoctrineExtensionsBundle\\Uploadable\\UploadableManager',
            'stof_doctrine_extensions.uploadable.mime_type_guesser.class' => 'Stof\\DoctrineExtensionsBundle\\Uploadable\\MimeTypeGuesserAdapter',
            'stof_doctrine_extensions.uploadable.default_file_info.class' => 'Stof\\DoctrineExtensionsBundle\\Uploadable\\UploadedFileInfo',
            'stof_doctrine_extensions.default_locale' => 'en',
            'stof_doctrine_extensions.default_file_path' => NULL,
            'stof_doctrine_extensions.translation_fallback' => false,
            'stof_doctrine_extensions.persist_default_translation' => false,
            'stof_doctrine_extensions.skip_translation_on_load' => false,
            'stof_doctrine_extensions.uploadable.validate_writable_directory' => true,
            'stof_doctrine_extensions.listener.translatable.class' => 'Gedmo\\Translatable\\TranslatableListener',
            'stof_doctrine_extensions.listener.timestampable.class' => 'Gedmo\\Timestampable\\TimestampableListener',
            'stof_doctrine_extensions.listener.blameable.class' => 'Gedmo\\Blameable\\BlameableListener',
            'stof_doctrine_extensions.listener.sluggable.class' => 'Gedmo\\Sluggable\\SluggableListener',
            'stof_doctrine_extensions.listener.tree.class' => 'Gedmo\\Tree\\TreeListener',
            'stof_doctrine_extensions.listener.loggable.class' => 'Gedmo\\Loggable\\LoggableListener',
            'stof_doctrine_extensions.listener.sortable.class' => 'Gedmo\\Sortable\\SortableListener',
            'stof_doctrine_extensions.listener.softdeleteable.class' => 'Gedmo\\SoftDeleteable\\SoftDeleteableListener',
            'stof_doctrine_extensions.listener.uploadable.class' => 'Gedmo\\Uploadable\\UploadableListener',
            'stof_doctrine_extensions.listener.reference_integrity.class' => 'Gedmo\\ReferenceIntegrity\\ReferenceIntegrityListener',
            'hateoas.link_factory.class' => 'Hateoas\\Factory\\LinkFactory',
            'hateoas.links_factory.class' => 'Hateoas\\Factory\\LinksFactory',
            'hateoas.embeds_factory.class' => 'Hateoas\\Factory\\EmbeddedsFactory',
            'hateoas.expression.evaluator.class' => 'Hateoas\\Expression\\ExpressionEvaluator',
            'bazinga_hateoas.expression_language.class' => 'Bazinga\\Bundle\\HateoasBundle\\ExpressionLanguage\\ExpressionLanguage',
            'hateoas.serializer.xml.class' => 'Hateoas\\Serializer\\XmlSerializer',
            'hateoas.serializer.json_hal.class' => 'Hateoas\\Serializer\\JsonHalSerializer',
            'hateoas.serializer.exclusion_manager.class' => 'Hateoas\\Serializer\\ExclusionManager',
            'hateoas.event_subscriber.xml.class' => 'Hateoas\\Serializer\\EventSubscriber\\XmlEventSubscriber',
            'hateoas.event_subscriber.json.class' => 'Hateoas\\Serializer\\EventSubscriber\\JsonEventSubscriber',
            'hateoas.inline_deferrer.embeds.class' => 'Hateoas\\Serializer\\Metadata\\InlineDeferrer',
            'hateoas.inline_deferrer.links.class' => 'Hateoas\\Serializer\\Metadata\\InlineDeferrer',
            'hateoas.configuration.provider.resolver.chain.class' => 'Hateoas\\Configuration\\Provider\\Resolver\\ChainResolver',
            'hateoas.configuration.provider.resolver.method.class' => 'Hateoas\\Configuration\\Provider\\Resolver\\MethodResolver',
            'hateoas.configuration.provider.resolver.static_method.class' => 'Hateoas\\Configuration\\Provider\\Resolver\\StaticMethodResolver',
            'hateoas.configuration.provider.resolver.symfony_container.class' => 'Hateoas\\Configuration\\Provider\\Resolver\\SymfonyContainerResolver',
            'hateoas.configuration.relation_provider.class' => 'Hateoas\\Configuration\\Provider\\RelationProvider',
            'hateoas.configuration.relations_repository.class' => 'Hateoas\\Configuration\\RelationsRepository',
            'hateoas.configuration.metadata.yaml_driver.class' => 'Hateoas\\Configuration\\Metadata\\Driver\\YamlDriver',
            'hateoas.configuration.metadata.xml_driver.class' => 'Hateoas\\Configuration\\Metadata\\Driver\\XmlDriver',
            'hateoas.configuration.metadata.annotation_driver.class' => 'Hateoas\\Configuration\\Metadata\\Driver\\AnnotationDriver',
            'hateoas.generator.registry.class' => 'Hateoas\\UrlGenerator\\UrlGeneratorRegistry',
            'hateoas.generator.symfony.class' => 'Hateoas\\UrlGenerator\\SymfonyUrlGenerator',
            'liip_theme.themes' => array(

            ),
            'liip_theme.active_theme' => NULL,
            'liip_theme.path_patterns' => array(
                'app_resource' => array(

                ),
                'bundle_resource' => array(

                ),
                'bundle_resource_dir' => array(

                ),
            ),
            'liip_theme.cache_warming' => true,
            'liip_theme.cookie' => NULL,
            'liip_theme.theme_controller.class' => 'Liip\\ThemeBundle\\Controller\\ThemeController',
            'liip_theme.templating_locator.class' => 'Liip\\ThemeBundle\\Locator\\TemplateLocator',
            'liip_theme.file_locator.class' => 'Liip\\ThemeBundle\\Locator\\FileLocator',
            'liip_theme.active_theme.class' => 'Liip\\ThemeBundle\\ActiveTheme',
            'liip_theme.cache_warmer.class' => 'Liip\\ThemeBundle\\CacheWarmer\\TemplatePathsCacheWarmer',
            'sulu.phpcr.session.class' => 'Sulu\\Component\\PHPCR\\SessionManager\\SessionManager',
            'sulu.content.template.default' => 'default',
            'sulu.content.internal_prefix' => '',
            'sulu.content.language.namespace' => 'i18n',
            'sulu.content.language.default' => 'en',
            'sulu.content.node_names.base' => 'cmf',
            'sulu.content.node_names.content' => 'contents',
            'sulu.content.node_names.route' => 'routes',
            'sulu.content.type.text_line.template' => 'SuluContentBundle:Template:content-types/text_line.html.twig',
            'sulu.content.type.text_area.template' => 'SuluContentBundle:Template:content-types/text_area.html.twig',
            'sulu.content.type.text_editor.template' => 'SuluContentBundle:Template:content-types/text_editor.html.twig',
            'sulu.content.type.resource_locator.template' => 'SuluContentBundle:Template:content-types/resource_locator.html.twig',
            'sulu.content.type.block.template' => 'SuluContentBundle:Template:content-types/block.html.twig',
            'sulu.content.template.paths' => array(

            ),
            'sulu.content.nav_contexts' => array(
                0 => 'main',
                1 => 'footer',
            ),
            'sulu.content.path_cleaner.class' => 'Sulu\\Component\\PHPCR\\PathCleanup',
            'sulu.content.mapper.class' => 'Sulu\\Component\\Content\\Mapper\\ContentMapper',
            'sulu.content.structure_manager.class' => 'Sulu\\Component\\Content\\StructureManager',
            'sulu.content.structure_manager.loader.class' => 'Sulu\\Component\\Content\\Template\\TemplateReader',
            'sulu.content.structure_manager.dumper.class' => 'Sulu\\Component\\Content\\Template\\Dumper\\PHPTemplateDumper',
            'sulu.content.structure_manager.dumper.path' => '../Resources/Skeleton',
            'sulu.content.type_manager.class' => 'Sulu\\Component\\Content\\ContentTypeManager',
            'sulu.content.type.text_line.class' => 'Sulu\\Component\\Content\\Types\\TextLine',
            'sulu.content.type.text_area.class' => 'Sulu\\Component\\Content\\Types\\TextArea',
            'sulu.content.type.text_editor.class' => 'Sulu\\Component\\Content\\Types\\TextEditor',
            'sulu.content.type.resource_locator.class' => 'Sulu\\Component\\Content\\Types\\ResourceLocator',
            'sulu.content.type.block.class' => 'Sulu\\Component\\Content\\Block\\BlockContentType',
            'sulu.content.rlp.mapper.phpcr.class' => 'Sulu\\Component\\Content\\Types\\Rlp\\Mapper\\PhpcrMapper',
            'sulu.content.rlp.strategy.tree.class' => 'Sulu\\Component\\Content\\Types\\Rlp\\Strategy\\TreeStrategy',
            'sulu.content.parent_child_any_finder.class' => 'Sulu\\Component\\Content\\Mapper\\LocalizationFinder\\ParentChildAnyFinder',
            'sulu_core.webspace.config_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/Resources/webspaces',
            'sulu_core.webspace.request_analyzer.enabled' => true,
            'sulu_core.webspace.request_analyzer.priority' => 300,
            'sulu_core.webspace.webspace_manager.class' => 'Sulu\\Component\\Webspace\\Manager\\WebspaceManager',
            'sulu_core.webspace.loader.xml_class' => 'Sulu\\Component\\Webspace\\Loader\\XmlFileLoader',
            'sulu_core.webspace.cache_class' => 'WebspaceCollectionCache',
            'sulu_core.webspace.base_class' => 'WebspaceCollection',
            'sulu.fields_defaults.translations' => array(
                'id' => 'public.id',
                'title' => 'public.title',
                'name' => 'public.name',
                'created' => 'public.created',
                'changed' => 'public.changed',
            ),
            'sulu.fields_defaults.widths' => array(
                'id' => '50px',
            ),
            'sulu_core.rest_helper.class' => 'Sulu\\Component\\Rest\\RestHelper',
            'sulu_core.doctrine_rest_helper.class' => 'Sulu\\Component\\Rest\\DoctrineRestHelper',
            'sulu_core.list_rest_helper.class' => 'Sulu\\Component\\Rest\\ListBuilder\\ListRestHelper',
            'sulu_core.doctrine_list_builder_factory.class' => 'Sulu\\Component\\Rest\\ListBuilder\\Doctrine\\DoctrineListBuilderFactory',
            'sulu_admin.name' => 'SULU 2.0',
            'sulu_admin.user_data_service' => 'sulu_security.user_manager',
            'sulu_admin.admin_pool.class' => 'Sulu\\Bundle\\AdminBundle\\Admin\\AdminPool',
            'sulu_admin.js_config_pool.class' => 'Sulu\\Bundle\\AdminBundle\\Admin\\JsConfigPool',
            'sulu_admin.widgets_handler.class' => 'Sulu\\Bundle\\AdminBundle\\Widgets\\WidgetsHandler',
            'sulu_security.system' => 'Sulu',
            'sulu_security.security_types.fixture' => 'vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/DataFixtures/security-types.xml',
            'permissions' => array(
                'view' => 64,
                'add' => 32,
                'edit' => 16,
                'delete' => 8,
                'archive' => 4,
                'live' => 2,
                'security' => 1,
            ),
            'sulu_security.admin.class' => 'Sulu\\Bundle\\SecurityBundle\\Admin\\SuluSecurityAdmin',
            'sulu_security.mask_converter.class' => 'Sulu\\Bundle\\SecurityBundle\\Permission\\MaskConverter',
            'sulu_security.salt_generator.class' => 'Sulu\\Bundle\\SecurityBundle\\Permission\\SaltGenerator',
            'sulu_security.admin.content_navigation.class' => 'Sulu\\Bundle\\SecurityBundle\\Admin\\SuluSecurityContentNavigation',
            'sulu_security.admin.roles_navigation.class' => 'Sulu\\Bundle\\SecurityBundle\\Admin\\SuluSecurityRolesContentNavigation',
            'sulu_security.permission_voter.class' => 'Sulu\\Bundle\\SecurityBundle\\Permission\\PermissionVoter',
            'sulu_security.user_repository.class' => 'Sulu\\Bundle\\SecurityBundle\\Entity\\UserRepository',
            'sulu_security.user_repository_factory.class' => 'Sulu\\Bundle\\SecurityBundle\\Factory\\UserRepositoryFactory',
            'sulu_contact.admin.class' => 'Sulu\\Bundle\\ContactBundle\\Admin\\SuluContactAdmin',
            'sulu_contact.admin.content_navigation.class' => 'Sulu\\Bundle\\ContactBundle\\Admin\\SuluContactContentNavigation',
            'sulu_contact.js_config.class' => 'Sulu\\Bundle\\AdminBundle\\Admin\\JsConfig',
            'sulu_contact.import.class' => 'Sulu\\Bundle\\ContactBundle\\Import\\Import',
            'sulu_contact.account_listener.class' => 'Sulu\\Bundle\\ContactBundle\\EventListener\\AccountListener',
            'sulu_contact.contact_manager.class' => 'Sulu\\Bundle\\ContactBundle\\Contact\\ContactManager',
            'sulu_contact.account_manager.class' => 'Sulu\\Bundle\\ContactBundle\\Contact\\AccountManager',
            'sulu_contact.twig.class' => 'Sulu\\Bundle\\ContactBundle\\Twig\\ContactTwigExtension',
            'sulu_contact.twig.cache.class' => 'Doctrine\\Common\\Cache\\ArrayCache',
            'sulu_contact.user_repository.class' => 'Sulu\\Bundle\\SecurityBundle\\Entity\\UserRepository',
            'sulu_contact.user_repository.entity' => 'SuluSecurityBundle:User',
            'sulu_contact.contact.widgets.account_info.class' => 'Sulu\\Bundle\\ContactBundle\\Widgets\\AccountInfo',
            'sulu_contact.contact.widgets.account_main_contact.class' => 'Sulu\\Bundle\\ContactBundle\\Widgets\\MainContact',
            'sulu_contact.contact.widgets.contact_info.class' => 'Sulu\\Bundle\\ContactBundle\\Widgets\\ContactInfo',
            'sulu_contact.contact.widgets.contact_main_account.class' => 'Sulu\\Bundle\\ContactBundle\\Widgets\\MainAccount',
            'sulu_contact.contact.widgets.table.class' => 'Sulu\\Bundle\\ContactBundle\\Widgets\\Table',
            'sulu_contact.contact.widgets.toolbar.class' => 'Sulu\\Bundle\\ContactBundle\\Widgets\\Toolbar',
            'sulu_contact.defaults' => array(
                'phoneType' => '1',
                'phoneTypeMobile' => '3',
                'phoneTypeIsdn' => '4',
                'emailType' => '1',
                'addressType' => '1',
                'urlType' => '1',
                'faxType' => '1',
                'country' => '15',
            ),
            'sulu_contact.account_types' => array(

            ),
            'sulu_contact.form_of_address' => array(

            ),
            'sulu.test_user_provider.class' => 'Sulu\\Bundle\\TestBundle\\Testing\\TestUserProvider',
            'sulu.test_voter.class' => 'Sulu\\Bundle\\TestBundle\\Testing\\TestVoter',
            'sulu_test.test_user_repository.class' => 'Sulu\\Bundle\\TestBundle\\Entity\\TestUserRepository',
            'sulu_tag.content.type.tag_list.template' => 'SuluTagBundle:Template:content-types/tag_list.html.twig',
            'sulu_tag.admin.class' => 'Sulu\\Bundle\\TagBundle\\Admin\\SuluTagAdmin',
            'sulu_tag.tag_manager.class' => 'Sulu\\Bundle\\TagBundle\\Tag\\TagManager',
            'sulu_tag.tag_repository.class' => 'Sulu\\Bundle\\TagBundle\\Entity\\TagRepository',
            'sulu_tag.content.type.tag_list.class' => 'Sulu\\Bundle\\TagBundle\\Content\\Types\\TagList',
            'sulu_media.collection.type.default' => array(
                'id' => 1,
            ),
            'sulu_media.collection.previews.limit' => 3,
            'sulu_media.collection.previews.format' => '150x100',
            'sulu_media.media.max_file_size' => '16MB',
            'sulu_media.media.blocked_file_types' => array(
                0 => 'file/exe',
            ),
            'sulu_media.media.storage.local.path' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/../uploads/media',
            'sulu_media.media.storage.local.segments' => '10',
            'sulu_media.image.command.prefix' => 'image.converter.prefix.',
            'sulu_media.format_cache.save_image' => 'true',
            'sulu_media.format_cache.path' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/../web/uploads/media',
            'sulu_media.format_cache.segments' => '10',
            'ghost_script.path' => '/usr/local/bin/gs',
            'sulu_media.format_manager.extensions' => array(
                0 => 'jpeg',
                1 => 'jpg',
                2 => 'gif',
                3 => 'png',
                4 => 'bmp',
                5 => 'svg',
                6 => 'psd',
                7 => 'pdf',
            ),
            'sulu_media.image.formats' => array(
                0 => array(
                    'name' => '170x170',
                    'commands' => array(
                        0 => array(
                            'action' => 'scale',
                            'parameters' => array(
                                'x' => '170',
                                'y' => '170',
                            ),
                        ),
                    ),
                ),
                1 => array(
                    'name' => '50x50',
                    'commands' => array(
                        0 => array(
                            'action' => 'scale',
                            'parameters' => array(
                                'x' => '50',
                                'y' => '50',
                            ),
                        ),
                    ),
                ),
                2 => array(
                    'name' => '150x100',
                    'commands' => array(
                        0 => array(
                            'action' => 'scale',
                            'parameters' => array(
                                'x' => '150',
                                'y' => '100',
                            ),
                        ),
                    ),
                ),
            ),
            'sulu_media.media.types' => array(
                0 => array(
                    'id' => 1,
                    'type' => 'default',
                    'mimeTypes' => array(
                        0 => '*',
                    ),
                ),
                1 => array(
                    'id' => 2,
                    'type' => 'image',
                    'mimeTypes' => array(
                        0 => 'image/jpg',
                        1 => 'image/jpeg',
                        2 => 'image/png',
                        3 => 'image/gif',
                        4 => 'image/svg+xml',
                        5 => 'image/vnd.adobe.photoshop',
                    ),
                ),
                2 => array(
                    'id' => 3,
                    'type' => 'video',
                    'mimeTypes' => array(
                        0 => 'video/mp4',
                    ),
                ),
                3 => array(
                    'id' => 4,
                    'type' => 'audio',
                    'mimeTypes' => array(
                        0 => 'audio/mpeg',
                    ),
                ),
            ),
            'sulu_media.admin.class' => 'Sulu\\Bundle\\MediaBundle\\Admin\\SuluMediaAdmin',
            'sulu_media.admin.content_navigation.class' => 'Sulu\\Bundle\\MediaBundle\\Admin\\SuluMediaContentNavigation',
            'sulu_media.media_manager.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\Manager\\DefaultMediaManager',
            'sulu_media.media_repository.class' => 'Sulu\\Bundle\\MediaBundle\\Entity\\MediaRepository',
            'sulu_media.collection_repository.class' => 'Sulu\\Bundle\\MediaBundle\\Entity\\CollectionRepository',
            'sulu_media.storage.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\Storage\\LocalStorage',
            'sulu_media.file_validator.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\FileValidator\\DefaultFileValidator',
            'sulu_media.format_manager.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\FormatManager\\DefaultFormatManager',
            'sulu_media.format_cache.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\FormatCache\\LocalFormatCache',
            'sulu_media.image.converter.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\ImageConverter\\ImagineImageConverter',
            'sulu_media.image.command_manager.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\ImageConverter\\Command\\Manager\\DefaultCommandManager',
            'sulu_media.image.command.resize.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\ImageConverter\\Command\\ResizeCommand',
            'sulu_media.image.command.scale.class' => 'Sulu\\Bundle\\MediaBundle\\Media\\ImageConverter\\Command\\ScaleCommand',
            'sulu_media.media_selection.class' => 'Sulu\\Bundle\\MediaBundle\\Content\\Types\\MediaSelectionContentType',
            'sulu_media.collection_manager.class' => 'Sulu\\Bundle\\MediaBundle\\Collection\\Manager\\DefaultCollectionManager',
            'sulu_category.content.type.category_list.template' => 'SuluCategoryBundle:Template:content-types/category_list.html.twig',
            'sulu_category.admin.class' => 'Sulu\\Bundle\\CategoryBundle\\Admin\\SuluCategoryAdmin',
            'sulu_category.category_manager.class' => 'Sulu\\Bundle\\CategoryBundle\\Category\\CategoryManager',
            'sulu_category.category_repository.class' => 'Sulu\\Bundle\\CategoryBundle\\Entity\\CategoryRepository',
            'sulu_category.admin.content_navigation.class' => 'Sulu\\Bundle\\CategoryBundle\\Admin\\SuluCategoryContentNavigation',
            'sulu_category.content.type.category_list.class' => 'Sulu\\Bundle\\CategoryBundle\\Content\\Types\\CategoryList',
            'doctrine_phpcr.dump_max_line_length' => 120,
            'doctrine_phpcr.credentials.class' => 'PHPCR\\SimpleCredentials',
            'doctrine_phpcr.class' => 'Doctrine\\Bundle\\PHPCRBundle\\ManagerRegistry',
            'doctrine_phpcr.proxy.class' => 'Doctrine\\Common\\Proxy\\Proxy',
            'doctrine_phpcr.sessions' => array(
                'default' => 'doctrine_phpcr.default_session',
            ),
            'doctrine_phpcr.odm.document_managers' => array(
                'default' => 'doctrine_phpcr.odm.default_document_manager',
            ),
            'doctrine_phpcr.default_session' => 'default',
            'doctrine_phpcr.odm.default_document_manager' => 'default',
            'doctrine_phpcr.console_dumper.class' => 'PHPCR\\Util\\Console\\Helper\\PhpcrConsoleDumperHelper',
            'doctrine_phpcr.initializer_manager.class' => 'Doctrine\\Bundle\\PHPCRBundle\\Initializer\\InitializerManager',
            'doctrine_phpcr.form.type.phpcr_reference.class' => 'Doctrine\\Bundle\\PHPCRBundle\\Form\\Type\\PHPCRReferenceType',
            'doctrine_phpcr.logger.chain.class' => 'Jackalope\\Transport\\Logging\\LoggerChain',
            'doctrine_phpcr.logger.class' => 'Jackalope\\Transport\\Logging\\Psr3Logger',
            'doctrine_phpcr.logger.profiling.class' => 'Jackalope\\Transport\\Logging\\DebugStack',
            'doctrine_phpcr.logger.stop_watch.class' => 'Doctrine\\Bundle\\PHPCRBundle\\DataCollector\\StopWatchLogger',
            'doctrine_phpcr.data_collector.class' => 'Doctrine\\Bundle\\PHPCRBundle\\DataCollector\\PHPCRDataCollector',
            'doctrine_phpcr.session.event_manager.class' => 'Symfony\\Bridge\\Doctrine\\ContainerAwareEventManager',
            'doctrine_phpcr.jackalope_doctrine_dbal.schema_listener.class' => 'Doctrine\\Bundle\\PHPCRBundle\\EventListener\\JackalopeDoctrineDbalSchemaListener',
            'doctrine_phpcr.jackalope_doctrine_dbal.repository_schema.class' => 'Jackalope\\Transport\\DoctrineDBAL\\RepositorySchema',
            'doctrine_phpcr.odm.configuration.class' => 'Doctrine\\ODM\\PHPCR\\Configuration',
            'doctrine_phpcr.odm.document_manager.class' => 'Doctrine\\ODM\\PHPCR\\DocumentManager',
            'doctrine_phpcr.odm.cache.array.class' => 'Doctrine\\Common\\Cache\\ArrayCache',
            'doctrine_phpcr.odm.cache.apc.class' => 'Doctrine\\Common\\Cache\\ApcCache',
            'doctrine_phpcr.odm.cache.memcache.class' => 'Doctrine\\Common\\Cache\\MemcacheCache',
            'doctrine_phpcr.odm.cache.memcache_host' => 'localhost',
            'doctrine_phpcr.odm.cache.memcache_port' => 11211,
            'doctrine_phpcr.odm.cache.memcache_instance.class' => 'Memcache',
            'doctrine_phpcr.odm.cache.xcache.class' => 'Doctrine\\Common\\Cache\\XcacheCache',
            'form.type_guesser.doctrine_phpcr.class' => 'Doctrine\\Bundle\\PHPCRBundle\\Form\\PHPCRTypeGuesser',
            'doctrine_phpcr.odm.form.path.type.class' => 'Doctrine\\Bundle\\PHPCRBundle\\Form\\Type\\PathType',
            'doctrine_phpcr.odm.metadata.driver_chain.class' => 'Doctrine\\Common\\Persistence\\Mapping\\Driver\\MappingDriverChain',
            'doctrine_phpcr.odm.metadata.annotation.class' => 'Doctrine\\ODM\\PHPCR\\Mapping\\Driver\\AnnotationDriver',
            'doctrine_phpcr.odm.metadata.xml.class' => 'Doctrine\\Bundle\\PHPCRBundle\\Mapping\\Driver\\XmlDriver',
            'doctrine_phpcr.odm.metadata.yml.class' => 'Doctrine\\Bundle\\PHPCRBundle\\Mapping\\Driver\\YamlDriver',
            'doctrine_phpcr.odm.metadata.php.class' => 'Doctrine\\Common\\Persistence\\Mapping\\Driver\\StaticPHPDriver',
            'doctrine_phpcr.odm.proxy_cache_warmer.class' => 'Symfony\\Bridge\\Doctrine\\CacheWarmer\\ProxyCacheWarmer',
            'doctrine_phpcr.odm.validator.valid_phpcr_odm.class' => 'Doctrine\\Bundle\\PHPCRBundle\\Validator\\Constraints\\ValidPhpcrOdmValidator',
            'doctrine_phpcr.odm.auto_generate_proxy_classes' => false,
            'doctrine_phpcr.odm.proxy_dir' => '/Users/daniel/Development/massiveart/sulu-complete/vendor/sulu/security-bundle/Sulu/Bundle/SecurityBundle/Tests/Resources/app/cache/test/doctrine/PHPCRProxies',
            'doctrine_phpcr.odm.proxy_namespace' => 'PHPCRProxies',
            'doctrine_phpcr.form.type_guess' => array(

            ),
            'console.command.ids' => array(

            ),
            'sulu.version' => '_._._',
            'doctrine_phpcr.migrate.migrators' => array(

            ),
        );
    }
}
