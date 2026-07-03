<?php

// This file is auto-generated and is for apps only. Bundles SHOULD NOT rely on its content.

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Config\Loader\ParamConfigurator as Param;

/**
 * This class provides array-shapes for configuring the services and bundles of an application.
 *
 * Services declared with the config() method below are autowired and autoconfigured by default.
 *
 * This is for apps only. Bundles SHOULD NOT use it.
 *
 * Example:
 *
 *     ```php
 *     // config/services.php
 *     namespace Symfony\Component\DependencyInjection\Loader\Configurator;
 *
 *     return App::config([
 *         'services' => [
 *             'App\\' => [
 *                 'resource' => '../src/',
 *             ],
 *         ],
 *     ]);
 *     ```
 *
 * @psalm-type ImportsConfig = list<string|array{
 *     resource: string,
 *     type?: string|null,
 *     ignore_errors?: bool,
 * }>
 * @psalm-type ParametersConfig = array<string, scalar|\UnitEnum|array<scalar|\UnitEnum|array<mixed>|Param|null>|Param|null>
 * @psalm-type ArgumentsType = list<mixed>|array<string, mixed>
 * @psalm-type CallType = array<string, ArgumentsType>|array{0:string, 1?:ArgumentsType, 2?:bool}|array{method:string, arguments?:ArgumentsType, returns_clone?:bool}
 * @psalm-type TagsType = list<string|array<string, array<string, mixed>>> // arrays inside the list must have only one element, with the tag name as the key
 * @psalm-type CallbackType = string|array{0:string|ReferenceConfigurator,1:string}|\Closure|ReferenceConfigurator|ExpressionConfigurator
 * @psalm-type DeprecationType = array{package: string, version: string, message?: string}
 * @psalm-type DefaultsType = array{
 *     public?: bool,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     autowire?: bool,
 *     autoconfigure?: bool,
 *     bind?: array<string, mixed>,
 * }
 * @psalm-type InstanceofType = array{
 *     shared?: bool,
 *     lazy?: bool|string,
 *     public?: bool,
 *     properties?: array<string, mixed>,
 *     configurator?: CallbackType,
 *     calls?: list<CallType>,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     autowire?: bool,
 *     bind?: array<string, mixed>,
 *     constructor?: string,
 * }
 * @psalm-type DefinitionType = array{
 *     class?: string,
 *     file?: string,
 *     parent?: string,
 *     shared?: bool,
 *     synthetic?: bool,
 *     lazy?: bool|string,
 *     public?: bool,
 *     abstract?: bool,
 *     deprecated?: DeprecationType,
 *     factory?: CallbackType,
 *     configurator?: CallbackType,
 *     arguments?: ArgumentsType,
 *     properties?: array<string, mixed>,
 *     calls?: list<CallType>,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     decorates?: string,
 *     decorates_tag?: string,
 *     decoration_inner_name?: string,
 *     decoration_priority?: int,
 *     decoration_on_invalid?: 'exception'|'ignore'|null,
 *     autowire?: bool,
 *     autoconfigure?: bool,
 *     bind?: array<string, mixed>,
 *     constructor?: string,
 *     from_callable?: CallbackType,
 * }
 * @psalm-type AliasType = string|array{
 *     alias: string,
 *     public?: bool,
 *     deprecated?: DeprecationType,
 * }
 * @psalm-type PrototypeType = array{
 *     resource: string,
 *     namespace?: string,
 *     exclude?: string|list<string>,
 *     parent?: string,
 *     shared?: bool,
 *     lazy?: bool|string,
 *     public?: bool,
 *     abstract?: bool,
 *     deprecated?: DeprecationType,
 *     factory?: CallbackType,
 *     arguments?: ArgumentsType,
 *     properties?: array<string, mixed>,
 *     configurator?: CallbackType,
 *     calls?: list<CallType>,
 *     tags?: TagsType,
 *     resource_tags?: TagsType,
 *     autowire?: bool,
 *     autoconfigure?: bool,
 *     bind?: array<string, mixed>,
 *     constructor?: string,
 * }
 * @psalm-type StackType = array{
 *     stack: list<DefinitionType|AliasType|PrototypeType|array<class-string, ArgumentsType|null>>,
 *     public?: bool,
 *     deprecated?: DeprecationType,
 *     decorates?: string,
 *     decorates_tag?: string,
 *     decoration_inner_name?: string,
 *     decoration_priority?: int,
 *     decoration_on_invalid?: 'exception'|'ignore'|null,
 * }
 * @psalm-type ServicesConfig = array{
 *     _defaults?: DefaultsType,
 *     _instanceof?: array<class-string, InstanceofType>,
 *     ...<string, DefinitionType|AliasType|PrototypeType|StackType|ArgumentsType|null>
 * }
 * @psalm-type ExtensionType = array<string, mixed>
 * @psalm-type FrameworkConfig = array{
 *     secret?: scalar|Param|null,
 *     http_method_override?: bool|Param, // Set true to enable support for the '_method' request parameter to determine the intended HTTP method on POST requests. // Default: false
 *     allowed_http_method_override?: null|list<string|Param>,
 *     trust_x_sendfile_type_header?: scalar|Param|null, // Set true to enable support for xsendfile in binary file responses. // Default: "%env(bool:default::SYMFONY_TRUST_X_SENDFILE_TYPE_HEADER)%"
 *     ide?: scalar|Param|null, // Default: "%env(default::SYMFONY_IDE)%"
 *     test?: bool|Param,
 *     default_locale?: scalar|Param|null, // Default: "en"
 *     set_locale_from_accept_language?: bool|Param, // Whether to use the Accept-Language HTTP header to set the Request locale (only when the "_locale" request attribute is not passed). // Default: false
 *     set_content_language_from_locale?: bool|Param, // Whether to set the Content-Language HTTP header on the Response using the Request locale. // Default: false
 *     enabled_locales?: list<scalar|Param|null>,
 *     trusted_hosts?: string|list<scalar|Param|null>,
 *     trusted_proxies?: mixed, // Default: ["%env(default::SYMFONY_TRUSTED_PROXIES)%"]
 *     trusted_headers?: string|list<scalar|Param|null>,
 *     error_controller?: scalar|Param|null, // Default: "error_controller"
 *     handle_all_throwables?: bool|Param, // HttpKernel will handle all kinds of \Throwable. // Default: true
 *     csrf_protection?: bool|array{
 *         enabled?: scalar|Param|null, // Default: null
 *         stateless_token_ids?: list<scalar|Param|null>,
 *         check_header?: scalar|Param|null, // Whether to check the CSRF token in a header in addition to a cookie when using stateless protection. // Default: false
 *         cookie_name?: scalar|Param|null, // The name of the cookie to use when using stateless protection. // Default: "csrf-token"
 *     },
 *     form?: bool|array{ // Form configuration
 *         enabled?: bool|Param, // Default: false
 *         csrf_protection?: bool|array{
 *             enabled?: scalar|Param|null, // Default: null
 *             token_id?: scalar|Param|null, // Default: null
 *             field_name?: scalar|Param|null, // Default: "_token"
 *             field_attr?: array<string, scalar|Param|null>,
 *         },
 *     },
 *     http_cache?: bool|array{ // HTTP cache configuration
 *         enabled?: bool|Param, // Default: false
 *         debug?: bool|Param, // Default: "%kernel.debug%"
 *         trace_level?: "none"|"short"|"full"|Param,
 *         trace_header?: scalar|Param|null,
 *         default_ttl?: int|Param,
 *         private_headers?: list<scalar|Param|null>,
 *         skip_response_headers?: list<scalar|Param|null>,
 *         allow_reload?: bool|Param,
 *         allow_revalidate?: bool|Param,
 *         stale_while_revalidate?: int|Param,
 *         stale_if_error?: int|Param,
 *         terminate_on_cache_hit?: bool|Param, // Deprecated: Setting the "framework.http_cache.terminate_on_cache_hit.terminate_on_cache_hit" configuration option is deprecated. It will be removed in version 9.0.
 *     },
 *     esi?: bool|array{ // ESI configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 *     ssi?: bool|array{ // SSI configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 *     fragments?: bool|array{ // Fragments configuration
 *         enabled?: bool|Param, // Default: false
 *         hinclude_default_template?: scalar|Param|null, // Default: null
 *         path?: scalar|Param|null, // Default: "/_fragment"
 *     },
 *     profiler?: bool|array{ // Profiler configuration
 *         enabled?: bool|Param, // Default: false
 *         collect?: bool|Param, // Default: true
 *         collect_parameter?: scalar|Param|null, // The name of the parameter to use to enable or disable collection on a per request basis. // Default: null
 *         only_exceptions?: bool|Param, // Default: false
 *         only_main_requests?: bool|Param, // Default: false
 *         dsn?: scalar|Param|null, // Default: "file:%kernel.cache_dir%/profiler"
 *         collect_serializer_data?: true|Param, // Deprecated: Setting the "framework.profiler.collect_serializer_data.collect_serializer_data" configuration option is deprecated. It will be removed in version 9.0. // Default: true
 *     },
 *     workflows?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         workflows?: array<string, array{ // Default: []
 *             audit_trail?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *             },
 *             type?: "workflow"|"state_machine"|Param, // Default: "state_machine"
 *             marking_store?: array{
 *                 type?: "method"|Param,
 *                 property?: scalar|Param|null,
 *                 service?: scalar|Param|null,
 *             },
 *             supports?: string|list<scalar|Param|null>,
 *             definition_validators?: list<scalar|Param|null>,
 *             support_strategy?: scalar|Param|null,
 *             initial_marking?: \BackedEnum|string|list<scalar|Param|null>,
 *             events_to_dispatch?: null|list<string|Param>,
 *             places?: string|list<array{ // Default: []
 *                 name?: scalar|Param|null,
 *                 metadata?: array<string, mixed>,
 *             }>,
 *             transitions?: list<array{ // Default: []
 *                 name?: string|Param,
 *                 guard?: string|Param, // An expression to block the transition.
 *                 from?: \BackedEnum|string|list<array{ // Default: []
 *                     place?: string|Param,
 *                     weight?: int|Param, // Default: 1
 *                 }>,
 *                 to?: \BackedEnum|string|list<array{ // Default: []
 *                     place?: string|Param,
 *                     weight?: int|Param, // Default: 1
 *                 }>,
 *                 weight?: int|Param, // Default: 1
 *                 metadata?: array<string, mixed>,
 *             }>,
 *             metadata?: array<string, mixed>,
 *         }>,
 *     },
 *     router?: bool|array{ // Router configuration
 *         enabled?: bool|Param, // Default: false
 *         resource?: scalar|Param|null,
 *         type?: scalar|Param|null,
 *         default_uri?: scalar|Param|null, // The default URI used to generate URLs in a non-HTTP context. // Default: null
 *         http_port?: scalar|Param|null, // Default: 80
 *         https_port?: scalar|Param|null, // Default: 443
 *         strict_requirements?: scalar|Param|null, // set to true to throw an exception when a parameter does not match the requirements set to false to disable exceptions when a parameter does not match the requirements (and return null instead) set to null to disable parameter checks against requirements 'true' is the preferred configuration in development mode, while 'false' or 'null' might be preferred in production // Default: true
 *         utf8?: bool|Param, // Default: true
 *     },
 *     session?: bool|array{ // Session configuration
 *         enabled?: bool|Param, // Default: false
 *         storage_factory_id?: scalar|Param|null, // Default: "session.storage.factory.native"
 *         handler_id?: scalar|Param|null, // Defaults to using the native session handler, or to the native *file* session handler if "save_path" is not null.
 *         name?: scalar|Param|null,
 *         cookie_lifetime?: scalar|Param|null,
 *         cookie_path?: scalar|Param|null,
 *         cookie_domain?: scalar|Param|null,
 *         cookie_secure?: true|false|"auto"|Param, // Default: "auto"
 *         cookie_httponly?: bool|Param, // Default: true
 *         cookie_samesite?: null|"lax"|"strict"|"none"|Param, // Default: "lax"
 *         use_cookies?: bool|Param,
 *         gc_divisor?: scalar|Param|null,
 *         gc_probability?: scalar|Param|null,
 *         gc_maxlifetime?: scalar|Param|null,
 *         save_path?: scalar|Param|null, // Defaults to "%kernel.cache_dir%/sessions" if the "handler_id" option is not null.
 *         metadata_update_threshold?: int|Param, // Seconds to wait between 2 session metadata updates. // Default: 0
 *     },
 *     request?: bool|array{ // Request configuration
 *         enabled?: bool|Param, // Default: false
 *         formats?: array<string, string|list<scalar|Param|null>>,
 *     },
 *     assets?: bool|array{ // Assets configuration
 *         enabled?: bool|Param, // Default: true
 *         strict_mode?: bool|Param, // Throw an exception if an entry is missing from the manifest.json. // Default: false
 *         version_strategy?: scalar|Param|null, // Default: null
 *         version?: scalar|Param|null, // Default: null
 *         version_format?: scalar|Param|null, // Default: "%%s?%%s"
 *         json_manifest_path?: scalar|Param|null, // Default: null
 *         base_path?: scalar|Param|null, // Default: ""
 *         base_urls?: string|list<scalar|Param|null>,
 *         packages?: array<string, array{ // Default: []
 *             strict_mode?: bool|Param, // Throw an exception if an entry is missing from the manifest.json. // Default: false
 *             version_strategy?: scalar|Param|null, // Default: null
 *             version?: scalar|Param|null,
 *             version_format?: scalar|Param|null, // Default: null
 *             json_manifest_path?: scalar|Param|null, // Default: null
 *             base_path?: scalar|Param|null, // Default: ""
 *             base_urls?: string|list<scalar|Param|null>,
 *         }>,
 *     },
 *     asset_mapper?: bool|array{ // Asset Mapper configuration
 *         enabled?: bool|Param, // Default: false
 *         paths?: string|array<string, scalar|Param|null>,
 *         excluded_patterns?: list<scalar|Param|null>,
 *         exclude_dotfiles?: bool|Param, // If true, any files starting with "." will be excluded from the asset mapper. // Default: true
 *         server?: bool|Param, // If true, a "dev server" will return the assets from the public directory (true in "debug" mode only by default). // Default: true
 *         public_prefix?: scalar|Param|null, // The public path where the assets will be written to (and served from when "server" is true). // Default: "/assets/"
 *         missing_import_mode?: "strict"|"warn"|"ignore"|Param, // Behavior if an asset cannot be found when imported from JavaScript or CSS files - e.g. "import './non-existent.js'". "strict" means an exception is thrown, "warn" means a warning is logged, "ignore" means the import is left as-is. // Default: "warn"
 *         extensions?: array<string, scalar|Param|null>,
 *         importmap_path?: scalar|Param|null, // The path of the importmap.php file. // Default: "%kernel.project_dir%/importmap.php"
 *         importmap_polyfill?: scalar|Param|null, // The importmap name that will be used to load the polyfill. Set to false to disable. // Default: "es-module-shims"
 *         importmap_script_attributes?: array<string, scalar|Param|null>,
 *         vendor_dir?: scalar|Param|null, // The directory to store JavaScript vendors. // Default: "%kernel.project_dir%/assets/vendor"
 *         precompress?: bool|array{ // Precompress assets with Brotli, Zstandard and gzip.
 *             enabled?: bool|Param, // Default: false
 *             formats?: list<scalar|Param|null>,
 *             extensions?: list<scalar|Param|null>,
 *         },
 *     },
 *     translator?: bool|array{ // Translator configuration
 *         enabled?: bool|Param, // Default: true
 *         fallbacks?: string|list<scalar|Param|null>,
 *         logging?: bool|Param, // Default: false
 *         formatter?: scalar|Param|null, // Default: "translator.formatter.default"
 *         cache_dir?: scalar|Param|null, // Default: "%kernel.cache_dir%/translations"
 *         default_path?: scalar|Param|null, // The default path used to load translations. // Default: "%kernel.project_dir%/translations"
 *         paths?: list<scalar|Param|null>,
 *         pseudo_localization?: bool|array{
 *             enabled?: bool|Param, // Default: false
 *             accents?: bool|Param, // Default: true
 *             expansion_factor?: float|Param, // Default: 1.0
 *             brackets?: bool|Param, // Default: true
 *             parse_html?: bool|Param, // Default: false
 *             localizable_html_attributes?: list<scalar|Param|null>,
 *         },
 *         providers?: array<string, array{ // Default: []
 *             dsn?: scalar|Param|null,
 *             domains?: list<scalar|Param|null>,
 *             locales?: list<scalar|Param|null>,
 *         }>,
 *         globals?: array<string, string|array{ // Default: []
 *             value?: mixed,
 *             message?: string|Param,
 *             parameters?: array<string, scalar|Param|null>,
 *             domain?: string|Param,
 *         }>,
 *     },
 *     validation?: bool|array{ // Validation configuration
 *         enabled?: bool|Param, // Default: true
 *         enable_attributes?: bool|Param, // Default: true
 *         static_method?: string|list<scalar|Param|null>,
 *         translation_domain?: scalar|Param|null, // Default: "validators"
 *         email_validation_mode?: "html5"|"html5-allow-no-tld"|"strict"|Param, // Default: "html5"
 *         mapping?: array{
 *             paths?: list<scalar|Param|null>,
 *         },
 *         not_compromised_password?: bool|array{
 *             enabled?: bool|Param, // When disabled, compromised passwords will be accepted as valid. // Default: true
 *             endpoint?: scalar|Param|null, // API endpoint for the NotCompromisedPassword Validator. // Default: null
 *         },
 *         disable_translation?: bool|Param, // Default: false
 *         property_metadata_existence_check?: bool|Param, // When enabled, validateProperty() and validatePropertyValue() throw an exception if no metadata is found for the given property. // Default: false
 *         auto_mapping?: array<string, array{ // Default: []
 *             services?: list<scalar|Param|null>,
 *         }>,
 *     },
 *     serializer?: bool|array{ // Serializer configuration
 *         enabled?: bool|Param, // Default: true
 *         enable_attributes?: bool|Param, // Default: true
 *         name_converter?: scalar|Param|null,
 *         circular_reference_handler?: scalar|Param|null,
 *         max_depth_handler?: scalar|Param|null,
 *         mapping?: array{
 *             paths?: list<scalar|Param|null>,
 *         },
 *         default_context?: array<string, mixed>,
 *         named_serializers?: array<string, array{ // Default: []
 *             name_converter?: scalar|Param|null,
 *             default_context?: array<string, mixed>,
 *             include_built_in_normalizers?: bool|Param, // Whether to include the built-in normalizers // Default: true
 *             include_built_in_encoders?: bool|Param, // Whether to include the built-in encoders // Default: true
 *         }>,
 *     },
 *     property_access?: bool|array{ // Property access configuration
 *         enabled?: bool|Param, // Default: true
 *         magic_call?: bool|Param, // Default: false
 *         magic_get?: bool|Param, // Default: true
 *         magic_set?: bool|Param, // Default: true
 *         throw_exception_on_invalid_index?: bool|Param, // Default: false
 *         throw_exception_on_invalid_property_path?: bool|Param, // Default: true
 *     },
 *     type_info?: bool|array{ // Type info configuration
 *         enabled?: bool|Param, // Default: true
 *         aliases?: array<string, scalar|Param|null>,
 *     },
 *     property_info?: bool|array{ // Property info configuration
 *         enabled?: bool|Param, // Default: true
 *         with_constructor_extractor?: bool|Param, // Registers the constructor extractor. // Default: true
 *     },
 *     cache?: array{ // Cache configuration
 *         prefix_seed?: scalar|Param|null, // Used to namespace cache keys when using several apps with the same shared backend. // Default: "_%kernel.project_dir%.%kernel.container_class%"
 *         app?: scalar|Param|null, // App related cache pools configuration. // Default: "cache.adapter.filesystem"
 *         system?: scalar|Param|null, // System related cache pools configuration. // Default: "cache.adapter.system"
 *         directory?: scalar|Param|null, // Default: "%kernel.share_dir%/pools/app"
 *         default_psr6_provider?: scalar|Param|null,
 *         default_redis_provider?: scalar|Param|null, // Default: "redis://localhost"
 *         default_valkey_provider?: scalar|Param|null, // Default: "valkey://localhost"
 *         default_memcached_provider?: scalar|Param|null, // Default: "memcached://localhost"
 *         default_doctrine_dbal_provider?: scalar|Param|null, // Default: "database_connection"
 *         default_pdo_provider?: scalar|Param|null, // Default: null
 *         pools?: array<string, array{ // Default: []
 *             adapters?: string|list<scalar|Param|null>,
 *             tags?: scalar|Param|null, // Default: null
 *             public?: bool|Param, // Default: false
 *             default_lifetime?: scalar|Param|null, // Default lifetime of the pool.
 *             provider?: scalar|Param|null, // Overwrite the setting from the default provider for this adapter.
 *             early_expiration_message_bus?: scalar|Param|null,
 *             clearer?: scalar|Param|null,
 *             marshaller?: scalar|Param|null, // The marshaller service to use for this pool.
 *         }>,
 *     },
 *     php_errors?: array{ // PHP errors handling configuration
 *         log?: mixed, // Use the application logger instead of the PHP logger for logging PHP errors. // Default: true
 *         throw?: bool|Param, // Throw PHP errors as \ErrorException instances. // Default: true
 *     },
 *     exceptions?: array<string, array{ // Default: []
 *         log_level?: scalar|Param|null, // The level of log message. Null to let Symfony decide. // Default: null
 *         status_code?: scalar|Param|null, // The status code of the response. Null or 0 to let Symfony decide. // Default: null
 *         log_channel?: scalar|Param|null, // The channel of log message. Null to let Symfony decide. // Default: null
 *     }>,
 *     web_link?: bool|array{ // Web links configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 *     lock?: bool|string|array{ // Lock configuration
 *         enabled?: bool|Param, // Default: true
 *         resources?: string|array<string, string|list<scalar|Param|null>>,
 *     },
 *     semaphore?: bool|string|array{ // Semaphore configuration
 *         enabled?: bool|Param, // Default: false
 *         resources?: string|array<string, scalar|Param|null>,
 *     },
 *     messenger?: bool|array{ // Messenger configuration
 *         enabled?: bool|Param, // Default: true
 *         routing?: array<string, string|list<scalar|Param|null>>,
 *         serializer?: array{
 *             default_serializer?: scalar|Param|null, // Service id to use as the default serializer for the transports. // Default: "messenger.transport.native_php_serializer"
 *             symfony_serializer?: array{
 *                 format?: scalar|Param|null, // Serialization format for the messenger.transport.symfony_serializer service (which is not the serializer used by default). // Default: "json"
 *                 context?: array<string, mixed>,
 *             },
 *         },
 *         transports?: array<string, string|array{ // Default: []
 *             dsn?: scalar|Param|null,
 *             serializer?: scalar|Param|null, // Service id of a custom serializer to use. // Default: null
 *             options?: array<string, mixed>,
 *             failure_transport?: scalar|Param|null, // Transport name to send failed messages to (after all retries have failed). // Default: null
 *             retry_strategy?: string|array{
 *                 service?: scalar|Param|null, // Service id to override the retry strategy entirely. // Default: null
 *                 max_retries?: int|Param, // Default: 3
 *                 delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
 *                 multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: this delay = (delay * (multiple ^ retries)). // Default: 2
 *                 max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
 *                 jitter?: float|Param, // Randomness to apply to the delay (between 0 and 1). // Default: 0.1
 *             },
 *             rate_limiter?: scalar|Param|null, // Rate limiter name to use when processing messages. // Default: null
 *         }>,
 *         failure_transport?: scalar|Param|null, // Transport name to send failed messages to (after all retries have failed). // Default: null
 *         stop_worker_on_signals?: int|string|list<scalar|Param|null>,
 *         default_bus?: scalar|Param|null, // Default: null
 *         buses?: array<string, array{ // Default: {"messenger.bus.default":{"default_middleware":{"enabled":true,"allow_no_handlers":false,"allow_no_senders":true},"middleware":[]}}
 *             default_middleware?: bool|string|array{
 *                 enabled?: bool|Param, // Default: true
 *                 allow_no_handlers?: bool|Param, // Default: false
 *                 allow_no_senders?: bool|Param, // Default: true
 *             },
 *             middleware?: string|list<string|array{ // Default: []
 *                 id?: scalar|Param|null,
 *                 arguments?: list<mixed>,
 *             }>,
 *         }>,
 *     },
 *     scheduler?: bool|array{ // Scheduler configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 *     disallow_search_engine_index?: bool|Param, // Enabled by default when debug is enabled. // Default: true
 *     http_client?: bool|array{ // HTTP Client configuration
 *         enabled?: bool|Param, // Default: true
 *         max_host_connections?: int|Param, // The maximum number of connections to a single host.
 *         default_options?: array{
 *             headers?: array<string, mixed>,
 *             vars?: array<string, mixed>,
 *             max_redirects?: int|Param, // The maximum number of redirects to follow.
 *             http_version?: scalar|Param|null, // The default HTTP version, typically 1.1 or 2.0, leave to null for the best version.
 *             resolve?: array<string, scalar|Param|null>,
 *             proxy?: scalar|Param|null, // The URL of the proxy to pass requests through or null for automatic detection.
 *             no_proxy?: scalar|Param|null, // A comma separated list of hosts that do not require a proxy to be reached.
 *             timeout?: float|Param, // The idle timeout, defaults to the "default_socket_timeout" ini parameter.
 *             max_duration?: float|Param, // The maximum execution time for the request+response as a whole.
 *             bindto?: scalar|Param|null, // A network interface name, IP address, a host name or a UNIX socket to bind to.
 *             verify_peer?: bool|Param, // Indicates if the peer should be verified in a TLS context.
 *             verify_host?: bool|Param, // Indicates if the host should exist as a certificate common name.
 *             cafile?: scalar|Param|null, // A certificate authority file.
 *             capath?: scalar|Param|null, // A directory that contains multiple certificate authority files.
 *             local_cert?: scalar|Param|null, // A PEM formatted certificate file.
 *             local_pk?: scalar|Param|null, // A private key file.
 *             passphrase?: scalar|Param|null, // The passphrase used to encrypt the "local_pk" file.
 *             ciphers?: scalar|Param|null, // A list of TLS ciphers separated by colons, commas or spaces (e.g. "RC3-SHA:TLS13-AES-128-GCM-SHA256"...)
 *             peer_fingerprint?: array{ // Associative array: hashing algorithm => hash(es).
 *                 sha1?: mixed,
 *                 pin-sha256?: mixed,
 *                 md5?: mixed,
 *             },
 *             crypto_method?: scalar|Param|null, // The minimum version of TLS to accept; must be one of STREAM_CRYPTO_METHOD_TLSv*_CLIENT constants.
 *             extra?: array<string, mixed>,
 *             rate_limiter?: scalar|Param|null, // Rate limiter name to use for throttling requests. // Default: null
 *             caching?: bool|array{ // Caching configuration.
 *                 enabled?: bool|Param, // Default: false
 *                 cache_pool?: string|Param, // The taggable cache pool to use for storing the responses. // Default: "cache.http_client"
 *                 shared?: bool|Param, // Indicates whether the cache is shared (public) or private. // Default: true
 *                 max_ttl?: int|Param, // The maximum TTL (in seconds) allowed for cached responses. // Default: 86400
 *             },
 *             retry_failed?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *                 retry_strategy?: scalar|Param|null, // service id to override the retry strategy. // Default: null
 *                 http_codes?: int|string|array<string, array{ // Default: []
 *                     code?: int|Param,
 *                     methods?: string|list<string|Param>,
 *                 }>,
 *                 max_retries?: int|Param, // Default: 3
 *                 delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
 *                 multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: delay * (multiple ^ retries). // Default: 2
 *                 max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
 *                 jitter?: float|Param, // Randomness in percent (between 0 and 1) to apply to the delay. // Default: 0.1
 *             },
 *         },
 *         mock_response_factory?: scalar|Param|null, // `true` to always return empty 200 responses, or the id of the service to use to generate mock responses - which should be either an invokable or an iterable.
 *         scoped_clients?: array<string, string|array{ // Default: []
 *             scope?: scalar|Param|null, // The regular expression that the request URL must match before adding the other options. When none is provided, the base URI is used instead.
 *             base_uri?: scalar|Param|null, // The URI to resolve relative URLs, following rules in RFC 3985, section 2.
 *             auth_basic?: scalar|Param|null, // An HTTP Basic authentication "username:password".
 *             auth_bearer?: scalar|Param|null, // A token enabling HTTP Bearer authorization.
 *             auth_ntlm?: scalar|Param|null, // A "username:password" pair to use Microsoft NTLM authentication (requires the cURL extension).
 *             query?: array<string, scalar|Param|null>,
 *             headers?: array<string, mixed>,
 *             max_redirects?: int|Param, // The maximum number of redirects to follow.
 *             http_version?: scalar|Param|null, // The default HTTP version, typically 1.1 or 2.0, leave to null for the best version.
 *             resolve?: array<string, scalar|Param|null>,
 *             proxy?: scalar|Param|null, // The URL of the proxy to pass requests through or null for automatic detection.
 *             no_proxy?: scalar|Param|null, // A comma separated list of hosts that do not require a proxy to be reached.
 *             timeout?: float|Param, // The idle timeout, defaults to the "default_socket_timeout" ini parameter.
 *             max_duration?: float|Param, // The maximum execution time for the request+response as a whole.
 *             bindto?: scalar|Param|null, // A network interface name, IP address, a host name or a UNIX socket to bind to.
 *             verify_peer?: bool|Param, // Indicates if the peer should be verified in a TLS context.
 *             verify_host?: bool|Param, // Indicates if the host should exist as a certificate common name.
 *             cafile?: scalar|Param|null, // A certificate authority file.
 *             capath?: scalar|Param|null, // A directory that contains multiple certificate authority files.
 *             local_cert?: scalar|Param|null, // A PEM formatted certificate file.
 *             local_pk?: scalar|Param|null, // A private key file.
 *             passphrase?: scalar|Param|null, // The passphrase used to encrypt the "local_pk" file.
 *             ciphers?: scalar|Param|null, // A list of TLS ciphers separated by colons, commas or spaces (e.g. "RC3-SHA:TLS13-AES-128-GCM-SHA256"...).
 *             peer_fingerprint?: array{ // Associative array: hashing algorithm => hash(es).
 *                 sha1?: mixed,
 *                 pin-sha256?: mixed,
 *                 md5?: mixed,
 *             },
 *             crypto_method?: scalar|Param|null, // The minimum version of TLS to accept; must be one of STREAM_CRYPTO_METHOD_TLSv*_CLIENT constants.
 *             mock_response_factory?: scalar|Param|null, // `true` to always return empty 200 responses, `false` to disable mocking, or the id of the service to use to generate mock responses (invokable or iterable).
 *             extra?: array<string, mixed>,
 *             rate_limiter?: scalar|Param|null, // Rate limiter name to use for throttling requests. // Default: null
 *             caching?: bool|array{ // Caching configuration.
 *                 enabled?: bool|Param, // Default: false
 *                 cache_pool?: string|Param, // The taggable cache pool to use for storing the responses. // Default: "cache.http_client"
 *                 shared?: bool|Param, // Indicates whether the cache is shared (public) or private. // Default: true
 *                 max_ttl?: int|Param, // The maximum TTL (in seconds) allowed for cached responses. // Default: 86400
 *             },
 *             retry_failed?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *                 retry_strategy?: scalar|Param|null, // service id to override the retry strategy. // Default: null
 *                 http_codes?: int|string|array<string, array{ // Default: []
 *                     code?: int|Param,
 *                     methods?: string|list<string|Param>,
 *                 }>,
 *                 max_retries?: int|Param, // Default: 3
 *                 delay?: int|Param, // Time in ms to delay (or the initial value when multiplier is used). // Default: 1000
 *                 multiplier?: float|Param, // If greater than 1, delay will grow exponentially for each retry: delay * (multiple ^ retries). // Default: 2
 *                 max_delay?: int|Param, // Max time in ms that a retry should ever be delayed (0 = infinite). // Default: 0
 *                 jitter?: float|Param, // Randomness in percent (between 0 and 1) to apply to the delay. // Default: 0.1
 *             },
 *         }>,
 *     },
 *     mailer?: bool|array{ // Mailer configuration
 *         enabled?: bool|Param, // Default: true
 *         message_bus?: scalar|Param|null, // The message bus to use. Defaults to the default bus if the Messenger component is installed. // Default: null
 *         dsn?: scalar|Param|null, // Default: null
 *         transports?: array<string, scalar|Param|null>,
 *         envelope?: array{ // Mailer Envelope configuration
 *             sender?: scalar|Param|null,
 *             recipients?: string|list<scalar|Param|null>,
 *             allowed_recipients?: string|list<scalar|Param|null>,
 *         },
 *         headers?: array<string, string|array{ // Default: []
 *             value?: mixed,
 *         }>,
 *         dkim_signer?: bool|array{ // DKIM signer configuration
 *             enabled?: bool|Param, // Default: false
 *             key?: scalar|Param|null, // Key content, or path to key (in PEM format with the `file://` prefix) // Default: ""
 *             domain?: scalar|Param|null, // Default: ""
 *             select?: scalar|Param|null, // Default: ""
 *             passphrase?: scalar|Param|null, // The private key passphrase // Default: ""
 *             options?: array<string, mixed>,
 *         },
 *         smime_signer?: bool|array{ // S/MIME signer configuration
 *             enabled?: bool|Param, // Default: false
 *             key?: scalar|Param|null, // Path to key (in PEM format) // Default: ""
 *             certificate?: scalar|Param|null, // Path to certificate (in PEM format without the `file://` prefix) // Default: ""
 *             passphrase?: scalar|Param|null, // The private key passphrase // Default: null
 *             extra_certificates?: scalar|Param|null, // Default: null
 *             sign_options?: int|Param, // Default: null
 *         },
 *         smime_encrypter?: bool|array{ // S/MIME encrypter configuration
 *             enabled?: bool|Param, // Default: false
 *             repository?: scalar|Param|null, // S/MIME certificate repository service. This service shall implement the `Symfony\Component\Mailer\EventListener\SmimeCertificateRepositoryInterface`. // Default: ""
 *             cipher?: int|Param, // A set of algorithms used to encrypt the message // Default: null
 *         },
 *     },
 *     secrets?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *         vault_directory?: scalar|Param|null, // Default: "%kernel.project_dir%/config/secrets/%kernel.runtime_environment%"
 *         local_dotenv_file?: scalar|Param|null, // Default: "%kernel.project_dir%/.env.%kernel.environment%.local"
 *         decryption_env_var?: scalar|Param|null, // Default: "base64:default::SYMFONY_DECRYPTION_SECRET"
 *     },
 *     notifier?: bool|array{ // Notifier configuration
 *         enabled?: bool|Param, // Default: false
 *         message_bus?: scalar|Param|null, // The message bus to use. Defaults to the default bus if the Messenger component is installed. // Default: null
 *         chatter_transports?: array<string, scalar|Param|null>,
 *         texter_transports?: array<string, scalar|Param|null>,
 *         notification_on_failed_messages?: bool|Param, // Default: false
 *         channel_policy?: array<string, string|list<scalar|Param|null>>,
 *         admin_recipients?: list<array{ // Default: []
 *             email?: scalar|Param|null,
 *             phone?: scalar|Param|null, // Default: ""
 *         }>,
 *     },
 *     rate_limiter?: bool|array{ // Rate limiter configuration
 *         enabled?: bool|Param, // Default: false
 *         limiters?: array<string, array{ // Default: []
 *             lock_factory?: scalar|Param|null, // The service ID of the lock factory used by this limiter (or null to disable locking). // Default: "auto"
 *             cache_pool?: scalar|Param|null, // The cache pool to use for storing the current limiter state. // Default: "cache.rate_limiter"
 *             storage_service?: scalar|Param|null, // The service ID of a custom storage implementation, this precedes any configured "cache_pool". // Default: null
 *             policy?: "fixed_window"|"token_bucket"|"sliding_window"|"compound"|"no_limit"|Param, // The algorithm to be used by this limiter.
 *             limiters?: string|list<scalar|Param|null>,
 *             limit?: int|Param, // The maximum allowed hits in a fixed interval or burst.
 *             interval?: scalar|Param|null, // Configures the fixed interval if "policy" is set to "fixed_window" or "sliding_window". The value must be a number followed by "second", "minute", "hour", "day", "week" or "month" (or their plural equivalent).
 *             rate?: array{ // Configures the fill rate if "policy" is set to "token_bucket".
 *                 interval?: scalar|Param|null, // Configures the rate interval. The value must be a number followed by "second", "minute", "hour", "day", "week" or "month" (or their plural equivalent).
 *                 amount?: int|Param, // Amount of tokens to add each interval. // Default: 1
 *             },
 *             anchor_at?: scalar|Param|null, // Aligns the "fixed_window" policy to a calendar (e.g. "2024-01-05 00:00:00 UTC" combined with `interval: 1 month` resets the counter on the 5th of each month). UTC if not specified. // Default: null
 *         }>,
 *     },
 *     uid?: bool|array{ // Uid configuration
 *         enabled?: bool|Param, // Default: true
 *         default_uuid_version?: 7|6|4|1|Param, // Default: 7
 *         name_based_uuid_version?: 5|3|Param, // Default: 5
 *         name_based_uuid_namespace?: scalar|Param|null,
 *         time_based_uuid_version?: 7|6|1|Param, // Default: 7
 *         time_based_uuid_node?: scalar|Param|null,
 *         uuid47_secret?: scalar|Param|null, // A high-entropy secret used by the "uuid47_transformer" service. Defaults to "kernel.secret". // Default: null
 *     },
 *     html_sanitizer?: bool|array{ // HtmlSanitizer configuration
 *         enabled?: bool|Param, // Default: true
 *         sanitizers?: array<string, array{ // Default: []
 *             default_action?: "drop"|"block"|"allow"|Param, // Defines how the sanitizer must behave by default.
 *             allow_safe_elements?: bool|Param, // Allows "safe" elements and attributes. // Default: false
 *             allow_static_elements?: bool|Param, // Allows all static elements and attributes from the W3C Sanitizer API standard. // Default: false
 *             allow_elements?: array<string, mixed>,
 *             block_elements?: string|list<string|Param>,
 *             drop_elements?: string|list<string|Param>,
 *             allow_attributes?: array<string, mixed>,
 *             drop_attributes?: array<string, mixed>,
 *             force_attributes?: array<string, array<string, string|Param>>,
 *             force_https_urls?: bool|Param, // Transforms URLs using the HTTP scheme to use the HTTPS scheme instead. // Default: false
 *             allowed_link_schemes?: string|list<string|Param>,
 *             allowed_link_hosts?: null|string|list<string|Param>,
 *             allow_relative_links?: bool|Param, // Allows relative URLs to be used in links href attributes. // Default: false
 *             allowed_media_schemes?: string|list<string|Param>,
 *             allowed_media_hosts?: null|string|list<string|Param>,
 *             allow_relative_medias?: bool|Param, // Allows relative URLs to be used in media source attributes (img, audio, video, ...). // Default: false
 *             with_attribute_sanitizers?: string|list<string|Param>,
 *             without_attribute_sanitizers?: string|list<string|Param>,
 *             max_input_length?: int|Param, // The maximum length allowed for the sanitized input. // Default: 0
 *         }>,
 *     },
 *     webhook?: bool|array{ // Webhook configuration
 *         enabled?: bool|Param, // Default: false
 *         message_bus?: scalar|Param|null, // The message bus to use. // Default: "messenger.default_bus"
 *         event_header_name?: scalar|Param|null, // Default: "Webhook-Event"
 *         id_header_name?: scalar|Param|null, // Default: "Webhook-Id"
 *         signature_header_name?: scalar|Param|null, // Default: "Webhook-Signature"
 *         signing_algorithm?: scalar|Param|null, // Default: "sha256"
 *         routing?: array<string, array{ // Default: []
 *             service?: scalar|Param|null,
 *             secret?: scalar|Param|null, // Default: ""
 *         }>,
 *     },
 *     remote-event?: bool|array{ // RemoteEvent configuration
 *         enabled?: bool|Param, // Default: false
 *     },
 *     json_streamer?: bool|array{ // JSON streamer configuration
 *         enabled?: bool|Param, // Default: false
 *         default_options?: array{
 *             include_null_properties?: bool|Param, // Encode the properties with null value // Default: false
 *             ...<string, mixed>
 *         },
 *     },
 * }
 * @psalm-type TwigConfig = array{
 *     form_themes?: list<scalar|Param|null>,
 *     globals?: array<string, array{ // Default: []
 *         id?: scalar|Param|null,
 *         type?: scalar|Param|null,
 *         value?: mixed,
 *     }>,
 *     autoescape_service?: scalar|Param|null, // Default: null
 *     autoescape_service_method?: scalar|Param|null, // Default: null
 *     cache?: scalar|Param|null, // Default: true
 *     charset?: scalar|Param|null, // Default: "%kernel.charset%"
 *     debug?: bool|Param, // Default: "%kernel.debug%"
 *     strict_variables?: bool|Param, // Default: "%kernel.debug%"
 *     auto_reload?: scalar|Param|null,
 *     optimizations?: int|Param,
 *     default_path?: scalar|Param|null, // The default path used to load templates. // Default: "%kernel.project_dir%/templates"
 *     file_name_pattern?: string|list<scalar|Param|null>,
 *     paths?: array<string, mixed>,
 *     date?: array{ // The default format options used by the date filter.
 *         format?: scalar|Param|null, // Default: "F j, Y H:i"
 *         interval_format?: scalar|Param|null, // Default: "%d days"
 *         timezone?: scalar|Param|null, // The timezone used when formatting dates, when set to null, the timezone returned by date_default_timezone_get() is used. // Default: null
 *     },
 *     number_format?: array{ // The default format options for the number_format filter.
 *         decimals?: int|Param, // Default: 0
 *         decimal_point?: scalar|Param|null, // Default: "."
 *         thousands_separator?: scalar|Param|null, // Default: ","
 *     },
 *     mailer?: array{
 *         html_to_text_converter?: scalar|Param|null, // A service implementing the "Symfony\Component\Mime\HtmlToTextConverter\HtmlToTextConverterInterface". // Default: null
 *     },
 * }
 * @psalm-type MonologConfig = array{
 *     use_microseconds?: scalar|Param|null, // Default: true
 *     channels?: list<scalar|Param|null>,
 *     handlers?: array<string, array{ // Default: []
 *         type?: scalar|Param|null,
 *         id?: scalar|Param|null,
 *         enabled?: bool|Param, // Default: true
 *         priority?: scalar|Param|null, // Default: 0
 *         level?: scalar|Param|null, // Default: "DEBUG"
 *         bubble?: bool|Param, // Default: true
 *         interactive_only?: bool|Param, // Default: false
 *         app_name?: scalar|Param|null, // Default: null
 *         include_stacktraces?: bool|Param, // Default: false
 *         process_psr_3_messages?: array{
 *             enabled?: bool|Param|null, // Default: null
 *             date_format?: scalar|Param|null,
 *             remove_used_context_fields?: bool|Param,
 *         },
 *         path?: scalar|Param|null, // Default: "%kernel.logs_dir%/%kernel.environment%.log"
 *         file_permission?: scalar|Param|null, // Default: null
 *         use_locking?: bool|Param, // Default: false
 *         filename_format?: scalar|Param|null, // Default: "{filename}-{date}"
 *         date_format?: scalar|Param|null, // Default: "Y-m-d"
 *         ident?: scalar|Param|null, // Default: false
 *         logopts?: scalar|Param|null, // Default: 1
 *         facility?: scalar|Param|null, // Default: "user"
 *         max_files?: scalar|Param|null, // Default: 0
 *         action_level?: scalar|Param|null, // Default: "WARNING"
 *         activation_strategy?: scalar|Param|null, // Default: null
 *         stop_buffering?: bool|Param, // Default: true
 *         passthru_level?: scalar|Param|null, // Default: null
 *         excluded_http_codes?: list<array{ // Default: []
 *             code?: scalar|Param|null,
 *             urls?: list<scalar|Param|null>,
 *         }>,
 *         accepted_levels?: list<scalar|Param|null>,
 *         min_level?: scalar|Param|null, // Default: "DEBUG"
 *         max_level?: scalar|Param|null, // Default: "EMERGENCY"
 *         buffer_size?: scalar|Param|null, // Default: 0
 *         flush_on_overflow?: bool|Param, // Default: false
 *         handler?: scalar|Param|null,
 *         url?: scalar|Param|null,
 *         exchange?: scalar|Param|null,
 *         exchange_name?: scalar|Param|null, // Default: "log"
 *         channel?: scalar|Param|null, // Default: null
 *         bot_name?: scalar|Param|null, // Default: "Monolog"
 *         use_attachment?: scalar|Param|null, // Default: true
 *         use_short_attachment?: scalar|Param|null, // Default: false
 *         include_extra?: scalar|Param|null, // Default: false
 *         icon_emoji?: scalar|Param|null, // Default: null
 *         webhook_url?: scalar|Param|null,
 *         exclude_fields?: list<scalar|Param|null>,
 *         token?: scalar|Param|null,
 *         region?: scalar|Param|null,
 *         source?: scalar|Param|null,
 *         use_ssl?: bool|Param, // Default: true
 *         user?: mixed,
 *         title?: scalar|Param|null, // Default: null
 *         host?: scalar|Param|null, // Default: null
 *         port?: scalar|Param|null, // Default: 514
 *         config?: list<scalar|Param|null>,
 *         members?: list<scalar|Param|null>,
 *         connection_string?: scalar|Param|null,
 *         timeout?: scalar|Param|null,
 *         time?: scalar|Param|null, // Default: 60
 *         deduplication_level?: scalar|Param|null, // Default: 400
 *         store?: scalar|Param|null, // Default: null
 *         connection_timeout?: scalar|Param|null,
 *         persistent?: bool|Param,
 *         message_type?: scalar|Param|null, // Default: 0
 *         parse_mode?: scalar|Param|null, // Default: null
 *         disable_webpage_preview?: bool|Param|null, // Default: null
 *         disable_notification?: bool|Param|null, // Default: null
 *         split_long_messages?: bool|Param, // Default: false
 *         delay_between_messages?: bool|Param, // Default: false
 *         topic?: int|Param, // Default: null
 *         factor?: int|Param, // Default: 1
 *         tags?: string|list<scalar|Param|null>,
 *         console_formatter_options?: mixed, // Default: []
 *         formatter?: scalar|Param|null,
 *         nested?: bool|Param, // Default: false
 *         publisher?: string|array{
 *             id?: scalar|Param|null,
 *             hostname?: scalar|Param|null,
 *             port?: scalar|Param|null, // Default: 12201
 *             chunk_size?: scalar|Param|null, // Default: 1420
 *             encoder?: "json"|"compressed_json"|Param,
 *         },
 *         mongodb?: string|array{
 *             id?: scalar|Param|null, // ID of a MongoDB\Client service
 *             uri?: scalar|Param|null,
 *             username?: scalar|Param|null,
 *             password?: scalar|Param|null,
 *             database?: scalar|Param|null, // Default: "monolog"
 *             collection?: scalar|Param|null, // Default: "logs"
 *         },
 *         elasticsearch?: string|array{
 *             id?: scalar|Param|null,
 *             hosts?: list<scalar|Param|null>,
 *             host?: scalar|Param|null,
 *             port?: scalar|Param|null, // Default: 9200
 *             transport?: scalar|Param|null, // Default: "Http"
 *             user?: scalar|Param|null, // Default: null
 *             password?: scalar|Param|null, // Default: null
 *         },
 *         index?: scalar|Param|null, // Default: "monolog"
 *         document_type?: scalar|Param|null, // Default: "logs"
 *         ignore_error?: scalar|Param|null, // Default: false
 *         redis?: string|array{
 *             id?: scalar|Param|null,
 *             host?: scalar|Param|null,
 *             password?: scalar|Param|null, // Default: null
 *             port?: scalar|Param|null, // Default: 6379
 *             database?: scalar|Param|null, // Default: 0
 *             key_name?: scalar|Param|null, // Default: "monolog_redis"
 *         },
 *         predis?: string|array{
 *             id?: scalar|Param|null,
 *             host?: scalar|Param|null,
 *         },
 *         from_email?: scalar|Param|null,
 *         to_email?: string|list<scalar|Param|null>,
 *         subject?: scalar|Param|null,
 *         content_type?: scalar|Param|null, // Default: null
 *         headers?: list<scalar|Param|null>,
 *         mailer?: scalar|Param|null, // Default: null
 *         email_prototype?: string|array{
 *             id?: scalar|Param|null,
 *             method?: scalar|Param|null, // Default: null
 *         },
 *         verbosity_levels?: array{
 *             VERBOSITY_QUIET?: scalar|Param|null, // Default: "ERROR"
 *             VERBOSITY_NORMAL?: scalar|Param|null, // Default: "WARNING"
 *             VERBOSITY_VERBOSE?: scalar|Param|null, // Default: "NOTICE"
 *             VERBOSITY_VERY_VERBOSE?: scalar|Param|null, // Default: "INFO"
 *             VERBOSITY_DEBUG?: scalar|Param|null, // Default: "DEBUG"
 *         },
 *         channels?: string|array{
 *             type?: scalar|Param|null,
 *             elements?: list<scalar|Param|null>,
 *         },
 *     }>,
 * }
 * @psalm-type FlysystemConfig = array{
 *     storages?: array<string, array{ // Default: []
 *         adapter?: scalar|Param|null, // DEPRECATED: Use the new config format instead (e.g. "local:" instead of "adapter: local")
 *         options?: list<mixed>,
 *         asyncaws?: array{
 *             client?: scalar|Param|null, // The AsyncAws S3 client service name
 *             bucket?: scalar|Param|null, // The name of the AWS S3 bucket
 *             prefix?: scalar|Param|null, // Optional path prefix to prepend to all object keys // Default: ""
 *         },
 *         aws?: array{
 *             client?: scalar|Param|null, // The AWS S3 client service name
 *             bucket?: scalar|Param|null, // The name of the AWS S3 bucket
 *             prefix?: scalar|Param|null, // Optional path prefix to prepend to all object keys // Default: ""
 *             options?: list<mixed>,
 *             streamReads?: bool|Param, // Whether to use streaming for file reads // Default: true
 *         },
 *         azure?: array{
 *             client?: scalar|Param|null, // The Azure Blob Storage client service name
 *             container?: scalar|Param|null, // The name of the Azure Blob Storage container
 *             prefix?: scalar|Param|null, // Optional path prefix to prepend to all blob names // Default: ""
 *         },
 *         ftp?: array{
 *             host?: scalar|Param|null, // FTP host
 *             username?: scalar|Param|null, // FTP username
 *             password?: scalar|Param|null, // FTP password
 *             port?: int|Param, // FTP port number // Default: 21
 *             root?: scalar|Param|null, // FTP root directory // Default: ""
 *             passive?: bool|Param, // Use passive mode // Default: true
 *             ssl?: bool|Param, // Use SSL/TLS encryption // Default: false
 *             timeout?: int|Param, // Connection timeout in seconds // Default: 90
 *             ignore_passive_address?: scalar|Param|null, // Ignore passive address // Default: null
 *             utf8?: bool|Param, // Enable UTF8 mode // Default: false
 *             transfer_mode?: scalar|Param|null, // Transfer mode (FTP_ASCII or FTP_BINARY constante on ftp extension) // Default: null
 *             system_type?: null|"windows"|"unix"|Param, // FTP system type // Default: null
 *             timestamps_on_unix_listings_enabled?: bool|Param, // Enable timestamps on Unix listings // Default: false
 *             recurse_manually?: bool|Param, // Recurse directories manually // Default: true
 *             use_raw_list_options?: bool|Param|null, // Use raw list options // Default: null
 *             connectivityChecker?: scalar|Param|null, // Connectivity checker service name // Default: null
 *             permissions?: array{ // Unix permissions configuration for files and directories
 *                 file?: array{ // File permissions
 *                     public?: int|Param, // Public file permissions // Default: 420
 *                     private?: int|Param, // Private file permissions // Default: 384
 *                 },
 *                 dir?: array{ // Directory permissions
 *                     public?: int|Param, // Public directory permissions // Default: 493
 *                     private?: int|Param, // Private directory permissions // Default: 448
 *                 },
 *             },
 *         },
 *         gcloud?: array{
 *             client?: scalar|Param|null, // The Google Cloud Storage client service name
 *             bucket?: scalar|Param|null, // The name of the Google Cloud Storage bucket
 *             prefix?: scalar|Param|null, // Optional path prefix to prepend to all object keys // Default: ""
 *             visibility_handler?: scalar|Param|null, // Optional visibility handler service name // Default: null
 *             streamReads?: bool|Param, // Whether to use streaming for file reads // Default: false
 *         },
 *         gridfs?: array{
 *             bucket?: scalar|Param|null, // GridFS bucket service name (if using an existing bucket service) // Default: null
 *             prefix?: scalar|Param|null, // Optional path prefix to prepend to all file names // Default: ""
 *             database?: scalar|Param|null, // MongoDB database name // Default: null
 *             doctrine_connection?: scalar|Param|null, // Doctrine MongoDB connection name (mutually exclusive with mongodb_uri)
 *             mongodb_uri?: scalar|Param|null, // MongoDB connection URI (mutually exclusive with doctrine_connection)
 *             mongodb_uri_options?: list<mixed>,
 *             mongodb_driver_options?: list<mixed>,
 *         },
 *         lazy?: array{ // Lazy adapter for runtime storage selection
 *             source?: scalar|Param|null, // The service name of the storage to use at runtime
 *         },
 *         local?: array{
 *             directory?: scalar|Param|null, // Directory path for local storage
 *             lock?: int|Param, // Lock flags for file operations // Default: 0
 *             skip_links?: bool|Param, // Whether to skip symbolic links // Default: false
 *             lazy_root_creation?: bool|Param, // Whether to create the root directory lazily // Default: false
 *             permissions?: array{ // Unix permissions configuration for files and directories
 *                 file?: array{ // File permissions
 *                     public?: int|Param, // Public file permissions // Default: 420
 *                     private?: int|Param, // Private file permissions // Default: 384
 *                 },
 *                 dir?: array{ // Directory permissions
 *                     public?: int|Param, // Public directory permissions // Default: 493
 *                     private?: int|Param, // Private directory permissions // Default: 448
 *                 },
 *             },
 *         },
 *         memory?: array<mixed>,
 *         sftp?: array{
 *             host?: scalar|Param|null, // SFTP host
 *             username?: scalar|Param|null, // SFTP username
 *             password?: scalar|Param|null, // SFTP password (optional if using private key) // Default: null
 *             privateKey?: scalar|Param|null, // Path to private key file or private key content // Default: null
 *             passphrase?: scalar|Param|null, // Private key passphrase // Default: null
 *             port?: int|Param, // SFTP port number // Default: 22
 *             timeout?: int|Param, // Connection timeout in seconds // Default: 90
 *             hostFingerprint?: scalar|Param|null, // Host fingerprint for verification // Default: null
 *             connectivityChecker?: scalar|Param|null, // Connectivity checker service name // Default: null
 *             preferredAlgorithms?: list<mixed>,
 *             root?: scalar|Param|null, // SFTP root directory // Default: ""
 *             permissions?: array{ // Unix permissions configuration for files and directories
 *                 file?: array{ // File permissions
 *                     public?: int|Param, // Public file permissions // Default: 420
 *                     private?: int|Param, // Private file permissions // Default: 384
 *                 },
 *                 dir?: array{ // Directory permissions
 *                     public?: int|Param, // Public directory permissions // Default: 493
 *                     private?: int|Param, // Private directory permissions // Default: 448
 *                 },
 *             },
 *         },
 *         webdav?: array{
 *             client?: scalar|Param|null, // The WebDAV client service name
 *             prefix?: scalar|Param|null, // Optional path prefix to prepend to all paths // Default: ""
 *             visibility_handling?: "throw"|"ignore"|Param, // How to handle visibility operations // Default: "throw"
 *             manual_copy?: bool|Param, // Whether to handle copy operations manually // Default: false
 *             manual_move?: bool|Param, // Whether to handle move operations manually // Default: false
 *         },
 *         bunnycdn?: array{
 *             client?: scalar|Param|null, // The BunnyCDN client service name
 *             pull_zone?: scalar|Param|null, // The BunnyCDN pull zone name // Default: ""
 *         },
 *         service?: scalar|Param|null, // Reference to a custom adapter service (alternative to registered adapter types)
 *         visibility?: scalar|Param|null, // Default visibility for files // Default: null
 *         directory_visibility?: scalar|Param|null, // Default visibility for directories // Default: null
 *         retain_visibility?: scalar|Param|null, // Keeps the original file visibility (public/private) when copying or moving. // Default: null
 *         case_sensitive?: bool|Param, // Deprecated: The "case_sensitive" option is deprecated and will be removed in 4.0. // Default: true
 *         disable_asserts?: bool|Param, // Deprecated: The "disable_asserts" option is deprecated and will be removed in 4.0. // Default: false
 *         public_url?: list<scalar|Param|null>,
 *         path_normalizer?: scalar|Param|null, // Path normalizer service name (should implement League\Flysystem\PathNormalizer) // Default: null
 *         public_url_generator?: scalar|Param|null, // For adapter that do not provide public URLs or override adapter capabilities and public_url option, a public URL generator service name can be configured in the main Filesystem configuration (should implement League\Flysystem\PublicUrlGenerator) // Default: null
 *         temporary_url_generator?: scalar|Param|null, // For adapter that do not provide public URLs or override adapter capabilities, a temporary URL generator service name can be configured in the main Filesystem configuration (should implement League\Flysystem\TemporaryUrlGenerator) // Default: null
 *         read_only?: bool|Param, // Converts a file system to read-only // Default: false
 *     }>,
 * }
 * @psalm-type SuluCoreConfig = array{
 *     webspace?: array{
 *         config_dir?: scalar|Param|null, // Default: "%kernel.project_dir%/config/webspaces"
 *     },
 *     fields_defaults?: array{
 *         translations?: array{
 *             id?: scalar|Param|null, // Default: "public.id"
 *             title?: scalar|Param|null, // Default: "public.title"
 *             name?: scalar|Param|null, // Default: "public.name"
 *             created?: scalar|Param|null, // Default: "public.created"
 *             changed?: scalar|Param|null, // Default: "public.changed"
 *         },
 *         widths?: array{
 *             id?: scalar|Param|null, // Default: "50px"
 *         },
 *     },
 *     cache_dir?: scalar|Param|null, // Default: "%kernel.cache_dir%/sulu"
 *     cache?: array{
 *         memoize?: array{
 *             default_lifetime?: scalar|Param|null, // Default: 1
 *         },
 *     },
 *     locales?: array<string, scalar|Param|null>,
 *     translations?: list<scalar|Param|null>,
 *     fallback_locale?: scalar|Param|null, // Default: "en"
 * }
 * @psalm-type DoctrineConfig = array{
 *     dbal?: array{
 *         default_connection?: scalar|Param|null,
 *         types?: array<string, string|array{ // Default: []
 *             class?: scalar|Param|null,
 *         }>,
 *         driver_schemes?: array<string, scalar|Param|null>,
 *         connections?: array<string, array{ // Default: []
 *             url?: scalar|Param|null, // A URL with connection information; any parameter value parsed from this string will override explicitly set parameters
 *             dbname?: scalar|Param|null,
 *             host?: scalar|Param|null, // Defaults to "localhost" at runtime.
 *             port?: scalar|Param|null, // Defaults to null at runtime.
 *             user?: scalar|Param|null, // Defaults to "root" at runtime.
 *             password?: scalar|Param|null, // Defaults to null at runtime.
 *             dbname_suffix?: scalar|Param|null, // Adds the given suffix to the configured database name, this option has no effects for the SQLite platform
 *             application_name?: scalar|Param|null,
 *             charset?: scalar|Param|null,
 *             path?: scalar|Param|null,
 *             memory?: bool|Param,
 *             unix_socket?: scalar|Param|null, // The unix socket to use for MySQL
 *             persistent?: bool|Param, // True to use as persistent connection for the ibm_db2 driver
 *             protocol?: scalar|Param|null, // The protocol to use for the ibm_db2 driver (default to TCPIP if omitted)
 *             service?: bool|Param, // True to use SERVICE_NAME as connection parameter instead of SID for Oracle
 *             servicename?: scalar|Param|null, // Overrules dbname parameter if given and used as SERVICE_NAME or SID connection parameter for Oracle depending on the service parameter.
 *             sessionMode?: scalar|Param|null, // The session mode to use for the oci8 driver
 *             server?: scalar|Param|null, // The name of a running database server to connect to for SQL Anywhere.
 *             default_dbname?: scalar|Param|null, // Override the default database (postgres) to connect to for PostgreSQL connection.
 *             sslmode?: scalar|Param|null, // Determines whether or with what priority a SSL TCP/IP connection will be negotiated with the server for PostgreSQL.
 *             sslrootcert?: scalar|Param|null, // The name of a file containing SSL certificate authority (CA) certificate(s). If the file exists, the server's certificate will be verified to be signed by one of these authorities.
 *             sslcert?: scalar|Param|null, // The path to the SSL client certificate file for PostgreSQL.
 *             sslkey?: scalar|Param|null, // The path to the SSL client key file for PostgreSQL.
 *             sslcrl?: scalar|Param|null, // The file name of the SSL certificate revocation list for PostgreSQL.
 *             pooled?: bool|Param, // True to use a pooled server with the oci8/pdo_oracle driver
 *             MultipleActiveResultSets?: bool|Param, // Configuring MultipleActiveResultSets for the pdo_sqlsrv driver
 *             instancename?: scalar|Param|null, // Optional parameter, complete whether to add the INSTANCE_NAME parameter in the connection. It is generally used to connect to an Oracle RAC server to select the name of a particular instance.
 *             connectstring?: scalar|Param|null, // Complete Easy Connect connection descriptor, see https://docs.oracle.com/database/121/NETAG/naming.htm.When using this option, you will still need to provide the user and password parameters, but the other parameters will no longer be used. Note that when using this parameter, the getHost and getPort methods from Doctrine\DBAL\Connection will no longer function as expected.
 *             driver?: scalar|Param|null, // Default: "pdo_mysql"
 *             auto_commit?: bool|Param,
 *             schema_filter?: scalar|Param|null,
 *             logging?: bool|Param, // Default: true
 *             profiling?: bool|Param, // Default: true
 *             profiling_collect_backtrace?: bool|Param, // Enables collecting backtraces when profiling is enabled // Default: false
 *             profiling_collect_schema_errors?: bool|Param, // Enables collecting schema errors when profiling is enabled // Default: true
 *             server_version?: scalar|Param|null,
 *             idle_connection_ttl?: int|Param, // Default: 600
 *             driver_class?: scalar|Param|null,
 *             wrapper_class?: scalar|Param|null,
 *             keep_replica?: bool|Param,
 *             options?: array<string, mixed>,
 *             mapping_types?: array<string, scalar|Param|null>,
 *             default_table_options?: array<string, scalar|Param|null>,
 *             schema_manager_factory?: scalar|Param|null, // Default: "doctrine.dbal.default_schema_manager_factory"
 *             result_cache?: scalar|Param|null,
 *             replicas?: array<string, array{ // Default: []
 *                 url?: scalar|Param|null, // A URL with connection information; any parameter value parsed from this string will override explicitly set parameters
 *                 dbname?: scalar|Param|null,
 *                 host?: scalar|Param|null, // Defaults to "localhost" at runtime.
 *                 port?: scalar|Param|null, // Defaults to null at runtime.
 *                 user?: scalar|Param|null, // Defaults to "root" at runtime.
 *                 password?: scalar|Param|null, // Defaults to null at runtime.
 *                 dbname_suffix?: scalar|Param|null, // Adds the given suffix to the configured database name, this option has no effects for the SQLite platform
 *                 application_name?: scalar|Param|null,
 *                 charset?: scalar|Param|null,
 *                 path?: scalar|Param|null,
 *                 memory?: bool|Param,
 *                 unix_socket?: scalar|Param|null, // The unix socket to use for MySQL
 *                 persistent?: bool|Param, // True to use as persistent connection for the ibm_db2 driver
 *                 protocol?: scalar|Param|null, // The protocol to use for the ibm_db2 driver (default to TCPIP if omitted)
 *                 service?: bool|Param, // True to use SERVICE_NAME as connection parameter instead of SID for Oracle
 *                 servicename?: scalar|Param|null, // Overrules dbname parameter if given and used as SERVICE_NAME or SID connection parameter for Oracle depending on the service parameter.
 *                 sessionMode?: scalar|Param|null, // The session mode to use for the oci8 driver
 *                 server?: scalar|Param|null, // The name of a running database server to connect to for SQL Anywhere.
 *                 default_dbname?: scalar|Param|null, // Override the default database (postgres) to connect to for PostgreSQL connection.
 *                 sslmode?: scalar|Param|null, // Determines whether or with what priority a SSL TCP/IP connection will be negotiated with the server for PostgreSQL.
 *                 sslrootcert?: scalar|Param|null, // The name of a file containing SSL certificate authority (CA) certificate(s). If the file exists, the server's certificate will be verified to be signed by one of these authorities.
 *                 sslcert?: scalar|Param|null, // The path to the SSL client certificate file for PostgreSQL.
 *                 sslkey?: scalar|Param|null, // The path to the SSL client key file for PostgreSQL.
 *                 sslcrl?: scalar|Param|null, // The file name of the SSL certificate revocation list for PostgreSQL.
 *                 pooled?: bool|Param, // True to use a pooled server with the oci8/pdo_oracle driver
 *                 MultipleActiveResultSets?: bool|Param, // Configuring MultipleActiveResultSets for the pdo_sqlsrv driver
 *                 instancename?: scalar|Param|null, // Optional parameter, complete whether to add the INSTANCE_NAME parameter in the connection. It is generally used to connect to an Oracle RAC server to select the name of a particular instance.
 *                 connectstring?: scalar|Param|null, // Complete Easy Connect connection descriptor, see https://docs.oracle.com/database/121/NETAG/naming.htm.When using this option, you will still need to provide the user and password parameters, but the other parameters will no longer be used. Note that when using this parameter, the getHost and getPort methods from Doctrine\DBAL\Connection will no longer function as expected.
 *             }>,
 *         }>,
 *     },
 *     orm?: array{
 *         default_entity_manager?: scalar|Param|null,
 *         enable_native_lazy_objects?: bool|Param, // Deprecated: The "enable_native_lazy_objects" option is deprecated and will be removed in DoctrineBundle 4.0, as native lazy objects are now always enabled. // Default: true
 *         controller_resolver?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *             auto_mapping?: bool|Param, // Deprecated: The "doctrine.orm.controller_resolver.auto_mapping.auto_mapping" option is deprecated and will be removed in DoctrineBundle 4.0, as it only accepts `false` since 3.0. // Set to true to enable using route placeholders as lookup criteria when the primary key doesn't match the argument name // Default: false
 *             evict_cache?: bool|Param, // Set to true to fetch the entity from the database instead of using the cache, if any // Default: false
 *         },
 *         entity_managers?: array<string, array{ // Default: []
 *             query_cache_driver?: string|array{
 *                 type?: scalar|Param|null, // Default: null
 *                 id?: scalar|Param|null,
 *                 pool?: scalar|Param|null,
 *             },
 *             metadata_cache_driver?: string|array{
 *                 type?: scalar|Param|null, // Default: null
 *                 id?: scalar|Param|null,
 *                 pool?: scalar|Param|null,
 *             },
 *             result_cache_driver?: string|array{
 *                 type?: scalar|Param|null, // Default: null
 *                 id?: scalar|Param|null,
 *                 pool?: scalar|Param|null,
 *             },
 *             entity_listeners?: array{
 *                 entities?: array<string, array{ // Default: []
 *                     listeners?: array<string, array{ // Default: []
 *                         events?: list<array{ // Default: []
 *                             type?: scalar|Param|null,
 *                             method?: scalar|Param|null, // Default: null
 *                         }>,
 *                     }>,
 *                 }>,
 *             },
 *             connection?: scalar|Param|null,
 *             class_metadata_factory_name?: scalar|Param|null, // Default: "Doctrine\\ORM\\Mapping\\ClassMetadataFactory"
 *             default_repository_class?: scalar|Param|null, // Default: "Doctrine\\ORM\\EntityRepository"
 *             auto_mapping?: scalar|Param|null, // Default: false
 *             naming_strategy?: scalar|Param|null, // Default: "doctrine.orm.naming_strategy.default"
 *             quote_strategy?: scalar|Param|null, // Default: "doctrine.orm.quote_strategy.default"
 *             typed_field_mapper?: scalar|Param|null, // Default: "doctrine.orm.typed_field_mapper.default"
 *             entity_listener_resolver?: scalar|Param|null, // Default: null
 *             fetch_mode_subselect_batch_size?: scalar|Param|null,
 *             repository_factory?: scalar|Param|null, // Default: "doctrine.orm.container_repository_factory"
 *             schema_ignore_classes?: list<scalar|Param|null>,
 *             validate_xml_mapping?: bool|Param, // Set to "true" to opt-in to the new mapping driver mode that was added in Doctrine ORM 2.14 and will be mandatory in ORM 3.0. See https://github.com/doctrine/orm/pull/6728. // Default: false
 *             second_level_cache?: array{
 *                 region_cache_driver?: string|array{
 *                     type?: scalar|Param|null, // Default: null
 *                     id?: scalar|Param|null,
 *                     pool?: scalar|Param|null,
 *                 },
 *                 region_lock_lifetime?: scalar|Param|null, // Default: 60
 *                 log_enabled?: bool|Param, // Default: true
 *                 region_lifetime?: scalar|Param|null, // Default: 3600
 *                 enabled?: bool|Param, // Default: true
 *                 factory?: scalar|Param|null,
 *                 regions?: array<string, array{ // Default: []
 *                     cache_driver?: string|array{
 *                         type?: scalar|Param|null, // Default: null
 *                         id?: scalar|Param|null,
 *                         pool?: scalar|Param|null,
 *                     },
 *                     lock_path?: scalar|Param|null, // Default: "%kernel.cache_dir%/doctrine/orm/slc/filelock"
 *                     lock_lifetime?: scalar|Param|null, // Default: 60
 *                     type?: scalar|Param|null, // Default: "default"
 *                     lifetime?: scalar|Param|null, // Default: null
 *                     service?: scalar|Param|null,
 *                     name?: scalar|Param|null,
 *                 }>,
 *                 loggers?: array<string, array{ // Default: []
 *                     name?: scalar|Param|null,
 *                     service?: scalar|Param|null,
 *                 }>,
 *             },
 *             hydrators?: array<string, scalar|Param|null>,
 *             mappings?: array<string, bool|string|array{ // Default: []
 *                 mapping?: scalar|Param|null, // Default: true
 *                 type?: scalar|Param|null,
 *                 dir?: scalar|Param|null,
 *                 alias?: scalar|Param|null,
 *                 prefix?: scalar|Param|null,
 *                 is_bundle?: bool|Param,
 *             }>,
 *             dql?: array{
 *                 string_functions?: array<string, scalar|Param|null>,
 *                 numeric_functions?: array<string, scalar|Param|null>,
 *                 datetime_functions?: array<string, scalar|Param|null>,
 *             },
 *             filters?: array<string, string|array{ // Default: []
 *                 class?: scalar|Param|null,
 *                 enabled?: bool|Param, // Default: false
 *                 parameters?: array<string, mixed>,
 *             }>,
 *             identity_generation_preferences?: array<string, scalar|Param|null>,
 *         }>,
 *         resolve_target_entities?: array<string, scalar|Param|null>,
 *     },
 * }
 * @psalm-type StofDoctrineExtensionsConfig = array{
 *     orm?: array<string, array{ // Default: []
 *         translatable?: scalar|Param|null, // Default: false
 *         timestampable?: scalar|Param|null, // Default: false
 *         blameable?: scalar|Param|null, // Default: false
 *         sluggable?: scalar|Param|null, // Default: false
 *         tree?: scalar|Param|null, // Default: false
 *         loggable?: scalar|Param|null, // Default: false
 *         ip_traceable?: scalar|Param|null, // Default: false
 *         sortable?: scalar|Param|null, // Default: false
 *         softdeleteable?: scalar|Param|null, // Default: false
 *         uploadable?: scalar|Param|null, // Default: false
 *         reference_integrity?: scalar|Param|null, // Default: false
 *     }>,
 *     mongodb?: array<string, array{ // Default: []
 *         translatable?: scalar|Param|null, // Default: false
 *         timestampable?: scalar|Param|null, // Default: false
 *         blameable?: scalar|Param|null, // Default: false
 *         sluggable?: scalar|Param|null, // Default: false
 *         tree?: scalar|Param|null, // Default: false
 *         loggable?: scalar|Param|null, // Default: false
 *         ip_traceable?: scalar|Param|null, // Default: false
 *         sortable?: scalar|Param|null, // Default: false
 *         softdeleteable?: scalar|Param|null, // Default: false
 *         uploadable?: scalar|Param|null, // Default: false
 *         reference_integrity?: scalar|Param|null, // Default: false
 *     }>,
 *     class?: array{
 *         translatable?: scalar|Param|null, // Default: "Gedmo\\Translatable\\TranslatableListener"
 *         timestampable?: scalar|Param|null, // Default: "Gedmo\\Timestampable\\TimestampableListener"
 *         blameable?: scalar|Param|null, // Default: "Gedmo\\Blameable\\BlameableListener"
 *         sluggable?: scalar|Param|null, // Default: "Gedmo\\Sluggable\\SluggableListener"
 *         tree?: scalar|Param|null, // Default: "Gedmo\\Tree\\TreeListener"
 *         loggable?: scalar|Param|null, // Default: "Gedmo\\Loggable\\LoggableListener"
 *         sortable?: scalar|Param|null, // Default: "Gedmo\\Sortable\\SortableListener"
 *         softdeleteable?: scalar|Param|null, // Default: "Gedmo\\SoftDeleteable\\SoftDeleteableListener"
 *         uploadable?: scalar|Param|null, // Default: "Gedmo\\Uploadable\\UploadableListener"
 *         reference_integrity?: scalar|Param|null, // Default: "Gedmo\\ReferenceIntegrity\\ReferenceIntegrityListener"
 *     },
 *     softdeleteable?: array{
 *         handle_post_flush_event?: bool|Param, // Default: false
 *     },
 *     uploadable?: array{
 *         default_file_path?: scalar|Param|null, // Default: null
 *         mime_type_guesser_class?: scalar|Param|null, // Default: "Stof\\DoctrineExtensionsBundle\\Uploadable\\MimeTypeGuesserAdapter"
 *         default_file_info_class?: scalar|Param|null, // Default: "Stof\\DoctrineExtensionsBundle\\Uploadable\\UploadedFileInfo"
 *         validate_writable_directory?: bool|Param, // Default: true
 *     },
 *     default_locale?: scalar|Param|null, // Default: "en"
 *     translation_fallback?: bool|Param, // Default: false
 *     persist_default_translation?: bool|Param, // Default: false
 *     skip_translation_on_load?: bool|Param, // Default: false
 *     metadata_cache_pool?: scalar|Param|null, // Default: null
 * }
 * @psalm-type FosRestConfig = array{
 *     disable_csrf_role?: scalar|Param|null, // Default: null
 *     unauthorized_challenge?: scalar|Param|null, // Default: null
 *     param_fetcher_listener?: bool|string|array{
 *         enabled?: bool|Param, // Default: false
 *         force?: bool|Param, // Default: false
 *         service?: scalar|Param|null, // Default: null
 *     },
 *     cache_dir?: scalar|Param|null, // Default: "%kernel.cache_dir%/fos_rest"
 *     allowed_methods_listener?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         service?: scalar|Param|null, // Default: null
 *     },
 *     routing_loader?: bool|Param, // Default: false
 *     body_converter?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         validate?: scalar|Param|null, // Default: false
 *         validation_errors_argument?: scalar|Param|null, // Default: "validationErrors"
 *     },
 *     service?: array{
 *         serializer?: scalar|Param|null, // Default: null
 *         view_handler?: scalar|Param|null, // Default: "fos_rest.view_handler.default"
 *         validator?: scalar|Param|null, // Default: "validator"
 *     },
 *     serializer?: array{
 *         version?: scalar|Param|null, // Default: null
 *         groups?: list<scalar|Param|null>,
 *         serialize_null?: bool|Param, // Default: false
 *     },
 *     zone?: list<array{ // Default: []
 *         path?: scalar|Param|null, // use the urldecoded format // Default: null
 *         host?: scalar|Param|null, // Default: null
 *         methods?: string|list<scalar|Param|null>,
 *         ips?: string|list<scalar|Param|null>,
 *     }>,
 *     view?: array{
 *         mime_types?: bool|array{
 *             enabled?: bool|Param, // Default: false
 *             service?: scalar|Param|null, // Default: null
 *             formats?: array<string, string|list<scalar|Param|null>>,
 *         },
 *         formats?: array<string, bool|Param>,
 *         view_response_listener?: bool|string|array{
 *             enabled?: bool|Param, // Default: false
 *             force?: bool|Param, // Default: false
 *             service?: scalar|Param|null, // Default: null
 *         },
 *         failed_validation?: scalar|Param|null, // Default: 400
 *         empty_content?: scalar|Param|null, // Default: 204
 *         serialize_null?: bool|Param, // Default: false
 *         jsonp_handler?: array{
 *             callback_param?: scalar|Param|null, // Default: "callback"
 *             mime_type?: scalar|Param|null, // Default: "application/javascript+jsonp"
 *         },
 *     },
 *     exception?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         map_exception_codes?: bool|Param, // Enables an event listener that maps exception codes to response status codes based on the map configured with the "fos_rest.exception.codes" option. // Default: false
 *         exception_listener?: bool|Param, // Default: false
 *         serialize_exceptions?: bool|Param, // Default: false
 *         flatten_exception_format?: "legacy"|"rfc7807"|Param, // Default: "legacy"
 *         serializer_error_renderer?: bool|Param, // Default: false
 *         codes?: array<string, int|Param>,
 *         messages?: array<string, bool|Param>,
 *         debug?: bool|Param, // Default: true
 *     },
 *     body_listener?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         service?: scalar|Param|null, // Default: null
 *         default_format?: scalar|Param|null, // Default: null
 *         throw_exception_on_unsupported_content_type?: bool|Param, // Default: false
 *         decoders?: array<string, scalar|Param|null>,
 *         array_normalizer?: string|array{
 *             service?: scalar|Param|null, // Default: null
 *             forms?: bool|Param, // Default: false
 *         },
 *     },
 *     format_listener?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         service?: scalar|Param|null, // Default: null
 *         rules?: list<array{ // Default: []
 *             path?: scalar|Param|null, // URL path info // Default: null
 *             host?: scalar|Param|null, // URL host name // Default: null
 *             methods?: mixed, // Method for URL // Default: null
 *             attributes?: array<string, mixed>,
 *             stop?: bool|Param, // Default: false
 *             prefer_extension?: bool|Param, // Default: true
 *             fallback_format?: scalar|Param|null, // Default: "html"
 *             priorities?: string|list<scalar|Param|null>,
 *         }>,
 *     },
 *     versioning?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         default_version?: scalar|Param|null, // Default: null
 *         resolvers?: array{
 *             query?: bool|array{
 *                 enabled?: bool|Param, // Default: true
 *                 parameter_name?: scalar|Param|null, // Default: "version"
 *             },
 *             custom_header?: bool|array{
 *                 enabled?: bool|Param, // Default: true
 *                 header_name?: scalar|Param|null, // Default: "X-Accept-Version"
 *             },
 *             media_type?: bool|array{
 *                 enabled?: bool|Param, // Default: true
 *                 regex?: scalar|Param|null, // Default: "/(v|version)=(?P<version>[0-9\\.]+)/"
 *             },
 *         },
 *         guessing_order?: list<scalar|Param|null>,
 *     },
 * }
 * @psalm-type JmsSerializerConfig = array{
 *     twig_enabled?: scalar|Param|null, // Default: "default"
 *     profiler?: scalar|Param|null, // Default: true
 *     enum_support?: scalar|Param|null, // Default: false
 *     default_value_property_reader_support?: scalar|Param|null, // Default: false
 *     handlers?: array{
 *         datetime?: array{
 *             default_format?: scalar|Param|null, // Default: "Y-m-d\\TH:i:sP"
 *             default_deserialization_formats?: list<scalar|Param|null>,
 *             default_timezone?: scalar|Param|null, // Default: "UTC"
 *             cdata?: scalar|Param|null, // Default: true
 *         },
 *         array_collection?: array{
 *             initialize_excluded?: bool|Param, // Default: false
 *         },
 *         symfony_uid?: array{
 *             default_format?: scalar|Param|null, // Default: "canonical"
 *             cdata?: scalar|Param|null, // Default: true
 *         },
 *     },
 *     subscribers?: array{
 *         doctrine_proxy?: array{
 *             initialize_excluded?: bool|Param, // Default: false
 *             initialize_virtual_types?: bool|Param, // Default: false
 *         },
 *     },
 *     object_constructors?: array{
 *         doctrine?: bool|array{
 *             enabled?: bool|Param, // Default: true
 *             fallback_strategy?: "null"|"exception"|"fallback"|Param, // Default: "null"
 *         },
 *     },
 *     property_naming?: string|array{
 *         id?: scalar|Param|null,
 *         separator?: scalar|Param|null, // Default: "_"
 *         lower_case?: bool|Param, // Default: true
 *     },
 *     expression_evaluator?: string|array{
 *         id?: scalar|Param|null, // Default: "jms_serializer.expression_evaluator"
 *     },
 *     metadata?: array{
 *         warmup?: array{
 *             paths?: array{
 *                 included?: list<scalar|Param|null>,
 *                 excluded?: list<scalar|Param|null>,
 *             },
 *         },
 *         cache?: scalar|Param|null, // Default: "file"
 *         debug?: bool|Param, // Default: true
 *         file_cache?: array{
 *             dir?: scalar|Param|null, // Default: null
 *         },
 *         include_interfaces?: bool|Param, // Default: false
 *         auto_detection?: bool|Param, // Default: true
 *         infer_types_from_doc_block?: bool|Param, // Default: false
 *         infer_types_from_doctrine_metadata?: bool|Param, // Infers type information from Doctrine metadata if no explicit type has been defined for a property. // Default: true
 *         directories?: array<string, array{ // Default: []
 *             path?: scalar|Param|null,
 *             namespace_prefix?: scalar|Param|null, // Default: ""
 *         }>,
 *     },
 *     visitors?: array{
 *         json_serialization?: array{
 *             depth?: scalar|Param|null,
 *             options?: scalar|Param|null, // Default: 1024
 *         },
 *         json_deserialization?: array{
 *             options?: scalar|Param|null, // Default: 0
 *             strict?: bool|Param, // Default: false
 *         },
 *         xml_serialization?: array{
 *             version?: scalar|Param|null,
 *             encoding?: scalar|Param|null,
 *             format_output?: bool|Param, // Default: false
 *             default_root_name?: scalar|Param|null,
 *             default_root_ns?: scalar|Param|null, // Default: ""
 *         },
 *         xml_deserialization?: array{
 *             doctype_whitelist?: list<scalar|Param|null>,
 *             external_entities?: bool|Param, // Default: false
 *             options?: scalar|Param|null, // Default: 0
 *         },
 *     },
 *     default_context?: array{
 *         serialization?: string|array{
 *             id?: scalar|Param|null,
 *             serialize_null?: scalar|Param|null, // Flag if null values should be serialized
 *             enable_max_depth_checks?: scalar|Param|null, // Flag to enable the max-depth exclusion strategy
 *             attributes?: array<string, scalar|Param|null>,
 *             groups?: list<scalar|Param|null>,
 *             version?: scalar|Param|null, // Application version to use in exclusion strategies
 *         },
 *         deserialization?: string|array{
 *             id?: scalar|Param|null,
 *             serialize_null?: scalar|Param|null, // Flag if null values should be serialized
 *             enable_max_depth_checks?: scalar|Param|null, // Flag to enable the max-depth exclusion strategy
 *             attributes?: array<string, scalar|Param|null>,
 *             groups?: list<scalar|Param|null>,
 *             version?: scalar|Param|null, // Application version to use in exclusion strategies
 *         },
 *     },
 *     instances?: array<string, array{ // Default: []
 *         inherit?: bool|Param, // Default: false
 *         enum_support?: scalar|Param|null, // Default: false
 *         default_value_property_reader_support?: scalar|Param|null, // Default: false
 *         handlers?: array{
 *             datetime?: array{
 *                 default_format?: scalar|Param|null, // Default: "Y-m-d\\TH:i:sP"
 *                 default_deserialization_formats?: list<scalar|Param|null>,
 *                 default_timezone?: scalar|Param|null, // Default: "UTC"
 *                 cdata?: scalar|Param|null, // Default: true
 *             },
 *             array_collection?: array{
 *                 initialize_excluded?: bool|Param, // Default: false
 *             },
 *             symfony_uid?: array{
 *                 default_format?: scalar|Param|null, // Default: "canonical"
 *                 cdata?: scalar|Param|null, // Default: true
 *             },
 *         },
 *         subscribers?: array{
 *             doctrine_proxy?: array{
 *                 initialize_excluded?: bool|Param, // Default: false
 *                 initialize_virtual_types?: bool|Param, // Default: false
 *             },
 *         },
 *         object_constructors?: array{
 *             doctrine?: bool|array{
 *                 enabled?: bool|Param, // Default: true
 *                 fallback_strategy?: "null"|"exception"|"fallback"|Param, // Default: "null"
 *             },
 *         },
 *         property_naming?: string|array{
 *             id?: scalar|Param|null,
 *             separator?: scalar|Param|null, // Default: "_"
 *             lower_case?: bool|Param, // Default: true
 *         },
 *         expression_evaluator?: string|array{
 *             id?: scalar|Param|null, // Default: "jms_serializer.expression_evaluator"
 *         },
 *         metadata?: array{
 *             warmup?: array{
 *                 paths?: array{
 *                     included?: list<scalar|Param|null>,
 *                     excluded?: list<scalar|Param|null>,
 *                 },
 *             },
 *             cache?: scalar|Param|null, // Default: "file"
 *             debug?: bool|Param, // Default: true
 *             file_cache?: array{
 *                 dir?: scalar|Param|null, // Default: null
 *             },
 *             include_interfaces?: bool|Param, // Default: false
 *             auto_detection?: bool|Param, // Default: true
 *             infer_types_from_doc_block?: bool|Param, // Default: false
 *             infer_types_from_doctrine_metadata?: bool|Param, // Infers type information from Doctrine metadata if no explicit type has been defined for a property. // Default: true
 *             directories?: array<string, array{ // Default: []
 *                 path?: scalar|Param|null,
 *                 namespace_prefix?: scalar|Param|null, // Default: ""
 *             }>,
 *         },
 *         visitors?: array{
 *             json_serialization?: array{
 *                 depth?: scalar|Param|null,
 *                 options?: scalar|Param|null, // Default: 1024
 *             },
 *             json_deserialization?: array{
 *                 options?: scalar|Param|null, // Default: 0
 *                 strict?: bool|Param, // Default: false
 *             },
 *             xml_serialization?: array{
 *                 version?: scalar|Param|null,
 *                 encoding?: scalar|Param|null,
 *                 format_output?: bool|Param, // Default: false
 *                 default_root_name?: scalar|Param|null,
 *                 default_root_ns?: scalar|Param|null, // Default: ""
 *             },
 *             xml_deserialization?: array{
 *                 doctype_whitelist?: list<scalar|Param|null>,
 *                 external_entities?: bool|Param, // Default: false
 *                 options?: scalar|Param|null, // Default: 0
 *             },
 *         },
 *         default_context?: array{
 *             serialization?: string|array{
 *                 id?: scalar|Param|null,
 *                 serialize_null?: scalar|Param|null, // Flag if null values should be serialized
 *                 enable_max_depth_checks?: scalar|Param|null, // Flag to enable the max-depth exclusion strategy
 *                 attributes?: array<string, scalar|Param|null>,
 *                 groups?: list<scalar|Param|null>,
 *                 version?: scalar|Param|null, // Application version to use in exclusion strategies
 *             },
 *             deserialization?: string|array{
 *                 id?: scalar|Param|null,
 *                 serialize_null?: scalar|Param|null, // Flag if null values should be serialized
 *                 enable_max_depth_checks?: scalar|Param|null, // Flag to enable the max-depth exclusion strategy
 *                 attributes?: array<string, scalar|Param|null>,
 *                 groups?: list<scalar|Param|null>,
 *                 version?: scalar|Param|null, // Application version to use in exclusion strategies
 *             },
 *         },
 *     }>,
 * }
 * @psalm-type FosHttpCacheConfig = array{
 *     generate_url_type?: "auto"|1|0|3|2|Param, // Set what URLs to generate on CacheManager::invalidate/refresh and InvalidationListener. Auto tries to guess the right mode based on your proxy client.
 *     cacheable?: array{
 *         response?: array{
 *             additional_status?: list<scalar|Param|null>,
 *             expression?: scalar|Param|null, // Expression to decide whether response is cacheable. Replaces the default status codes. // Default: null
 *         },
 *     },
 *     cache_control?: array{
 *         defaults?: array{
 *             overwrite?: bool|Param, // Whether to overwrite existing cache headers // Default: false
 *         },
 *         ttl_header?: scalar|Param|null, // Specify the header name to use with the cache_control.reverse_proxy_ttl setting // Default: "X-Reverse-Proxy-TTL"
 *         rules?: list<array{ // Default: []
 *             match?: array{
 *                 path?: scalar|Param|null, // Request path. // Default: null
 *                 query_string?: scalar|Param|null, // Request query string. // Default: null
 *                 host?: scalar|Param|null, // Request host name. // Default: null
 *                 methods?: string|array<string, scalar|Param|null>,
 *                 ips?: string|array<string, scalar|Param|null>,
 *                 attributes?: array<string, scalar|Param|null>,
 *                 additional_response_status?: list<scalar|Param|null>,
 *                 match_response?: scalar|Param|null, // Expression to decide whether response should be matched. Replaces cacheable configuration. // Default: null
 *                 expression_language?: scalar|Param|null, // Service name of a custom ExpressionLanguage to use.
 *             },
 *             headers?: array{
 *                 overwrite?: "default"|true|false|Param, // Whether to overwrite cache headers for this rule, defaults to the cache_control.defaults.overwrite setting // Default: "default"
 *                 cache_control?: array{ // Add the specified cache control directives.
 *                     max_age?: scalar|Param|null,
 *                     s_maxage?: scalar|Param|null,
 *                     private?: bool|Param,
 *                     public?: bool|Param,
 *                     must_revalidate?: bool|Param,
 *                     proxy_revalidate?: bool|Param,
 *                     no_transform?: bool|Param,
 *                     no_cache?: bool|Param,
 *                     no_store?: bool|Param,
 *                     stale_if_error?: scalar|Param|null,
 *                     stale_while_revalidate?: scalar|Param|null,
 *                 },
 *                 etag?: "weak"|"strong"|false|Param, // Set a simple ETag which is just the md5 hash of the response body. You can specify which type of ETag you want by passing "strong" or "weak". // Default: false
 *                 last_modified?: scalar|Param|null, // Set a default last modified timestamp if none is set yet. Value must be parseable by DateTime
 *                 reverse_proxy_ttl?: scalar|Param|null, // Specify a custom time to live in seconds for your caching proxy. This value is sent in the custom header configured in cache_control.ttl_header. // Default: null
 *                 vary?: string|list<scalar|Param|null>,
 *             },
 *         }>,
 *     },
 *     proxy_client?: array{
 *         default?: "varnish"|"nginx"|"symfony"|"cloudflare"|"cloudfront"|"fastly"|"noop"|Param, // If you configure more than one proxy client, you need to specify which client is the default.
 *         varnish?: array{
 *             tags_header?: scalar|Param|null, // HTTP header to use when sending tag invalidation requests to Varnish
 *             header_length?: scalar|Param|null, // Maximum header length when invalidating tags. If there are more tags to invalidate than fit into the header, the invalidation request is split into several requests.
 *             default_ban_headers?: array<string, scalar|Param|null>,
 *             tag_mode?: "ban"|"purgekeys"|Param, // If you can enable the xkey module in Varnish, use the purgekeys mode for more efficient tag handling // Default: "ban"
 *             http?: array{
 *                 servers?: array<string, scalar|Param|null>,
 *                 servers_from_jsonenv?: mixed, // Addresses of the hosts the caching proxy is running on (env var that contains a json array as a string). The values may be hostnames or ips, and with :port if not the default port 80.
 *                 base_url?: scalar|Param|null, // Default host name and optional path for path based invalidation. // Default: null
 *                 http_client?: scalar|Param|null, // Httplug async client service name to use for sending the requests. // Default: null
 *                 request_factory?: scalar|Param|null, // Service name of PSR-17 message factory. // Default: null
 *                 stream_factory?: scalar|Param|null, // Service name of PSR-17 stream factory. // Default: null
 *             },
 *         },
 *         nginx?: array{
 *             purge_location?: scalar|Param|null, // Path to trigger the purge on Nginx for different location purge. // Default: false
 *             http?: array{
 *                 servers?: array<string, scalar|Param|null>,
 *                 servers_from_jsonenv?: mixed, // Addresses of the hosts the caching proxy is running on (env var that contains a json array as a string). The values may be hostnames or ips, and with :port if not the default port 80.
 *                 base_url?: scalar|Param|null, // Default host name and optional path for path based invalidation. // Default: null
 *                 http_client?: scalar|Param|null, // Httplug async client service name to use for sending the requests. // Default: null
 *                 request_factory?: scalar|Param|null, // Service name of PSR-17 message factory. // Default: null
 *                 stream_factory?: scalar|Param|null, // Service name of PSR-17 stream factory. // Default: null
 *             },
 *         },
 *         symfony?: array{
 *             tags_header?: scalar|Param|null, // HTTP header to use when sending tag invalidation requests to Symfony HttpCache // Default: "X-Cache-Tags"
 *             tags_method?: scalar|Param|null, // HTTP method for sending tag invalidation requests to Symfony HttpCache // Default: "PURGETAGS"
 *             header_length?: scalar|Param|null, // Maximum header length when invalidating tags. If there are more tags to invalidate than fit into the header, the invalidation request is split into several requests.
 *             purge_method?: scalar|Param|null, // HTTP method to use when sending purge requests to Symfony HttpCache // Default: "PURGE"
 *             use_kernel_dispatcher?: bool|Param, // Dispatches invalidation requests to the kernel directly instead of executing real HTTP requests. Requires special kernel setup! Refer to the documentation for more information. // Default: false
 *             http?: array{
 *                 servers?: array<string, scalar|Param|null>,
 *                 servers_from_jsonenv?: mixed, // Addresses of the hosts the caching proxy is running on (env var that contains a json array as a string). The values may be hostnames or ips, and with :port if not the default port 80.
 *                 base_url?: scalar|Param|null, // Default host name and optional path for path based invalidation. // Default: null
 *                 http_client?: scalar|Param|null, // Httplug async client service name to use for sending the requests. // Default: null
 *                 request_factory?: scalar|Param|null, // Service name of PSR-17 message factory. // Default: null
 *                 stream_factory?: scalar|Param|null, // Service name of PSR-17 stream factory. // Default: null
 *             },
 *         },
 *         cloudflare?: array{
 *             authentication_token?: scalar|Param|null, // API authorization token, requires Zone.Cache Purge permissions
 *             zone_identifier?: scalar|Param|null, // Identifier for your Cloudflare zone you want to purge the cache for
 *             http?: array{
 *                 servers?: array<string, scalar|Param|null>,
 *                 http_client?: scalar|Param|null, // Httplug async client service name to use for sending the requests. // Default: null
 *             },
 *         },
 *         cloudfront?: array{ // Configure a client to interact with AWS cloudfront. You need to install jean-beru/fos-http-cache-cloudfront to work with cloudfront
 *             distribution_id?: scalar|Param|null, // Identifier for your CloudFront distribution you want to purge the cache for
 *             client?: scalar|Param|null, // AsyncAws\CloudFront\CloudFrontClient client to use // Default: null
 *             configuration?: mixed, // Client configuration from https://async-aws.com/configuration.html // Default: []
 *         },
 *         fastly?: array{ // Configure a client to interact with Fastly.
 *             service_identifier?: scalar|Param|null, // Identifier for your Fastly service account.
 *             authentication_token?: scalar|Param|null, // User token for authentication against Fastly APIs.
 *             soft_purge?: scalar|Param|null, // Boolean for doing soft purges or not on tag & URL purging. Soft purges expires the cache unlike hard purge (removal), and allow grace/stale handling within Fastly VCL. // Default: true
 *             http?: array{
 *                 servers?: array<string, scalar|Param|null>,
 *                 base_url?: scalar|Param|null, // Default host name and optional path for path based invalidation. // Default: "service"
 *                 http_client?: scalar|Param|null, // Httplug async client service name to use for sending the requests. // Default: null
 *             },
 *         },
 *         noop?: bool|Param,
 *     },
 *     cache_manager?: array{ // Configure the cache manager. Needs a proxy_client to be configured.
 *         enabled?: true|false|"auto"|Param, // Allows to disable the invalidation manager. Enabled by default if you configure a proxy client. // Default: "auto"
 *         custom_proxy_client?: scalar|Param|null, // Service name of a custom proxy client to use. With a custom client, generate_url_type defaults to ABSOLUTE_URL and tag support needs to be explicitly enabled. If no custom proxy client is specified, the first proxy client you configured is used.
 *         generate_url_type?: "auto"|1|0|3|2|Param, // Deprecated: Configure the url type on top level to also have it apply to the InvalidationListener in addition to the CacheManager // Set what URLs to generate on invalidate/refresh Route. Auto tries to guess the right mode based on your proxy client. // Default: "auto"
 *     },
 *     tags?: array{
 *         enabled?: true|false|"auto"|Param, // Allows to disable tag support. Enabled by default if you configured the cache manager and have a proxy client that supports tagging. // Default: "auto"
 *         strict?: bool|Param, // Default: false
 *         expression_language?: scalar|Param|null, // Service name of a custom ExpressionLanguage to use. // Default: null
 *         response_header?: scalar|Param|null, // HTTP header that contains cache tags. Defaults to xkey-softpurge for Varnish xkey or X-Cache-Tags otherwise // Default: null
 *         separator?: scalar|Param|null, // Character(s) to use to separate multiple tags. Defaults to " " for Varnish xkey or "," otherwise // Default: null
 *         max_header_value_length?: scalar|Param|null, // If configured the tag header value will be split into multiple response headers of the same name (see "response_header" configuration key) that all do not exceed the configured "max_header_value_length" (recommended is 4KB = 4096) - configure in bytes. // Default: null
 *         rules?: list<array{ // Default: []
 *             match?: array{
 *                 path?: scalar|Param|null, // Request path. // Default: null
 *                 query_string?: scalar|Param|null, // Request query string. // Default: null
 *                 host?: scalar|Param|null, // Request host name. // Default: null
 *                 methods?: string|array<string, scalar|Param|null>,
 *                 ips?: string|array<string, scalar|Param|null>,
 *                 attributes?: array<string, scalar|Param|null>,
 *             },
 *             tags?: list<scalar|Param|null>,
 *             tag_expressions?: list<scalar|Param|null>,
 *         }>,
 *     },
 *     invalidation?: array{
 *         enabled?: true|false|"auto"|Param, // Allows to disable the listener for invalidation. Enabled by default if the cache manager is configured. When disabled, the cache manager is no longer flushed automatically. // Default: "auto"
 *         expression_language?: scalar|Param|null, // Service name of a custom ExpressionLanguage to use. // Default: null
 *         rules?: list<array{ // Default: []
 *             match?: array{
 *                 path?: scalar|Param|null, // Request path. // Default: null
 *                 query_string?: scalar|Param|null, // Request query string. // Default: null
 *                 host?: scalar|Param|null, // Request host name. // Default: null
 *                 methods?: string|array<string, scalar|Param|null>,
 *                 ips?: string|array<string, scalar|Param|null>,
 *                 attributes?: array<string, scalar|Param|null>,
 *             },
 *             routes?: array<string, array{ // Default: []
 *                 ignore_extra_params?: bool|Param, // Default: true
 *             }>,
 *         }>,
 *     },
 *     user_context?: bool|array{ // Listener that returns the request for the user context hash as early as possible.
 *         enabled?: bool|Param, // Default: false
 *         match?: array{
 *             matcher_service?: scalar|Param|null, // Service id of a request matcher that tells whether the request is a context hash request. // Default: "fos_http_cache.user_context.request_matcher"
 *             accept?: scalar|Param|null, // Specify the accept HTTP header used for context hash requests. // Default: "application/vnd.fos.user-context-hash"
 *             method?: scalar|Param|null, // Specify the HTTP method used for context hash requests. // Default: null
 *         },
 *         hash_cache_ttl?: scalar|Param|null, // Cache the response for the hash for the specified number of seconds. Setting this to 0 will not cache those responses at all. // Default: 0
 *         always_vary_on_context_hash?: bool|Param, // Whether to always add the user context hash header name in the response Vary header. // Default: true
 *         user_identifier_headers?: list<scalar|Param|null>,
 *         session_name_prefix?: scalar|Param|null, // Prefix for session cookies. Must match your PHP session configuration. Set to false to ignore the session in user context. // Default: false
 *         user_hash_header?: scalar|Param|null, // Name of the header that contains the hash information for the context. // Default: "X-User-Context-Hash"
 *         role_provider?: bool|Param, // Whether to enable a provider that automatically adds all roles of the current user to the context. // Default: false
 *         logout_handler?: bool|array{
 *             enabled?: true|false|"auto"|Param, // Whether to enable the user context logout handler. // Default: "auto"
 *         },
 *     },
 *     flash_message?: bool|array{ // Activate the flash message listener that puts flash messages into a cookie.
 *         enabled?: bool|Param, // Default: false
 *         name?: scalar|Param|null, // Name of the cookie to set for flashes. // Default: "flashes"
 *         path?: scalar|Param|null, // Cookie path validity. // Default: "/"
 *         host?: scalar|Param|null, // Cookie host name validity. // Default: null
 *         secure?: scalar|Param|null, // Whether the cookie should only be transmitted over a secure HTTPS connection from the client. // Default: false
 *     },
 *     test?: array{
 *         cache_header?: scalar|Param|null, // HTTP cache hit/miss header // Default: "X-Cache"
 *         proxy_server?: array{ // Configure how caching proxy will be run in your tests
 *             default?: "varnish"|"nginx"|Param, // If you configure more than one proxy server, specify which client is the default.
 *             varnish?: array{
 *                 config_file?: scalar|Param|null,
 *                 binary?: scalar|Param|null, // Default: "varnishd"
 *                 port?: int|Param, // Default: 6181
 *                 ip?: scalar|Param|null, // Default: "127.0.0.1"
 *             },
 *             nginx?: array{
 *                 config_file?: scalar|Param|null,
 *                 binary?: scalar|Param|null, // Default: "nginx"
 *                 port?: int|Param, // Default: 8080
 *                 ip?: scalar|Param|null, // Default: "127.0.0.1"
 *             },
 *         },
 *     },
 *     debug?: bool|array{
 *         enabled?: bool|Param, // Whether to send a debug header with the response to trigger a caching proxy to send debug information. If not set, defaults to kernel.debug. // Default: true
 *         header?: scalar|Param|null, // The header to send if debug is true. // Default: "X-Cache-Debug"
 *     },
 * }
 * @psalm-type SuluAdminConfig = array{
 *     name?: scalar|Param|null, // Default: "Sulu Admin"
 *     email?: scalar|Param|null, // Default: ""
 *     user_data_service?: scalar|Param|null, // Default: "sulu_security.user_manager"
 *     resources?: array<string, array{ // Default: []
 *         routes?: array{
 *             list?: scalar|Param|null,
 *             detail?: scalar|Param|null,
 *         },
 *         views?: array{
 *             list?: scalar|Param|null,
 *             detail?: scalar|Param|null,
 *         },
 *         security_context?: scalar|Param|null,
 *         security_class?: scalar|Param|null,
 *     }>,
 *     collaboration?: array{
 *         enabled?: bool|Param, // Default: false
 *         interval?: scalar|Param|null, // The seconds between the keep alive messages for the collaboration feature // Default: 20
 *         threshold?: scalar|Param|null, // The time after which a collabaration without keep alive signal is terminated // Default: 60
 *     },
 *     forms?: array{
 *         directories?: list<scalar|Param|null>,
 *     },
 *     templates?: array<string, array{ // Default: []
 *         default_type?: scalar|Param|null, // Default: null
 *         directories?: array<string, scalar|Param|null>,
 *     }>,
 *     lists?: array{
 *         directories?: list<scalar|Param|null>,
 *     },
 *     icon_sets?: array<string, scalar|Param|null>,
 *     field_type_options?: array{
 *         selection?: array<string, array{ // Default: []
 *             default_type?: scalar|Param|null,
 *             resource_key?: scalar|Param|null,
 *             view?: array{
 *                 name?: scalar|Param|null,
 *                 result_to_view?: list<scalar|Param|null>,
 *             },
 *             types?: array{
 *                 auto_complete?: array{
 *                     allow_add?: bool|Param, // Default: false
 *                     id_property?: scalar|Param|null, // Default: "id"
 *                     display_property?: scalar|Param|null,
 *                     filter_parameter?: scalar|Param|null,
 *                     search_properties?: list<scalar|Param|null>,
 *                 },
 *                 list?: array{
 *                     adapter?: scalar|Param|null,
 *                     list_key?: scalar|Param|null,
 *                 },
 *                 list_overlay?: array{
 *                     adapter?: scalar|Param|null,
 *                     list_key?: scalar|Param|null,
 *                     display_properties?: list<scalar|Param|null>,
 *                     icon?: scalar|Param|null,
 *                     label?: scalar|Param|null,
 *                     overlay_title?: scalar|Param|null,
 *                 },
 *             },
 *         }>,
 *         single_selection?: array<string, array{ // Default: []
 *             default_type?: scalar|Param|null,
 *             resource_key?: scalar|Param|null,
 *             view?: array{
 *                 name?: scalar|Param|null,
 *                 result_to_view?: list<scalar|Param|null>,
 *             },
 *             types?: array{
 *                 auto_complete?: array{
 *                     display_property?: scalar|Param|null,
 *                     search_properties?: list<scalar|Param|null>,
 *                 },
 *                 list_overlay?: array{
 *                     adapter?: scalar|Param|null,
 *                     detail_options?: list<scalar|Param|null>,
 *                     list_key?: scalar|Param|null,
 *                     display_properties?: list<scalar|Param|null>,
 *                     icon?: scalar|Param|null,
 *                     empty_text?: scalar|Param|null,
 *                     overlay_title?: scalar|Param|null,
 *                 },
 *                 single_select?: array{
 *                     display_property?: scalar|Param|null,
 *                     id_property?: scalar|Param|null,
 *                     overlay_title?: scalar|Param|null,
 *                 },
 *             },
 *         }>,
 *     },
 * }
 * @psalm-type SuluContactConfig = array{
 *     defaults?: array{
 *         phoneType?: scalar|Param|null, // Default: "1"
 *         phoneTypeMobile?: scalar|Param|null, // Default: "3"
 *         phoneTypeIsdn?: scalar|Param|null, // Default: "1"
 *         emailType?: scalar|Param|null, // Default: "1"
 *         addressType?: scalar|Param|null, // Default: "1"
 *         urlType?: scalar|Param|null, // Default: "1"
 *         faxType?: scalar|Param|null, // Default: "1"
 *         socialMediaProfileType?: scalar|Param|null, // Default: "1"
 *         country?: scalar|Param|null, // Default: "AT"
 *     },
 *     form?: array{
 *         contact?: array{
 *             category_root?: scalar|Param|null, // Default: null
 *         },
 *         account?: array{
 *             category_root?: scalar|Param|null, // Default: null
 *         },
 *     },
 *     form_of_address?: array<string, array{ // Default: {"male":{"id":0,"name":"male","translation":"contact.contacts.formOfAddress.male"},"female":{"id":1,"name":"female","translation":"contact.contacts.formOfAddress.female"}}
 *         id?: scalar|Param|null,
 *         name?: scalar|Param|null,
 *         translation?: scalar|Param|null,
 *     }>,
 *     objects?: array{
 *         contact?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\ContactBundle\\Entity\\Contact"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\ContactBundle\\Entity\\ContactRepository"
 *         },
 *         account?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\ContactBundle\\Entity\\Account"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\ContactBundle\\Entity\\AccountRepository"
 *         },
 *     },
 * }
 * @psalm-type SuluMediaConfig = array{
 *     adapter?: scalar|Param|null, // Default: "auto"
 *     image_format_files?: list<scalar|Param|null>,
 *     system_collections?: array<string, array{ // Default: []
 *         meta_title?: list<scalar|Param|null>,
 *         collections?: array<string, array{ // Default: []
 *             meta_title?: list<scalar|Param|null>,
 *         }>,
 *     }>,
 *     ghost_script?: array{
 *         path?: scalar|Param|null, // Default: "gs"
 *     },
 *     upload?: array{
 *         max_filesize?: int|Param, // Default: 256
 *         blocked_file_types?: list<scalar|Param|null>,
 *     },
 *     format_manager?: array{
 *         response_headers?: list<scalar|Param|null>,
 *         default_imagine_options?: list<scalar|Param|null>,
 *         mime_types?: list<scalar|Param|null>,
 *         types?: list<array{ // Default: [{"type":"document","mimeTypes":["*"]},{"type":"image","mimeTypes":["image/*"]},{"type":"video","mimeTypes":["video/*"]},{"type":"audio","mimeTypes":["audio/*"]}]
 *             type?: scalar|Param|null,
 *             mimeTypes?: list<scalar|Param|null>,
 *         }>,
 *     },
 *     format_cache?: array{
 *         path?: scalar|Param|null, // Default: "%kernel.project_dir%/public/uploads/media"
 *         save_image?: bool|Param, // Default: true
 *         segments?: scalar|Param|null, // Default: 10
 *     },
 *     disposition_type?: array{
 *         default?: "inline"|"attachment"|Param, // Default: "attachment"
 *         mime_types_inline?: list<scalar|Param|null>,
 *         mime_types_attachment?: list<scalar|Param|null>,
 *     },
 *     routing?: array{
 *         media_proxy_path?: scalar|Param|null, // Default: "/uploads/media/{slug}"
 *         media_download_path?: scalar|Param|null, // Default: "/media/{id}/download/{slug}"
 *         media_download_path_admin?: scalar|Param|null, // Default: "/admin/media/{id}/download/{slug}"
 *     },
 *     ffmpeg?: array{
 *         ffmpeg_binary?: scalar|Param|null,
 *         ffprobe_binary?: scalar|Param|null,
 *         binary_timeout?: scalar|Param|null, // Default: 60
 *         threads_count?: scalar|Param|null, // Default: 4
 *     },
 *     objects?: array{
 *         media?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\MediaBundle\\Entity\\Media"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\MediaBundle\\Entity\\MediaRepository"
 *         },
 *     },
 *     storage?: array{
 *         flysystem_service?: scalar|Param|null, // Service to use for storing media. // Default: "default.storage"
 *         segments?: int|Param, // Default: 10
 *     },
 * }
 * @psalm-type SuluSecurityConfig = array{
 *     system?: scalar|Param|null, // Deprecated: The system option is deprecated and will be removed. Setting this option in the admin context will break the permissions registered by the bundles. // Default: "Sulu"
 *     checker?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *     },
 *     password_policy?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         pattern?: scalar|Param|null, // Default: ".{8,}"
 *         info_translation_key?: scalar|Param|null, // Default: "sulu_security.password_policy_information"
 *     },
 *     single_sign_on?: array{
 *         providers?: array<string, array{ // Default: []
 *             dsn?: scalar|Param|null,
 *             default_role_key?: scalar|Param|null,
 *         }>,
 *     },
 *     two_factor?: array{
 *         email?: array{
 *             template?: scalar|Param|null, // Default: "@SuluSecurity/mail_templates/two_factor"
 *         },
 *         force?: bool|array{
 *             enabled?: bool|Param, // Default: false
 *             pattern?: scalar|Param|null, // Default: "(.+)"
 *         },
 *     },
 *     reset_password?: array{
 *         mail?: array{
 *             token_send_limit?: int|Param, // Default: 3
 *             sender?: scalar|Param|null, // Default: ""
 *             subject?: scalar|Param|null, // Default: "sulu_security.reset_mail_subject"
 *             template?: scalar|Param|null, // Default: "@SuluSecurity/mail_templates/reset_password.html.twig"
 *             translation_domain?: scalar|Param|null, // Default: "admin"
 *         },
 *     },
 *     objects?: array{
 *         user?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\User"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\UserRepository"
 *         },
 *         role?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\Role"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\RoleRepository"
 *         },
 *         role_setting?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\RoleSetting"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\RoleSettingRepository"
 *         },
 *         access_control?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\AccessControl"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\SecurityBundle\\Entity\\AccessControlRepository"
 *         },
 *     },
 * }
 * @psalm-type SuluCategoryConfig = array{
 *     objects?: array{
 *         category?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\CategoryBundle\\Entity\\Category"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\CategoryBundle\\Entity\\CategoryRepository"
 *         },
 *         category_translation?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\CategoryBundle\\Entity\\CategoryTranslation"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\CategoryBundle\\Entity\\CategoryTranslationRepository"
 *         },
 *         keyword?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\CategoryBundle\\Entity\\Keyword"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\CategoryBundle\\Entity\\KeywordRepository"
 *         },
 *     },
 * }
 * @psalm-type SuluTagConfig = array{
 *     objects?: array{
 *         tag?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\TagBundle\\Entity\\Tag"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\TagBundle\\Entity\\TagRepository"
 *         },
 *     },
 * }
 * @psalm-type SuluWebsiteConfig = array{
 *     analytics?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     segments?: array{
 *         switch_url?: scalar|Param|null, // Default: "/_sulu_segment_switch"
 *         cookie?: scalar|Param|null, // Default: "_ss"
 *         header?: scalar|Param|null, // Default: "X-Sulu-Segment"
 *     },
 *     twig?: array{
 *         navigation?: array{
 *             cache_lifetime?: scalar|Param|null, // Default: 1
 *         },
 *         content?: array{
 *             cache_lifetime?: scalar|Param|null, // Default: 1
 *         },
 *         sitemap?: array{
 *             cache_lifetime?: scalar|Param|null, // Default: 3600
 *         },
 *     },
 *     sitemap?: array{
 *         dump_dir?: scalar|Param|null, // Default: "%sulu.cache_dir%/sitemaps"
 *     },
 *     default_locale?: array{
 *         provider_service_id?: scalar|Param|null, // Default: "sulu_website.default_locale.portal_provider"
 *     },
 *     objects?: array{
 *         analytics?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\WebsiteBundle\\Entity\\Analytics"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\WebsiteBundle\\Entity\\AnalyticsRepository"
 *         },
 *     },
 * }
 * @psalm-type SuluLocationConfig = array{
 *     geolocator?: "nominatim"|"google"|"mapquest"|Param, // Default: "nominatim"
 *     geolocators?: array{
 *         nominatim?: array{
 *             api_key?: scalar|Param|null, // Default: ""
 *             endpoint?: scalar|Param|null, // Default: "https://nominatim.openstreetmap.org/search"
 *         },
 *         google?: array{
 *             api_key?: scalar|Param|null, // Default: ""
 *         },
 *         mapquest?: array{
 *             api_key?: scalar|Param|null, // Default: ""
 *             endpoint?: scalar|Param|null, // Default: "https://www.mapquestapi.com/geocoding/v1/address"
 *         },
 *     },
 * }
 * @psalm-type SuluHttpCacheConfig = array{
 *     tags?: bool|array{
 *         enabled?: bool|Param, // Default: true
 *     },
 *     cache?: array{
 *         max_age?: int|Param, // Default: 240
 *         shared_max_age?: int|Param, // Default: 240
 *     },
 *     proxy_client?: array{
 *         noop?: bool|Param,
 *         symfony?: bool|array{
 *             enabled?: bool|Param, // Default: false
 *             servers?: string|array<string, scalar|Param|null>,
 *             base_url?: scalar|Param|null, // Default host name and optional path for path based invalidation. // Default: null
 *         },
 *         varnish?: bool|array{
 *             enabled?: bool|Param, // Default: false
 *             servers?: string|array<string, scalar|Param|null>,
 *             base_url?: scalar|Param|null, // Default host name and optional path for path based invalidation. // Default: null
 *             tag_mode?: "ban"|"purgekeys"|Param, // Use the purgekeys mode for more efficient tag handling, if your Varnish server supports the xkey module // Default: "ban"
 *             tags_header?: scalar|Param|null, // HTTP header to use when sending tag invalidation requests to Varnish
 *         },
 *     },
 *     debug?: bool|array{
 *         enabled?: bool|Param, // Whether to send a debug header with the response to trigger a caching proxy to send debug information. If not set, defaults to kernel.debug. // Default: true
 *     },
 * }
 * @psalm-type SuluActivityConfig = array{
 *     storage?: array{
 *         adapter?: scalar|Param|null, // Default: "doctrine"
 *         persist_payload?: bool|Param, // Default: false
 *     },
 *     objects?: array{
 *         activity?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\ActivityBundle\\Domain\\Model\\Activity"
 *         },
 *     },
 * }
 * @psalm-type SuluTrashConfig = array{
 *     objects?: array{
 *         trash_item?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\TrashBundle\\Domain\\Model\\TrashItem"
 *         },
 *     },
 * }
 * @psalm-type SuluReferenceConfig = array{
 *     objects?: array{
 *         reference?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\ReferenceBundle\\Domain\\Model\\Reference"
 *         },
 *     },
 * }
 * @psalm-type SuluCustomUrlConfig = array{
 *     objects?: array{
 *         custom_url?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\CustomUrl\\Domain\\Model\\CustomUrl"
 *         },
 *         custom_url_route?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\CustomUrl\\Domain\\Model\\CustomUrlRoute"
 *         },
 *     },
 * }
 * @psalm-type SuluMarkupConfig = array{
 *     link_tag?: array{
 *         provider_attribute?: scalar|Param|null, // Default: null
 *     },
 * }
 * @psalm-type SuluAudienceTargetingConfig = array{
 *     headers?: array{
 *         target_group?: scalar|Param|null, // Default: "X-Sulu-Target-Group"
 *         url?: scalar|Param|null, // Default: "X-Forwarded-URL"
 *     },
 *     url?: scalar|Param|null, // Default: "/_sulu_target_group"
 *     cookies?: array{
 *         target_group?: scalar|Param|null, // Default: "_svtg"
 *         session?: scalar|Param|null, // Default: "_svs"
 *     },
 *     hit?: array{
 *         url?: scalar|Param|null, // Default: "/_sulu_target_group_hit"
 *         headers?: array{
 *             referrer?: scalar|Param|null, // Default: "X-Forwarded-Referrer"
 *             uuid?: scalar|Param|null, // Default: "X-Forwarded-UUID"
 *         },
 *     },
 *     number_of_priorities?: scalar|Param|null, // Default: 5
 *     objects?: array{
 *         target_group?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroup"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroupRepository"
 *         },
 *         target_group_condition?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroupCondition"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroupConditionRepository"
 *         },
 *         target_group_rule?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroupRule"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroupRuleRepository"
 *         },
 *         target_group_webspace?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroupWebspace"
 *             repository?: scalar|Param|null, // Default: "Sulu\\Bundle\\AudienceTargetingBundle\\Entity\\TargetGroupWebspaceRepository"
 *         },
 *     },
 * }
 * @psalm-type MassiveBuildConfig = array{
 *     command_class?: scalar|Param|null, // Default: "Massive\\Bundle\\BuildBundle\\Command\\BuildCommand"
 *     targets?: array<string, array{ // Default: []
 *         dependencies?: array<string, list<scalar|Param|null>>,
 *     }>,
 * }
 * @psalm-type WebProfilerConfig = array{
 *     toolbar?: bool|array{ // Profiler toolbar configuration
 *         enabled?: bool|Param, // Default: false
 *         ajax_replace?: bool|Param, // Replace toolbar on AJAX requests // Default: false
 *     },
 *     intercept_redirects?: bool|Param, // Default: false
 *     excluded_ajax_paths?: scalar|Param|null, // Default: "^/((index|app(_[\\w]+)?)\\.php/)?_wdt"
 * }
 * @psalm-type SuluTestConfig = array{
 *     enable_test_user_provider?: bool|Param, // Default: false
 * }
 * @psalm-type DebugConfig = array{
 *     max_items?: int|Param, // Max number of displayed items past the first level, -1 means no limit. // Default: 2500
 *     min_depth?: int|Param, // Minimum tree depth to clone all the items, 1 is default. // Default: 1
 *     max_string_length?: int|Param, // Max length of displayed strings, -1 means no limit. // Default: -1
 *     dump_destination?: scalar|Param|null, // A stream URL where dumps should be written to. // Default: null
 *     theme?: "dark"|"light"|Param, // Changes the color of the dump() output when rendered directly on the templating. "dark" (default) or "light". // Default: "dark"
 * }
 * @psalm-type SecurityConfig = array{
 *     access_denied_url?: scalar|Param|null, // Default: null
 *     session_fixation_strategy?: "none"|"migrate"|"invalidate"|Param, // Default: "migrate"
 *     expose_security_errors?: \Symfony\Component\Security\Http\Authentication\ExposeSecurityLevel::None|\Symfony\Component\Security\Http\Authentication\ExposeSecurityLevel::AccountStatus|\Symfony\Component\Security\Http\Authentication\ExposeSecurityLevel::All|Param, // Default: "none"
 *     erase_credentials?: bool|Param, // Deprecated: Setting the "security.erase_credentials.erase_credentials" configuration option is deprecated. It will be removed in Symfony 9.0, as the "eraseCredentials()" method was removed in Symfony 8.0. // Default: true
 *     access_decision_manager?: array{
 *         strategy?: "affirmative"|"consensus"|"unanimous"|"priority"|Param,
 *         service?: scalar|Param|null,
 *         strategy_service?: scalar|Param|null,
 *         allow_if_all_abstain?: bool|Param, // Default: false
 *         allow_if_equal_granted_denied?: bool|Param, // Default: true
 *     },
 *     password_hashers?: array<string, string|array{ // Default: []
 *         algorithm?: scalar|Param|null,
 *         migrate_from?: string|list<scalar|Param|null>,
 *         hash_algorithm?: scalar|Param|null, // Name of hashing algorithm for PBKDF2 (i.e. sha256, sha512, etc..) See hash_algos() for a list of supported algorithms. // Default: "sha512"
 *         key_length?: scalar|Param|null, // Default: 40
 *         ignore_case?: bool|Param, // Default: false
 *         encode_as_base64?: bool|Param, // Default: true
 *         iterations?: scalar|Param|null, // Default: 5000
 *         cost?: int|Param, // Default: null
 *         memory_cost?: scalar|Param|null, // Default: null
 *         time_cost?: scalar|Param|null, // Default: null
 *         id?: scalar|Param|null,
 *     }>,
 *     providers?: array<string, array{ // Default: []
 *         id?: scalar|Param|null,
 *         chain?: array{
 *             providers?: string|list<scalar|Param|null>,
 *         },
 *         entity?: array{
 *             class?: scalar|Param|null, // The full entity class name of your user class.
 *             property?: scalar|Param|null, // Default: null
 *             manager_name?: scalar|Param|null, // Default: null
 *         },
 *         memory?: array{
 *             users?: array<string, array{ // Default: []
 *                 password?: scalar|Param|null, // Default: null
 *                 roles?: string|list<scalar|Param|null>,
 *             }>,
 *         },
 *         ldap?: array{
 *             service?: scalar|Param|null,
 *             base_dn?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: null
 *             search_password?: scalar|Param|null, // Default: null
 *             extra_fields?: list<scalar|Param|null>,
 *             default_roles?: string|list<scalar|Param|null>,
 *             role_fetcher?: scalar|Param|null, // Default: null
 *             uid_key?: scalar|Param|null, // Default: "sAMAccountName"
 *             filter?: scalar|Param|null, // Default: "({uid_key}={user_identifier})"
 *             password_attribute?: scalar|Param|null, // Default: null
 *         },
 *     }>,
 *     firewalls?: array<string, array{ // Default: []
 *         pattern?: scalar|Param|null,
 *         host?: scalar|Param|null,
 *         methods?: string|list<scalar|Param|null>,
 *         security?: bool|Param, // Default: true
 *         user_checker?: scalar|Param|null, // The UserChecker to use when authenticating users in this firewall. // Default: "security.user_checker"
 *         request_matcher?: scalar|Param|null,
 *         access_denied_url?: scalar|Param|null,
 *         access_denied_handler?: scalar|Param|null,
 *         entry_point?: scalar|Param|null, // An enabled authenticator name or a service id that implements "Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface".
 *         provider?: scalar|Param|null,
 *         stateless?: bool|Param, // Default: false
 *         lazy?: bool|Param, // Default: false
 *         context?: scalar|Param|null,
 *         logout?: array{
 *             enable_csrf?: bool|Param|null, // Default: null
 *             csrf_token_id?: scalar|Param|null, // Default: "logout"
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_manager?: scalar|Param|null,
 *             path?: scalar|Param|null, // Default: "/logout"
 *             target?: scalar|Param|null, // Default: "/"
 *             invalidate_session?: bool|Param, // Default: true
 *             clear_site_data?: string|list<"*"|"cache"|"cookies"|"storage"|"clientHints"|"executionContexts"|"prefetchCache"|"prerenderCache"|Param>,
 *             delete_cookies?: string|array<string, array{ // Default: []
 *                 path?: scalar|Param|null, // Default: null
 *                 domain?: scalar|Param|null, // Default: null
 *                 secure?: scalar|Param|null, // Default: false
 *                 samesite?: scalar|Param|null, // Default: null
 *                 partitioned?: scalar|Param|null, // Default: false
 *             }>,
 *         },
 *         switch_user?: array{
 *             provider?: scalar|Param|null,
 *             parameter?: scalar|Param|null, // Default: "_switch_user"
 *             role?: scalar|Param|null, // Default: "ROLE_ALLOWED_TO_SWITCH"
 *             target_route?: scalar|Param|null, // Default: null
 *         },
 *         required_badges?: list<scalar|Param|null>,
 *         custom_authenticators?: list<scalar|Param|null>,
 *         login_throttling?: array{
 *             limiter?: scalar|Param|null, // A service id implementing "Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface".
 *             max_attempts?: int|Param, // Default: 5
 *             interval?: scalar|Param|null, // Default: "1 minute"
 *             lock_factory?: scalar|Param|null, // The service ID of the lock factory used by the login rate limiter (or null to disable locking). // Default: null
 *             cache_pool?: string|Param, // The cache pool to use for storing the limiter state // Default: "cache.rate_limiter"
 *             storage_service?: string|Param, // The service ID of a custom storage implementation, this precedes any configured "cache_pool" // Default: null
 *         },
 *         x509?: array{
 *             provider?: scalar|Param|null,
 *             user?: scalar|Param|null, // Default: "SSL_CLIENT_S_DN_Email"
 *             credentials?: scalar|Param|null, // Default: "SSL_CLIENT_S_DN"
 *             user_identifier?: scalar|Param|null, // Default: "emailAddress"
 *         },
 *         remote_user?: array{
 *             provider?: scalar|Param|null,
 *             user?: scalar|Param|null, // Default: "REMOTE_USER"
 *         },
 *         login_link?: array{
 *             check_route?: scalar|Param|null, // Route that will validate the login link - e.g. "app_login_link_verify".
 *             check_post_only?: scalar|Param|null, // If true, only HTTP POST requests to "check_route" will be handled by the authenticator. // Default: false
 *             signature_properties?: list<scalar|Param|null>,
 *             lifetime?: int|Param, // The lifetime of the login link in seconds. // Default: 600
 *             max_uses?: int|Param, // Max number of times a login link can be used - null means unlimited within lifetime. // Default: null
 *             used_link_cache?: scalar|Param|null, // Cache service id used to expired links of max_uses is set.
 *             success_handler?: scalar|Param|null, // A service id that implements Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface.
 *             failure_handler?: scalar|Param|null, // A service id that implements Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface.
 *             provider?: scalar|Param|null, // The user provider to load users from.
 *             secret?: scalar|Param|null, // Default: "%kernel.secret%"
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             target_path_parameter?: scalar|Param|null, // Default: "_target_path"
 *             use_referer?: bool|Param, // Default: false
 *             failure_path?: scalar|Param|null, // Default: null
 *             failure_forward?: bool|Param, // Default: false
 *             failure_path_parameter?: scalar|Param|null, // Default: "_failure_path"
 *         },
 *         form_login?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_parameter?: scalar|Param|null, // Default: "_username"
 *             password_parameter?: scalar|Param|null, // Default: "_password"
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_id?: scalar|Param|null, // Default: "authenticate"
 *             enable_csrf?: bool|Param, // Default: false
 *             post_only?: bool|Param, // Default: true
 *             form_only?: bool|Param, // Default: false
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             target_path_parameter?: scalar|Param|null, // Default: "_target_path"
 *             use_referer?: bool|Param, // Default: false
 *             failure_path?: scalar|Param|null, // Default: null
 *             failure_forward?: bool|Param, // Default: false
 *             failure_path_parameter?: scalar|Param|null, // Default: "_failure_path"
 *         },
 *         form_login_ldap?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_parameter?: scalar|Param|null, // Default: "_username"
 *             password_parameter?: scalar|Param|null, // Default: "_password"
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_id?: scalar|Param|null, // Default: "authenticate"
 *             enable_csrf?: bool|Param, // Default: false
 *             post_only?: bool|Param, // Default: true
 *             form_only?: bool|Param, // Default: false
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             target_path_parameter?: scalar|Param|null, // Default: "_target_path"
 *             use_referer?: bool|Param, // Default: false
 *             failure_path?: scalar|Param|null, // Default: null
 *             failure_forward?: bool|Param, // Default: false
 *             failure_path_parameter?: scalar|Param|null, // Default: "_failure_path"
 *             service?: scalar|Param|null, // Default: "ldap"
 *             dn_string?: scalar|Param|null, // Default: "{user_identifier}"
 *             query_string?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: ""
 *             search_password?: scalar|Param|null, // Default: ""
 *         },
 *         json_login?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_path?: scalar|Param|null, // Default: "username"
 *             password_path?: scalar|Param|null, // Default: "password"
 *         },
 *         json_login_ldap?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             check_path?: scalar|Param|null, // Default: "/login_check"
 *             use_forward?: bool|Param, // Default: false
 *             login_path?: scalar|Param|null, // Default: "/login"
 *             username_path?: scalar|Param|null, // Default: "username"
 *             password_path?: scalar|Param|null, // Default: "password"
 *             service?: scalar|Param|null, // Default: "ldap"
 *             dn_string?: scalar|Param|null, // Default: "{user_identifier}"
 *             query_string?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: ""
 *             search_password?: scalar|Param|null, // Default: ""
 *         },
 *         access_token?: array{
 *             provider?: scalar|Param|null,
 *             remember_me?: bool|Param, // Default: true
 *             success_handler?: scalar|Param|null,
 *             failure_handler?: scalar|Param|null,
 *             realm?: scalar|Param|null, // Default: null
 *             token_extractors?: string|list<scalar|Param|null>,
 *             token_handler?: string|array{
 *                 id?: scalar|Param|null,
 *                 oidc_user_info?: string|array{
 *                     base_uri?: scalar|Param|null, // Base URI of the userinfo endpoint on the OIDC server, or the OIDC server URI to use the discovery (require "discovery" to be configured).
 *                     discovery?: array{ // Enable the OIDC discovery.
 *                         cache?: array{
 *                             id?: scalar|Param|null, // Cache service id to use to cache the OIDC discovery configuration.
 *                         },
 *                     },
 *                     claim?: scalar|Param|null, // Claim which contains the user identifier (e.g. sub, email, etc.). // Default: "sub"
 *                     client?: scalar|Param|null, // HttpClient service id to use to call the OIDC server.
 *                 },
 *                 oidc?: array{
 *                     discovery?: array{ // Enable the OIDC discovery.
 *                         base_uri?: string|list<scalar|Param|null>,
 *                         cache?: array{
 *                             id?: scalar|Param|null, // Cache service id to use to cache the OIDC discovery configuration.
 *                         },
 *                         enforce_key_usage_verification?: bool|Param, // When enabled (default), only keys explicitly designated for signature (via "use":"sig" or a "key_ops" entry containing "sign"/"verify") are accepted. When disabled, keys without any usage designation are also accepted; keys explicitly restricted to encryption are still rejected. // Default: true
 *                     },
 *                     claim?: scalar|Param|null, // Claim which contains the user identifier (e.g.: sub, email..). // Default: "sub"
 *                     audience?: scalar|Param|null, // Audience set in the token, for validation purpose.
 *                     issuers?: list<scalar|Param|null>,
 *                     algorithms?: list<scalar|Param|null>,
 *                     keyset?: scalar|Param|null, // JSON-encoded JWKSet used to sign the token (must contain a list of valid public keys).
 *                     encryption?: bool|array{
 *                         enabled?: bool|Param, // Default: false
 *                         enforce?: bool|Param, // When enabled, the token shall be encrypted. // Default: false
 *                         algorithms?: list<scalar|Param|null>,
 *                         keyset?: scalar|Param|null, // JSON-encoded JWKSet used to decrypt the token (must contain a list of valid private keys).
 *                     },
 *                 },
 *                 cas?: array{
 *                     validation_url?: scalar|Param|null, // CAS server validation URL
 *                     prefix?: scalar|Param|null, // CAS prefix // Default: "cas"
 *                     http_client?: scalar|Param|null, // HTTP Client service // Default: null
 *                 },
 *                 oauth2?: scalar|Param|null,
 *             },
 *         },
 *         http_basic?: array{
 *             provider?: scalar|Param|null,
 *             realm?: scalar|Param|null, // Default: "Secured Area"
 *         },
 *         http_basic_ldap?: array{
 *             provider?: scalar|Param|null,
 *             realm?: scalar|Param|null, // Default: "Secured Area"
 *             service?: scalar|Param|null, // Default: "ldap"
 *             dn_string?: scalar|Param|null, // Default: "{user_identifier}"
 *             query_string?: scalar|Param|null,
 *             search_dn?: scalar|Param|null, // Default: ""
 *             search_password?: scalar|Param|null, // Default: ""
 *         },
 *         remember_me?: array{
 *             secret?: scalar|Param|null, // Default: "%kernel.secret%"
 *             service?: scalar|Param|null,
 *             user_providers?: string|list<scalar|Param|null>,
 *             catch_exceptions?: bool|Param, // Default: true
 *             signature_properties?: list<scalar|Param|null>,
 *             token_provider?: string|array{
 *                 service?: scalar|Param|null, // The service ID of a custom remember-me token provider.
 *                 doctrine?: bool|array{
 *                     enabled?: bool|Param, // Default: false
 *                     connection?: scalar|Param|null, // Default: null
 *                 },
 *             },
 *             token_verifier?: scalar|Param|null, // The service ID of a custom rememberme token verifier.
 *             name?: scalar|Param|null, // Default: "REMEMBERME"
 *             lifetime?: int|Param, // Default: 31536000
 *             path?: scalar|Param|null, // Default: "/"
 *             domain?: scalar|Param|null, // Default: null
 *             secure?: true|false|"auto"|Param, // Default: null
 *             httponly?: bool|Param, // Default: true
 *             samesite?: null|"lax"|"strict"|"none"|Param, // Default: "lax"
 *             always_remember_me?: bool|Param, // Default: false
 *             remember_me_parameter?: scalar|Param|null, // Default: "_remember_me"
 *         },
 *         two_factor?: array{
 *             check_path?: scalar|Param|null, // Default: "/2fa_check"
 *             post_only?: bool|Param, // Default: true
 *             auth_form_path?: scalar|Param|null, // Default: "/2fa"
 *             always_use_default_target_path?: bool|Param, // Default: false
 *             default_target_path?: scalar|Param|null, // Default: "/"
 *             success_handler?: scalar|Param|null, // Default: null
 *             failure_handler?: scalar|Param|null, // Default: null
 *             authentication_required_handler?: scalar|Param|null, // Default: null
 *             auth_code_parameter_name?: scalar|Param|null, // Default: "_auth_code"
 *             trusted_parameter_name?: scalar|Param|null, // Default: "_trusted"
 *             remember_me_sets_trusted?: scalar|Param|null, // Default: false
 *             multi_factor?: bool|Param, // Default: false
 *             prepare_on_login?: bool|Param, // Default: false
 *             prepare_on_access_denied?: bool|Param, // Default: false
 *             enable_csrf?: scalar|Param|null, // Default: false
 *             csrf_parameter?: scalar|Param|null, // Default: "_csrf_token"
 *             csrf_token_id?: scalar|Param|null, // Default: "two_factor"
 *             csrf_header?: scalar|Param|null, // Default: null
 *             csrf_token_manager?: scalar|Param|null, // Default: "scheb_two_factor.csrf_token_manager"
 *             provider?: scalar|Param|null, // Default: null
 *         },
 *     }>,
 *     access_control?: list<array{ // Default: []
 *         request_matcher?: scalar|Param|null, // Default: null
 *         requires_channel?: scalar|Param|null, // Default: null
 *         path?: scalar|Param|null, // Use the urldecoded format. // Default: null
 *         host?: scalar|Param|null, // Default: null
 *         port?: int|Param, // Default: null
 *         ips?: string|list<scalar|Param|null>,
 *         attributes?: array<string, scalar|Param|null>,
 *         route?: scalar|Param|null, // Default: null
 *         methods?: string|list<scalar|Param|null>,
 *         allow_if?: scalar|Param|null, // Default: null
 *         roles?: string|list<scalar|Param|null>,
 *     }>,
 *     role_hierarchy?: array<string, string|list<scalar|Param|null>>,
 * }
 * @psalm-type SuluPreviewConfig = array{
 *     defaults?: array<mixed>,
 *     mode?: scalar|Param|null, // Default: "auto"
 *     delay?: scalar|Param|null, // Used for the delayed send of changes // Default: 500
 *     cache_adapter?: scalar|Param|null, // Define the symfony framework cache adapter for preview // Default: "cache.app"
 *     cache?: array{ // Deprecated: The "cache" option is deprecated. Use "cache_adapter" instead.
 *         type?: scalar|Param|null, // Default: null
 *         namespace?: scalar|Param|null, // Default: null
 *         apc?: array<mixed>,
 *         apcu?: array<mixed>,
 *         array?: array<mixed>,
 *         file_system?: array{
 *             directory?: scalar|Param|null, // Default: "%sulu.cache_dir%/preview"
 *             extension?: scalar|Param|null, // Default: null
 *             umask?: int|Param, // Default: 2
 *         },
 *         redis?: array{
 *             connection_id?: scalar|Param|null, // Default: null
 *             host?: scalar|Param|null, // Default: "127.0.0.1"
 *             port?: scalar|Param|null, // Default: "6379"
 *             password?: scalar|Param|null, // Default: null
 *             timeout?: scalar|Param|null, // Default: null
 *             database?: scalar|Param|null, // Default: null
 *         },
 *     },
 *     objects?: array{
 *         preview_link?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Bundle\\PreviewBundle\\Domain\\Model\\PreviewLink"
 *         },
 *     },
 * }
 * @psalm-type FosJsRoutingConfig = array{
 *     serializer?: scalar|Param|null,
 *     routes_to_expose?: list<scalar|Param|null>,
 *     router?: scalar|Param|null, // Default: "router"
 *     request_context_base_url?: scalar|Param|null, // Default: null
 *     cache_control?: array{
 *         public?: bool|Param, // Default: false
 *         expires?: scalar|Param|null, // Default: null
 *         maxage?: scalar|Param|null, // Default: null
 *         smaxage?: scalar|Param|null, // Default: null
 *         vary?: list<scalar|Param|null>,
 *     },
 * }
 * @psalm-type CmfRoutingConfig = array{
 *     chain?: array{
 *         routers_by_id?: array<string, scalar|Param|null>,
 *         replace_symfony_router?: bool|Param, // Default: true
 *     },
 *     dynamic?: bool|array{
 *         enabled?: bool|Param, // Default: false
 *         route_collection_limit?: scalar|Param|null, // Default: 0
 *         generic_controller?: scalar|Param|null, // Default: null
 *         default_controller?: scalar|Param|null, // Default: null
 *         controllers_by_type?: array<string, scalar|Param|null>,
 *         controllers_by_class?: array<string, scalar|Param|null>,
 *         templates_by_class?: array<string, scalar|Param|null>,
 *         persistence?: array{
 *             phpcr?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *                 manager_name?: scalar|Param|null, // Default: null
 *                 route_basepaths?: string|list<scalar|Param|null>,
 *                 enable_initializer?: bool|Param, // Default: true
 *             },
 *             orm?: bool|array{
 *                 enabled?: bool|Param, // Default: false
 *                 manager_name?: scalar|Param|null, // Default: null
 *                 route_class?: scalar|Param|null, // Default: "Symfony\\Cmf\\Bundle\\RoutingBundle\\Doctrine\\Orm\\Route"
 *             },
 *         },
 *         uri_filter_regexp?: scalar|Param|null, // Default: ""
 *         route_provider_service_id?: scalar|Param|null,
 *         route_filters_by_id?: array<string, scalar|Param|null>,
 *         content_repository_service_id?: scalar|Param|null,
 *         locales?: list<scalar|Param|null>,
 *         limit_candidates?: int|Param, // Default: 20
 *         match_implicit_locale?: bool|Param, // Default: true
 *         redirectable_url_matcher?: bool|Param, // Default: false
 *         auto_locale_pattern?: bool|Param, // Default: false
 *         url_generator?: scalar|Param|null, // URL generator service ID // Default: "cmf_routing.generator"
 *     },
 * }
 * @psalm-type SchebTwoFactorConfig = array{
 *     persister?: scalar|Param|null, // Default: "scheb_two_factor.persister.doctrine"
 *     model_manager_name?: scalar|Param|null, // Default: null
 *     security_tokens?: list<scalar|Param|null>,
 *     ip_whitelist?: list<scalar|Param|null>,
 *     ip_whitelist_provider?: scalar|Param|null, // Default: "scheb_two_factor.default_ip_whitelist_provider"
 *     two_factor_token_factory?: scalar|Param|null, // Default: "scheb_two_factor.default_token_factory"
 *     two_factor_provider_decider?: scalar|Param|null, // Default: "scheb_two_factor.default_provider_decider"
 *     two_factor_condition?: scalar|Param|null, // Default: null
 *     code_reuse_cache?: scalar|Param|null, // Default: null
 *     code_reuse_cache_duration?: int|Param, // Default: 60
 *     code_reuse_default_handler?: scalar|Param|null, // Default: null
 *     trusted_device?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         manager?: scalar|Param|null, // Default: "scheb_two_factor.default_trusted_device_manager"
 *         lifetime?: int|Param, // Default: 5184000
 *         extend_lifetime?: bool|Param, // Default: false
 *         key?: scalar|Param|null, // Default: null
 *         cookie_name?: scalar|Param|null, // Default: "trusted_device"
 *         cookie_secure?: true|false|"auto"|Param, // Default: "auto"
 *         cookie_domain?: scalar|Param|null, // Default: null
 *         cookie_path?: scalar|Param|null, // Default: "/"
 *         cookie_same_site?: scalar|Param|null, // Default: "lax"
 *     },
 *     backup_codes?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         manager?: scalar|Param|null, // Default: "scheb_two_factor.default_backup_code_manager"
 *     },
 *     email?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         mailer?: scalar|Param|null, // Default: null
 *         code_generator?: scalar|Param|null, // Default: "scheb_two_factor.security.email.default_code_generator"
 *         form_renderer?: scalar|Param|null, // Default: null
 *         sender_email?: scalar|Param|null, // Default: null
 *         sender_name?: scalar|Param|null, // Default: null
 *         template?: scalar|Param|null, // Default: "@SchebTwoFactor/Authentication/form.html.twig"
 *         digits?: int|Param, // Default: 4
 *     },
 *     google?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         form_renderer?: scalar|Param|null, // Default: null
 *         issuer?: scalar|Param|null, // Default: null
 *         server_name?: scalar|Param|null, // Default: null
 *         template?: scalar|Param|null, // Default: "@SchebTwoFactor/Authentication/form.html.twig"
 *         digits?: int|Param, // Default: 6
 *         leeway?: int|Param, // Default: 0
 *     },
 *     totp?: bool|array{
 *         enabled?: scalar|Param|null, // Default: false
 *         form_renderer?: scalar|Param|null, // Default: null
 *         issuer?: scalar|Param|null, // Default: null
 *         server_name?: scalar|Param|null, // Default: null
 *         leeway?: int|Param, // Default: 0
 *         parameters?: list<scalar|Param|null>,
 *         template?: scalar|Param|null, // Default: "@SchebTwoFactor/Authentication/form.html.twig"
 *     },
 * }
 * @psalm-type CmsigSealConfig = array{
 *     index_name_prefix?: scalar|Param|null, // Default: ""
 *     schemas?: array<string, array{ // Default: []
 *         dir?: scalar|Param|null,
 *         engine?: scalar|Param|null, // Default: null
 *     }>,
 *     engines?: array<string, array{ // Default: []
 *         adapter?: scalar|Param|null,
 *     }>,
 * }
 * @psalm-type SuluContentConfig = array{
 *     content_resolver?: array{
 *         max_depth?: scalar|Param|null, // Default: 5
 *     },
 * }
 * @psalm-type SuluArticleConfig = array{
 *     default_main_webspace?: string|array<string, scalar|Param|null>,
 *     default_additional_webspaces?: list<array<string, scalar|Param|null>>,
 *     objects?: array{
 *         article?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Article\\Domain\\Model\\Article"
 *         },
 *         article_content?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Article\\Domain\\Model\\ArticleDimensionContent"
 *         },
 *     },
 * }
 * @psalm-type SuluSnippetConfig = array{
 *     objects?: array{
 *         snippet?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Snippet\\Domain\\Model\\Snippet"
 *         },
 *         snippet_content?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Snippet\\Domain\\Model\\SnippetDimensionContent"
 *         },
 *         snippet_area?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Snippet\\Domain\\Model\\SnippetArea"
 *         },
 *     },
 * }
 * @psalm-type SuluSearchConfig = array{
 *     admin?: array{
 *         resources?: list<array{ // Default: []
 *             name?: scalar|Param|null, // Default: null
 *             icon?: scalar|Param|null, // Default: null
 *             route?: array{
 *                 name?: scalar|Param|null,
 *                 resultToRouteName?: array<string, scalar|Param|null>,
 *                 resultToRoute?: array<string, scalar|Param|null>,
 *             },
 *             securityContext?: scalar|Param|null, // Default: null
 *             contexts?: list<scalar|Param|null>,
 *         }>,
 *     },
 * }
 * @psalm-type SuluPageConfig = array{
 *     objects?: array{
 *         page?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Page\\Domain\\Model\\Page"
 *         },
 *         page_content?: array{
 *             model?: scalar|Param|null, // Default: "Sulu\\Page\\Domain\\Model\\PageDimensionContent"
 *         },
 *     },
 * }
 * @psalm-type ConfigType = array{
 *     imports?: ImportsConfig,
 *     parameters?: ParametersConfig,
 *     services?: ServicesConfig,
 *     framework?: FrameworkConfig,
 *     twig?: TwigConfig,
 *     monolog?: MonologConfig,
 *     flysystem?: FlysystemConfig,
 *     sulu_core?: SuluCoreConfig,
 *     doctrine?: DoctrineConfig,
 *     stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *     fos_rest?: FosRestConfig,
 *     jms_serializer?: JmsSerializerConfig,
 *     fos_http_cache?: FosHttpCacheConfig,
 *     sulu_admin?: SuluAdminConfig,
 *     sulu_contact?: SuluContactConfig,
 *     sulu_media?: SuluMediaConfig,
 *     sulu_security?: SuluSecurityConfig,
 *     sulu_category?: SuluCategoryConfig,
 *     sulu_tag?: SuluTagConfig,
 *     sulu_website?: SuluWebsiteConfig,
 *     sulu_location?: SuluLocationConfig,
 *     sulu_http_cache?: SuluHttpCacheConfig,
 *     sulu_activity?: SuluActivityConfig,
 *     sulu_trash?: SuluTrashConfig,
 *     sulu_reference?: SuluReferenceConfig,
 *     sulu_custom_url?: SuluCustomUrlConfig,
 *     sulu_markup?: SuluMarkupConfig,
 *     sulu_audience_targeting?: SuluAudienceTargetingConfig,
 *     massive_build?: MassiveBuildConfig,
 *     security?: SecurityConfig,
 *     sulu_preview?: SuluPreviewConfig,
 *     fos_js_routing?: FosJsRoutingConfig,
 *     cmf_routing?: CmfRoutingConfig,
 *     scheb_two_factor?: SchebTwoFactorConfig,
 *     cmsig_seal?: CmsigSealConfig,
 *     sulu_content?: SuluContentConfig,
 *     sulu_article?: SuluArticleConfig,
 *     sulu_snippet?: SuluSnippetConfig,
 *     sulu_search?: SuluSearchConfig,
 *     sulu_page?: SuluPageConfig,
 *     "when@dev"?: array{
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         framework?: FrameworkConfig,
 *         twig?: TwigConfig,
 *         monolog?: MonologConfig,
 *         flysystem?: FlysystemConfig,
 *         sulu_core?: SuluCoreConfig,
 *         doctrine?: DoctrineConfig,
 *         stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *         fos_rest?: FosRestConfig,
 *         jms_serializer?: JmsSerializerConfig,
 *         fos_http_cache?: FosHttpCacheConfig,
 *         sulu_admin?: SuluAdminConfig,
 *         sulu_contact?: SuluContactConfig,
 *         sulu_media?: SuluMediaConfig,
 *         sulu_security?: SuluSecurityConfig,
 *         sulu_category?: SuluCategoryConfig,
 *         sulu_tag?: SuluTagConfig,
 *         sulu_website?: SuluWebsiteConfig,
 *         sulu_location?: SuluLocationConfig,
 *         sulu_http_cache?: SuluHttpCacheConfig,
 *         sulu_activity?: SuluActivityConfig,
 *         sulu_trash?: SuluTrashConfig,
 *         sulu_reference?: SuluReferenceConfig,
 *         sulu_custom_url?: SuluCustomUrlConfig,
 *         sulu_markup?: SuluMarkupConfig,
 *         sulu_audience_targeting?: SuluAudienceTargetingConfig,
 *         massive_build?: MassiveBuildConfig,
 *         web_profiler?: WebProfilerConfig,
 *         sulu_test?: SuluTestConfig,
 *         debug?: DebugConfig,
 *         security?: SecurityConfig,
 *         sulu_preview?: SuluPreviewConfig,
 *         fos_js_routing?: FosJsRoutingConfig,
 *         cmf_routing?: CmfRoutingConfig,
 *         scheb_two_factor?: SchebTwoFactorConfig,
 *         cmsig_seal?: CmsigSealConfig,
 *         sulu_content?: SuluContentConfig,
 *         sulu_article?: SuluArticleConfig,
 *         sulu_snippet?: SuluSnippetConfig,
 *         sulu_search?: SuluSearchConfig,
 *         sulu_page?: SuluPageConfig,
 *     },
 *     "when@prod"?: array{
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         framework?: FrameworkConfig,
 *         twig?: TwigConfig,
 *         monolog?: MonologConfig,
 *         flysystem?: FlysystemConfig,
 *         sulu_core?: SuluCoreConfig,
 *         doctrine?: DoctrineConfig,
 *         stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *         fos_rest?: FosRestConfig,
 *         jms_serializer?: JmsSerializerConfig,
 *         fos_http_cache?: FosHttpCacheConfig,
 *         sulu_admin?: SuluAdminConfig,
 *         sulu_contact?: SuluContactConfig,
 *         sulu_media?: SuluMediaConfig,
 *         sulu_security?: SuluSecurityConfig,
 *         sulu_category?: SuluCategoryConfig,
 *         sulu_tag?: SuluTagConfig,
 *         sulu_website?: SuluWebsiteConfig,
 *         sulu_location?: SuluLocationConfig,
 *         sulu_http_cache?: SuluHttpCacheConfig,
 *         sulu_activity?: SuluActivityConfig,
 *         sulu_trash?: SuluTrashConfig,
 *         sulu_reference?: SuluReferenceConfig,
 *         sulu_custom_url?: SuluCustomUrlConfig,
 *         sulu_markup?: SuluMarkupConfig,
 *         sulu_audience_targeting?: SuluAudienceTargetingConfig,
 *         massive_build?: MassiveBuildConfig,
 *         security?: SecurityConfig,
 *         sulu_preview?: SuluPreviewConfig,
 *         fos_js_routing?: FosJsRoutingConfig,
 *         cmf_routing?: CmfRoutingConfig,
 *         scheb_two_factor?: SchebTwoFactorConfig,
 *         cmsig_seal?: CmsigSealConfig,
 *         sulu_content?: SuluContentConfig,
 *         sulu_article?: SuluArticleConfig,
 *         sulu_snippet?: SuluSnippetConfig,
 *         sulu_search?: SuluSearchConfig,
 *         sulu_page?: SuluPageConfig,
 *     },
 *     "when@stage"?: array{
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         framework?: FrameworkConfig,
 *         twig?: TwigConfig,
 *         monolog?: MonologConfig,
 *         flysystem?: FlysystemConfig,
 *         sulu_core?: SuluCoreConfig,
 *         doctrine?: DoctrineConfig,
 *         stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *         fos_rest?: FosRestConfig,
 *         jms_serializer?: JmsSerializerConfig,
 *         fos_http_cache?: FosHttpCacheConfig,
 *         sulu_admin?: SuluAdminConfig,
 *         sulu_contact?: SuluContactConfig,
 *         sulu_media?: SuluMediaConfig,
 *         sulu_security?: SuluSecurityConfig,
 *         sulu_category?: SuluCategoryConfig,
 *         sulu_tag?: SuluTagConfig,
 *         sulu_website?: SuluWebsiteConfig,
 *         sulu_location?: SuluLocationConfig,
 *         sulu_http_cache?: SuluHttpCacheConfig,
 *         sulu_activity?: SuluActivityConfig,
 *         sulu_trash?: SuluTrashConfig,
 *         sulu_reference?: SuluReferenceConfig,
 *         sulu_custom_url?: SuluCustomUrlConfig,
 *         sulu_markup?: SuluMarkupConfig,
 *         sulu_audience_targeting?: SuluAudienceTargetingConfig,
 *         massive_build?: MassiveBuildConfig,
 *         security?: SecurityConfig,
 *         sulu_preview?: SuluPreviewConfig,
 *         fos_js_routing?: FosJsRoutingConfig,
 *         cmf_routing?: CmfRoutingConfig,
 *         scheb_two_factor?: SchebTwoFactorConfig,
 *         cmsig_seal?: CmsigSealConfig,
 *         sulu_content?: SuluContentConfig,
 *         sulu_article?: SuluArticleConfig,
 *         sulu_snippet?: SuluSnippetConfig,
 *         sulu_search?: SuluSearchConfig,
 *         sulu_page?: SuluPageConfig,
 *     },
 *     "when@test"?: array{
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         framework?: FrameworkConfig,
 *         twig?: TwigConfig,
 *         monolog?: MonologConfig,
 *         flysystem?: FlysystemConfig,
 *         sulu_core?: SuluCoreConfig,
 *         doctrine?: DoctrineConfig,
 *         stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *         fos_rest?: FosRestConfig,
 *         jms_serializer?: JmsSerializerConfig,
 *         fos_http_cache?: FosHttpCacheConfig,
 *         sulu_admin?: SuluAdminConfig,
 *         sulu_contact?: SuluContactConfig,
 *         sulu_media?: SuluMediaConfig,
 *         sulu_security?: SuluSecurityConfig,
 *         sulu_category?: SuluCategoryConfig,
 *         sulu_tag?: SuluTagConfig,
 *         sulu_website?: SuluWebsiteConfig,
 *         sulu_location?: SuluLocationConfig,
 *         sulu_http_cache?: SuluHttpCacheConfig,
 *         sulu_activity?: SuluActivityConfig,
 *         sulu_trash?: SuluTrashConfig,
 *         sulu_reference?: SuluReferenceConfig,
 *         sulu_custom_url?: SuluCustomUrlConfig,
 *         sulu_markup?: SuluMarkupConfig,
 *         sulu_audience_targeting?: SuluAudienceTargetingConfig,
 *         massive_build?: MassiveBuildConfig,
 *         web_profiler?: WebProfilerConfig,
 *         sulu_test?: SuluTestConfig,
 *         debug?: DebugConfig,
 *         security?: SecurityConfig,
 *         sulu_preview?: SuluPreviewConfig,
 *         fos_js_routing?: FosJsRoutingConfig,
 *         cmf_routing?: CmfRoutingConfig,
 *         scheb_two_factor?: SchebTwoFactorConfig,
 *         cmsig_seal?: CmsigSealConfig,
 *         sulu_content?: SuluContentConfig,
 *         sulu_article?: SuluArticleConfig,
 *         sulu_snippet?: SuluSnippetConfig,
 *         sulu_search?: SuluSearchConfig,
 *         sulu_page?: SuluPageConfig,
 *     },
 *     "when@website"?: array{
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         framework?: FrameworkConfig,
 *         twig?: TwigConfig,
 *         monolog?: MonologConfig,
 *         flysystem?: FlysystemConfig,
 *         sulu_core?: SuluCoreConfig,
 *         doctrine?: DoctrineConfig,
 *         stof_doctrine_extensions?: StofDoctrineExtensionsConfig,
 *         fos_rest?: FosRestConfig,
 *         jms_serializer?: JmsSerializerConfig,
 *         fos_http_cache?: FosHttpCacheConfig,
 *         sulu_admin?: SuluAdminConfig,
 *         sulu_contact?: SuluContactConfig,
 *         sulu_media?: SuluMediaConfig,
 *         sulu_security?: SuluSecurityConfig,
 *         sulu_category?: SuluCategoryConfig,
 *         sulu_tag?: SuluTagConfig,
 *         sulu_website?: SuluWebsiteConfig,
 *         sulu_location?: SuluLocationConfig,
 *         sulu_http_cache?: SuluHttpCacheConfig,
 *         sulu_activity?: SuluActivityConfig,
 *         sulu_trash?: SuluTrashConfig,
 *         sulu_reference?: SuluReferenceConfig,
 *         sulu_custom_url?: SuluCustomUrlConfig,
 *         sulu_markup?: SuluMarkupConfig,
 *         sulu_audience_targeting?: SuluAudienceTargetingConfig,
 *         massive_build?: MassiveBuildConfig,
 *         security?: SecurityConfig,
 *         sulu_preview?: SuluPreviewConfig,
 *         fos_js_routing?: FosJsRoutingConfig,
 *         cmf_routing?: CmfRoutingConfig,
 *         scheb_two_factor?: SchebTwoFactorConfig,
 *         cmsig_seal?: CmsigSealConfig,
 *         sulu_content?: SuluContentConfig,
 *         sulu_article?: SuluArticleConfig,
 *         sulu_snippet?: SuluSnippetConfig,
 *         sulu_search?: SuluSearchConfig,
 *         sulu_page?: SuluPageConfig,
 *     },
 *     ...<string, ExtensionType|array{ // extra keys must follow the when@%env% pattern or match an extension alias
 *         imports?: ImportsConfig,
 *         parameters?: ParametersConfig,
 *         services?: ServicesConfig,
 *         ...<string, ExtensionType>,
 *     }>
 * }
 */
final class App
{
    /**
     * @param ConfigType $config
     *
     * @psalm-return ConfigType
     */
    public static function config(array $config): array
    {
        /** @var ConfigType $config */
        $config = AppReference::config($config);

        return $config;
    }
}

namespace Symfony\Component\Routing\Loader\Configurator;

/**
 * This class provides array-shapes for configuring the routes of an application.
 *
 * Example:
 *
 *     ```php
 *     // config/routes.php
 *     namespace Symfony\Component\Routing\Loader\Configurator;
 *
 *     return Routes::config([
 *         'controllers' => [
 *             'resource' => 'routing.controllers',
 *         ],
 *     ]);
 *     ```
 *
 * @psalm-type RouteConfig = array{
 *     path: string|array<string,string>,
 *     controller?: string,
 *     methods?: string|list<string>,
 *     requirements?: array<string,string>,
 *     defaults?: array<string,mixed>,
 *     options?: array<string,mixed>,
 *     host?: string|array<string,string>,
 *     schemes?: string|list<string>,
 *     condition?: string,
 *     locale?: string,
 *     format?: string,
 *     utf8?: bool,
 *     stateless?: bool,
 * }
 * @psalm-type ImportConfig = array{
 *     resource: string,
 *     type?: string,
 *     exclude?: string|list<string>,
 *     prefix?: string|array<string,string>,
 *     name_prefix?: string,
 *     trailing_slash_on_root?: bool,
 *     controller?: string,
 *     methods?: string|list<string>,
 *     requirements?: array<string,string>,
 *     defaults?: array<string,mixed>,
 *     options?: array<string,mixed>,
 *     host?: string|array<string,string>,
 *     schemes?: string|list<string>,
 *     condition?: string,
 *     locale?: string,
 *     format?: string,
 *     utf8?: bool,
 *     stateless?: bool,
 * }
 * @psalm-type AliasConfig = array{
 *     alias: string,
 *     deprecated?: array{package:string, version:string, message?:string},
 * }
 * @psalm-type RoutesConfig = array{
 *     "when@dev"?: array<string, RouteConfig|ImportConfig|AliasConfig>,
 *     "when@prod"?: array<string, RouteConfig|ImportConfig|AliasConfig>,
 *     "when@stage"?: array<string, RouteConfig|ImportConfig|AliasConfig>,
 *     "when@test"?: array<string, RouteConfig|ImportConfig|AliasConfig>,
 *     "when@website"?: array<string, RouteConfig|ImportConfig|AliasConfig>,
 *     ...<string, RouteConfig|ImportConfig|AliasConfig>
 * }
 */
final class Routes
{
    /**
     * @param RoutesConfig $config
     *
     * @psalm-return RoutesConfig
     */
    public static function config(array $config): array
    {
        return $config;
    }
}
