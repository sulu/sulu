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

use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\Admin\Helper\SecuritySystemsSelect;
use Sulu\Bundle\SecurityBundle\Admin\Helper\SystemLanguageSelect;
use Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin;
use Sulu\Bundle\SecurityBundle\Build\SecurityBuilder;
use Sulu\Bundle\SecurityBundle\Build\UserBuilder;
use Sulu\Bundle\SecurityBundle\Controller\PermissionController;
use Sulu\Bundle\SecurityBundle\Controller\ProfileController;
use Sulu\Bundle\SecurityBundle\Controller\ProfileTwoFactorController;
use Sulu\Bundle\SecurityBundle\Controller\ResettingController;
use Sulu\Bundle\SecurityBundle\Controller\RoleController;
use Sulu\Bundle\SecurityBundle\Controller\RoleSettingController;
use Sulu\Bundle\SecurityBundle\Controller\UserController;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Bundle\SecurityBundle\Entity\UserSettingRepository;
use Sulu\Bundle\SecurityBundle\EventListener\AuthenticationFailureListener;
use Sulu\Bundle\SecurityBundle\EventListener\LogoutEventSubscriber;
use Sulu\Bundle\SecurityBundle\EventListener\PermissionInheritanceSubscriber;
use Sulu\Bundle\SecurityBundle\EventListener\SystemListener;
use Sulu\Bundle\SecurityBundle\EventListener\UserLocaleListener;
use Sulu\Bundle\SecurityBundle\Metadata\PasswordPolicyFormMetadataVisitor;
use Sulu\Bundle\SecurityBundle\Metadata\TwoFactorFormMetadataVisitor;
use Sulu\Bundle\SecurityBundle\Security\AuthenticationEntryPoint;
use Sulu\Bundle\SecurityBundle\Security\AuthenticationHandler;
use Sulu\Bundle\SecurityBundle\System\SystemStore;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\SecurityBundle\Twig\UserTwigExtension;
use Sulu\Bundle\SecurityBundle\TwoFactor\BackupCodeGenerator;
use Sulu\Bundle\SecurityBundle\User\UserProvider;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Bundle\SecurityBundle\Util\TokenGenerator;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManager;
use Sulu\Component\Security\Authorization\AccessControl\DoctrineAccessControlProvider;
use Sulu\Component\Security\Authorization\MaskConverter;
use Sulu\Component\Security\Authorization\SecurityContextVoter;
use Sulu\Component\Security\Serializer\Subscriber\SecuredEntitySubscriber;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_security.permissions', ['review' => 128, 'view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8, 'archive' => 4, 'live' => 2, 'security' => 1]);
    $parameters->set('sulu_security.entity.user_setting', UserSetting::class);

    $services->set('sulu_security.resetting_controller', ResettingController::class)
        ->public()
        ->args([
            new Reference('validator'),
            new Reference('translator'),
            new Reference('sulu_security.token_generator'),
            new Reference('twig'),
            new Reference('security.token_storage'),
            new Reference('event_dispatcher'),
            new Reference('mailer'),
            new Reference('security.password_hasher_factory'),
            new Reference('sulu.repository.user'),
            new Reference('router'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu_security.system%',
            '%sulu_security.reset_password.mail.sender%',
            '%sulu_security.reset_password.mail.subject%',
            '%sulu_security.reset_password.mail.translation_domain%',
            '%sulu_security.reset_password.mail.template%',
            '%sulu_security.reset_password.mail.token_send_limit%',
            '%sulu_admin.email%',
            '%kernel.secret%',
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.admin', SecurityAdmin::class)
        ->lazy()
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_security.security_checker'),
            new Reference('router'),
            new Reference('translator'),
            new Reference('sulu_admin.admin_pool'),
            '%sulu_admin.resources%',
            '%sulu_security.two_factor_backup_codes_enabled%',
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.security_systems_select_helper', SecuritySystemsSelect::class)
        ->public()
        ->args([
            new Reference('sulu_admin.admin_pool'),
            '%sulu_security.system%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.system_language_select_helper', SystemLanguageSelect::class)
        ->public()
        ->args(['%sulu_core.translated_locales%'])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.authentication_entry_point', AuthenticationEntryPoint::class);

    $services->set('sulu_security.authentication_handler', AuthenticationHandler::class)
        ->args([
            new Reference('router'),
            '%sulu_security.two_factor_methods%',
        ]);

    $services->set('sulu_security.mask_converter', MaskConverter::class)
        ->public()
        ->args(['%sulu_security.permissions%']);

    $services->set('sulu_security.salt_generator', SaltGenerator::class)
        ->public();

    $services->alias(SaltGenerator::class, 'sulu_security.salt_generator');

    $services->set('sulu_security.token_generator', TokenGenerator::class)
        ->public();

    $services->set('sulu_security.security_context_voter', SecurityContextVoter::class)
        ->private()
        ->args([
            new Reference('sulu_security.access_control_manager'),
            '%sulu_security.permissions%',
        ])
        ->tag('security.voter');

    $services->set('sulu_security.access_control_manager', AccessControlManager::class)
        ->args([
            new Reference('sulu_security.mask_converter'),
            new Reference('event_dispatcher'),
            new Reference('sulu_security.system_store'),
            tagged_iterator('sulu_security.access_control_descendant_provider'),
            new Reference('sulu.repository.role'),
            new Reference('sulu.repository.access_control'),
            new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_security.permissions%',
            tagged_iterator('sulu.access_control'),
        ]);

    $services->set('sulu_security.system_store', SystemStore::class)
        ->args([new Reference('sulu.repository.role')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->alias(SystemStoreInterface::class, 'sulu_security.system_store');

    $services->set('sulu_security.doctrine_access_control_provider', DoctrineAccessControlProvider::class)
        ->args([
            new Reference('doctrine.orm.default_entity_manager'),
            new Reference('sulu.repository.role'),
            new Reference('sulu.repository.access_control'),
            new Reference('sulu_security.mask_converter'),
        ])
        ->tag('sulu.access_control');

    $services->set('sulu_security.permission_controller', PermissionController::class)
        ->public()
        ->args([
            new Reference('sulu_security.access_control_manager'),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu.repository.role'),
            new Reference('fos_rest.view_handler'),
            '%sulu_admin.resources%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.profile_controller', ProfileController::class)
        ->public()
        ->args([
            new Reference('security.token_storage'),
            new Reference('doctrine.orm.default_entity_manager'),
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_security.user_setting_repository'),
            new Reference('sulu_security.user_manager'),
            '%sulu.model.user.class%',
            '%sulu.model.contact.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.two_factor_backup_code_generator', BackupCodeGenerator::class);

    $services->set('sulu_security.profile_two_factor_controller', ProfileTwoFactorController::class)
        ->public()
        ->args([
            new Reference('security.token_storage'),
            new Reference('doctrine.orm.default_entity_manager'),
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_security.two_factor_backup_code_generator'),
            new Reference('scheb_two_factor.security.totp_authenticator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('scheb_two_factor.security.google_authenticator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_security.two_factor_backup_codes_enabled%',
            '%sulu_security.two_factor_force_pattern%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.role_controller', RoleController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_security.mask_converter'),
            new Reference('sulu.repository.role'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu.model.role.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.role_setting_controller', RoleSettingController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu.repository.role_setting'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.user_controller', UserController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_security.user_manager'),
            new Reference('doctrine.orm.entity_manager'),
            '%sulu.model.user.class%',
            new Reference(FieldDescriptorFactoryInterface::class),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.user_setting_repository', UserSettingRepository::class)
        ->public()
        ->args(['%sulu_security.entity.user_setting%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_security.user_repository', UserRepository::class)
        ->public()
        ->args(['%sulu.model.user.class%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_security.user_provider', UserProvider::class)
        ->args([
            new Reference('sulu_security.user_repository'),
            new Reference('sulu_security.system_store'),
            new Reference('doctrine.orm.entity_manager'),
        ]);

    $services->set('sulu_security.build.user', UserBuilder::class)
        ->tag('massive_build.builder');

    $services->set('sulu_security.build.security', SecurityBuilder::class)
        ->tag('massive_build.builder');

    $services->set('sulu_security.serializer.handler.secured_entity', SecuredEntitySubscriber::class)
        ->args([
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage'),
        ])
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.twig_extension.user.cache_adapter', ArrayAdapter::class)
        // second argument is $storeSerialized before Symfony 8 and $deepClone since, so it is set positionally
        ->args([0, false]);

    $services->set('sulu_security.twig_extension.user', UserTwigExtension::class)
        ->args([
            new Reference('sulu_security.twig_extension.user.cache_adapter'),
            new Reference('sulu.repository.user'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_security.user_locale_listener', UserLocaleListener::class)
        ->args([
            new Reference('security.token_storage'),
            new Reference('translator'),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.system_listener', SystemListener::class)
        ->args([
            new Reference('sulu_security.system_store'),
            '%sulu.context%',
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_security.login_failure_listener', AuthenticationFailureListener::class)
        ->args([
            new Reference('security.password_hasher_factory'),
            new Reference('sulu.repository.user'),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.logout_event_subscriber', LogoutEventSubscriber::class)
        ->args([new Reference('router')])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.access_control_query_enhancer', AccessControlQueryEnhancer::class)
        ->args([
            new Reference('sulu_security.system_store'),
            new Reference('doctrine.orm.entity_manager'),
        ]);

    $services->set('sulu_security.permission_inheritance_subscriber', PermissionInheritanceSubscriber::class)
        ->args([new Reference('sulu_security.access_control_manager')])
        ->tag('doctrine.event_listener', ['event' => 'postPersist']);

    $services->set('sulu_security.user_manager', UserManager::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('security.password_hasher_factory', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu.repository.role'),
            new Reference('sulu_contact.contact_manager'),
            new Reference('sulu_security.salt_generator'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu_security.password_policy_pattern%',
        ]);

    $services->set('sulu_security.password_pattern_form_metadata_visitor', PasswordPolicyFormMetadataVisitor::class)
        ->args([
            new Reference('translator'),
            '%sulu_security.password_policy_pattern%',
            '%sulu_security.password_policy_info_translation_key%',
        ])
        ->tag('sulu_admin.form_metadata_visitor');

    $services->set('sulu_security.two_factor_form_metadata_visitor', TwoFactorFormMetadataVisitor::class)
        ->args([
            '%sulu_security.two_factor_methods%',
            '%sulu_security.two_factor_force_pattern%',
            new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu_admin.form_metadata_visitor');
};
