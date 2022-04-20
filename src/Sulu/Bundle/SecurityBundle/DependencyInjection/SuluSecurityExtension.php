<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\SecurityBundle\Exception\RoleKeyAlreadyExistsException;
use Sulu\Bundle\SecurityBundle\Exception\RoleNameAlreadyExistsException;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\RoleSettingRepositoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\DescendantProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class SuluSecurityExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_security.system', $config['system']);
        $container->setParameter('sulu_security.security_types.fixture', $config['security_types']['fixture']);

        $passwordPolicyEnabled = $config['password_policy']['enabled'];
        $container->setParameter('sulu_security.password_policy.pattern', $passwordPolicyEnabled ? $config['password_policy']['pattern'] : null);
        $container->setParameter('sulu_security.password_policy.information_translation_key', $passwordPolicyEnabled ? $config['password_policy']['information_translation_key'] : null);

        foreach ($config['reset_password']['mail'] as $option => $value) {
            $container->setParameter('sulu_security.reset_password.mail.' . $option, $value);
        }

        $container->registerForAutoconfiguration(DescendantProviderInterface::class)
            ->addTag('sulu_security.access_control_descendant_provider');

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('command.xml');

        if (\interface_exists(LogoutSuccessHandlerInterface::class)) {
            $loader->load('logout_success_handler.xml');
        }

        if ($config['checker']['enabled']) {
            $loader->load('checker.xml');
        }

        $this->configurePersistence($config['objects'], $container);
        $container->addAliases(
            [
                UserRepositoryInterface::class => 'sulu.repository.user',
                RoleRepositoryInterface::class => 'sulu.repository.role',
                RoleSettingRepositoryInterface::class => 'sulu.repository.role_setting',
                AccessControlRepositoryInterface::class => 'sulu.repository.access_control',
            ]
        );
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            RoleNameAlreadyExistsException::class => 409,
                            RoleKeyAlreadyExistsException::class => 409,
                            UsernameNotUniqueException::class => 409,
                            EmailNotUniqueException::class => 409,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('framework')
            && SuluKernel::CONTEXT_ADMIN === $container->getParameter('sulu.context')
        ) {
            $container->prependExtensionConfig(
                'framework',
                [
                    'csrf_protection' => true,
                    'session' => [
                        'cookie_path' => '/admin',
                    ],
                    'fragments' => [
                        'path' => '/admin/_fragments',
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'permissions' => [
                            'routes' => [
                                'detail' => 'sulu_security.get_permissions',
                            ],
                        ],
                        'roles' => [
                            'routes' => [
                                'list' => 'sulu_security.get_roles',
                                'detail' => 'sulu_security.get_role',
                            ],
                        ],
                        'users' => [
                            'routes' => [
                                'list' => 'sulu_security.get_users',
                                'detail' => 'sulu_security.get_user',
                            ],
                        ],
                        'profile' => [
                            'routes' => [
                                'detail' => 'sulu_security.get_profile',
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
