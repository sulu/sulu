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

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\ContactBundle\Entity\PositionRepository;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\Admin\Helper\SecuritySystemsSelect;
use Sulu\Bundle\SecurityBundle\Admin\Helper\SystemLanguageSelect;
use Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin;
use Sulu\Bundle\SecurityBundle\Build\SecurityBuilder;
use Sulu\Bundle\SecurityBundle\Build\UserBuilder;
use Sulu\Bundle\SecurityBundle\Controller\ContextsController;
use Sulu\Bundle\SecurityBundle\Controller\GroupController;
use Sulu\Bundle\SecurityBundle\Controller\PermissionController;
use Sulu\Bundle\SecurityBundle\Controller\ProfileController;
use Sulu\Bundle\SecurityBundle\Controller\ProfileTwoFactorController;
use Sulu\Bundle\SecurityBundle\Controller\ResettingController;
use Sulu\Bundle\SecurityBundle\Controller\RoleController;
use Sulu\Bundle\SecurityBundle\Controller\RoleSettingController;
use Sulu\Bundle\SecurityBundle\Controller\UserController;
use Sulu\Bundle\SecurityBundle\DataFixtures\ORM\LoadSecurityTypes;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\GroupRepository;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Bundle\SecurityBundle\Entity\UserSettingRepository;
use Sulu\Bundle\SecurityBundle\EventListener\AuhenticationFailureListener;
use Sulu\Bundle\SecurityBundle\EventListener\ForceTwoFactorSetupSubscriber;
use Sulu\Bundle\SecurityBundle\EventListener\LogoutEventSubscriber;
use Sulu\Bundle\SecurityBundle\EventListener\PermissionInheritanceSubscriber;
use Sulu\Bundle\SecurityBundle\EventListener\PhpcrSecuritySubscriber;
use Sulu\Bundle\SecurityBundle\EventListener\SystemListener;
use Sulu\Bundle\SecurityBundle\EventListener\UserLocaleListener;
use Sulu\Bundle\SecurityBundle\Metadata\PasswordPolicyFormMetadataVisitor;
use Sulu\Bundle\SecurityBundle\Metadata\TwoFactorFormMetadataVisitor;
use Sulu\Bundle\SecurityBundle\Security\AuthenticationEntryPoint;
use Sulu\Bundle\SecurityBundle\Security\AuthenticationHandler;
use Sulu\Bundle\SecurityBundle\Serializer\Subscriber\SecuritySubscriber;
use Sulu\Bundle\SecurityBundle\System\SystemStore;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\SecurityBundle\Twig\UserTwigExtension;
use Sulu\Bundle\SecurityBundle\TwoFactor\BackupCodeGenerator;
use Sulu\Bundle\SecurityBundle\TwoFactor\TwoFactorForceChecker;
use Sulu\Bundle\SecurityBundle\User\UserProvider;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Bundle\SecurityBundle\Util\TokenGenerator;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManager;
use Sulu\Component\Security\Authorization\AccessControl\DoctrineAccessControlProvider;
use Sulu\Component\Security\Authorization\AccessControl\PhpcrAccessControlProvider;
use Sulu\Component\Security\Authorization\MaskConverter;
use Sulu\Component\Security\Authorization\SecurityContextVoter;
use Sulu\Component\Security\Serializer\Subscriber\SecuredEntitySubscriber;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_security.permissions', ['view' => 64, 'add' => 32, 'edit' => 16, 'delete' => 8, 'archive' => 4, 'live' => 2, 'security' => 1]);
    $parameters->set('permissions', '%sulu_security.permissions%');
    $parameters->set('sulu_security.admin.class', SecurityAdmin::class);
    $parameters->set('sulu_security.authentication_entry_point.class', AuthenticationEntryPoint::class);
    $parameters->set('sulu_security.authentication_handler.class', AuthenticationHandler::class);
    $parameters->set('sulu_security.mask_converter.class', MaskConverter::class);
    $parameters->set('sulu_security.salt_generator.class', SaltGenerator::class);
    $parameters->set('sulu_security.token_generator.class', TokenGenerator::class);
    $parameters->set('sulu_security.security_context_voter.class', SecurityContextVoter::class);
    $parameters->set('sulu_security.access_control_manager.class', AccessControlManager::class);
    $parameters->set('sulu_security.phpcr_access_control_provider.class', PhpcrAccessControlProvider::class);
    $parameters->set('sulu_security.doctrine_access_control_provider.class', DoctrineAccessControlProvider::class);
    $parameters->set('sulu_security.permission_controller.class', PermissionController::class);
    $parameters->set('sulu_security.group_repository.class', GroupRepository::class);
    $parameters->set('sulu_security.user_repository.class', UserRepository::class);
    $parameters->set('sulu_security.user_setting_repository.class', UserSettingRepository::class);
    $parameters->set('sulu_security.user_repository_factory.class', 'Sulu\Component\Security\Authentication\UserRepositoryFactory');
    $parameters->set('sulu_security.build.user.class', UserBuilder::class);
    $parameters->set('sulu_security.entity.role', Role::class);
    $parameters->set('sulu_security.entity.group', Group::class);
    $parameters->set('sulu_security.entity.user_setting', UserSetting::class);
    $parameters->set('sulu_security.profile_controller.class', ProfileController::class);

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
            new Reference('sulu_security.encoder_factory'),
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

    $services->set('sulu_security.admin', '%sulu_security.admin.class%')
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_security.security_checker'),
            new Reference('router'),
            new Reference('translator'),
            new Reference('sulu_admin.admin_pool'),
            '%sulu_admin.resources%',
            '%sulu_security.two_factor_backup_codes_enabled%',
            new Reference('sulu_security.two_factor_force_checker'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_security.two_factor_setup_methods%',
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

    $services->set('sulu_security.authentication_entry_point', '%sulu_security.authentication_entry_point.class%');

    $services->set('sulu_security.authentication_handler', '%sulu_security.authentication_handler.class%')
        ->args([
            new Reference('router'),
            '%sulu_security.two_factor_methods%',
        ]);

    $services->set('sulu_security.mask_converter', '%sulu_security.mask_converter.class%')
        ->public()
        ->args(['%permissions%']);

    $services->set('sulu_security.salt_generator', '%sulu_security.salt_generator.class%')
        ->public();

    $services->alias('%sulu_security.salt_generator.class%', 'sulu_security.salt_generator');

    $services->set('sulu_security.token_generator', '%sulu_security.token_generator.class%')
        ->public();

    $services->set('sulu_security.security_context_voter', '%sulu_security.security_context_voter.class%')
        ->private()
        ->args([
            new Reference('sulu_security.access_control_manager'),
            '%permissions%',
        ])
        ->tag('security.voter');

    $services->set('sulu_security.access_control_manager', '%sulu_security.access_control_manager.class%')
        ->args([
            new Reference('sulu_security.mask_converter'),
            new Reference('event_dispatcher'),
            new Reference('sulu_security.system_store'),
            tagged_iterator('sulu_security.access_control_descendant_provider'),
            new Reference('sulu.repository.role'),
            new Reference('sulu.repository.access_control'),
            new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_security.permissions%',
        ]);

    $services->set('sulu_security.system_store', SystemStore::class)
        ->args([new Reference('sulu.repository.role')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->alias(SystemStoreInterface::class, 'sulu_security.system_store');

    $services->set('sulu_security.phpcr_access_control_provider', '%sulu_security.phpcr_access_control_provider.class%')
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu.repository.role'),
            '%permissions%',
        ])
        ->tag('sulu.access_control');

    $services->set('sulu_security.doctrine_access_control_provider', '%sulu_security.doctrine_access_control_provider.class%')
        ->args([
            new Reference('doctrine.orm.default_entity_manager'),
            new Reference('sulu.repository.role'),
            new Reference('sulu.repository.access_control'),
            new Reference('sulu_security.mask_converter'),
        ])
        ->tag('sulu.access_control');

    $services->set('sulu_security.permission_controller', '%sulu_security.permission_controller.class%')
        ->public()
        ->args([
            new Reference('sulu_security.access_control_manager'),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu.repository.role'),
            new Reference('fos_rest.view_handler'),
            '%sulu_admin.resources%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.profile_controller', '%sulu_security.profile_controller.class%')
        ->public()
        ->args([
            new Reference('security.token_storage'),
            new Reference('doctrine.orm.default_entity_manager'),
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_security.user_setting_repository'),
            new Reference('sulu_security.user_manager'),
            '%sulu.model.user.class%',
            '%sulu.model.contact.class%',
            new Reference('sulu_security.two_factor_force_checker'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.two_factor_backup_code_generator', BackupCodeGenerator::class);

    $services->set('sulu_security.two_factor_force_checker', TwoFactorForceChecker::class)
        ->args([
            '%sulu_security.two_factor_force_pattern%',
            '%sulu_security.two_factor_force_setup%',
            '%sulu_security.two_factor_setup_methods%',
        ]);

    $services->set('sulu_security.force_two_factor_setup_listener', ForceTwoFactorSetupSubscriber::class)
        ->args([
            new Reference('security.token_storage'),
            new Reference('sulu_security.two_factor_force_checker'),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.profile_two_factor_controller', ProfileTwoFactorController::class)
        ->public()
        ->args([
            new Reference('security.token_storage'),
            new Reference('doctrine.orm.default_entity_manager'),
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_security.two_factor_backup_code_generator'),
            new Reference('scheb_two_factor.security.totp_authenticator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('scheb_two_factor.security.google_authenticator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('scheb_two_factor.security.email.code_generator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_security.two_factor_backup_codes_enabled%',
            '%sulu_security.two_factor_force_pattern%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.contexts_controller', ContextsController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_admin.admin_pool'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_security.group_controller', GroupController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu.repository.role'),
            new Reference('doctrine.orm.entity_manager'),
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

    $services->set('sulu_security.group_repository', '%sulu_security.group_repository.class%')
        ->args(['%sulu_security.entity.group%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_security.user_setting_repository', '%sulu_security.user_setting_repository.class%')
        ->public()
        ->args(['%sulu_security.entity.user_setting%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_security.user_repository', '%sulu_security.user_repository.class%')
        ->public()
        ->args(['%sulu.model.user.class%'])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_security.user_provider', UserProvider::class)
        ->args([
            new Reference('sulu_security.user_repository'),
            new Reference('sulu_security.system_store'),
            new Reference('doctrine.orm.entity_manager'),
        ]);

    $services->set('sulu_security.build.user', '%sulu_security.build.user.class%')
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

    $services->set('sulu_security.document.serializer.subscriber.security', SecuritySubscriber::class)
        ->args([
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_security.twig_extension.user.cache_adapter', ArrayAdapter::class);

    $services->set('sulu_security.twig_extension.user.cache', PositionRepository::class)
        ->args([new Reference('sulu_security.twig_extension.user.cache_adapter')])
        ->factory([DoctrineProvider::class, 'wrap']);

    $services->set('sulu_security.twig_extension.user', UserTwigExtension::class)
        ->args([
            new Reference('sulu_security.twig_extension.user.cache'),
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
            null,
            '%sulu.context%',
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_security.fixtures.security_types', LoadSecurityTypes::class)
        ->args(['%sulu_security.security_types.fixture%'])
        ->tag('doctrine.fixture.orm');

    $services->set('sulu_security.login_failure_listener', AuhenticationFailureListener::class)
        ->args([
            new Reference('sulu_security.encoder_factory'),
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

    $services->set('sulu_security.phpcr_security_subscriber', PhpcrSecuritySubscriber::class)
        ->args([
            new Reference('sulu_security.phpcr_access_control_provider'),
            new Reference('sulu_security.doctrine_access_control_provider'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_security.user_manager', UserManager::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_security.encoder_factory', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu.repository.role'),
            new Reference('sulu_security.group_repository'),
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
