<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Admin;

use Sulu\Bundle\ContentBundle\Admin\CKEditorJsConfig;
use Sulu\Bundle\SecurityBundle\Entity\RoleSetting;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CKEditorJsConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var RoleInterface[]
     */
    private $roles;

    /**
     * @var CKEditorJsConfig
     */
    private $jsConfig;

    public function setUp()
    {
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(UserInterface::class);
        $this->roles = [];

        $self = $this;
        $this->user->getRoleObjects()->will(
            function () use ($self) {
                return array_map(
                    function ($role) {
                        return $role->reveal();
                    },
                    $self->roles
                );
            }
        );

        $this->jsConfig = new CKEditorJsConfig($this->tokenStorage->reveal());
    }

    private function createRole($ckeditorSetting = null)
    {
        $setting = null;
        if (null !== $ckeditorSetting) {
            $setting = $this->prophesize(RoleSetting::class);
            $setting->getKey()->willReturn(CKEditorJsConfig::SETTING_KEY);
            $setting->getValue()->willReturn($ckeditorSetting);
            $setting = $setting->reveal();
        }

        $role = $this->prophesize(RoleInterface::class);
        $role->getSetting(CKEditorJsConfig::SETTING_KEY)->willReturn($setting);

        return $role;
    }

    public function testGetName()
    {
        $this->assertEquals('sulu_content.ckeditor_toolbar', $this->jsConfig->getName());
    }

    public function testGetParametersNoToken()
    {
        $this->assertEquals(['settingKey' => CKEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
    }

    public function testGetParametersWrongUser()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn(new \stdClass());

        $this->assertEquals(['settingKey' => CKEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
    }

    public function testGetParametersNoRole()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->assertEquals(['settingKey' => CKEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
    }

    public function testGetParametersNoSetting()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->roles[] = $this->createRole();

        $this->assertEquals(['settingKey' => CKEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
    }

    public function testGetParameters()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->roles[] = $this->createRole(
            [
                'semantics' => ['Format'],
                'basicstyles' => [
                    'Superscript',
                    'Italic',
                    'Bold',
                    'Underline',
                    'Strike',
                ],
            ]
        );

        $this->assertEquals(
            [
                'settingKey' => CKEditorJsConfig::SETTING_KEY,
                'userToolbar' => [
                    'semantics' => ['Format'],
                    'basicstyles' => [
                        'Superscript',
                        'Italic',
                        'Bold',
                        'Underline',
                        'Strike',
                    ],
                ],
            ],
            $this->jsConfig->getParameters()
        );
    }

    public function testGetParametersMultipleRoles()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->roles[] = $this->createRole(
            [
                'semantics' => ['Format'],
                'basicstyles' => [
                    'Superscript',
                    'Italic',
                    'Bold',
                    'Underline',
                    'Strike',
                ],
            ]
        );
        $this->roles[] = $this->createRole(
            [
                'semantics' => ['Format', 'Test'],
                'test' => ['Test'],
            ]
        );

        $this->assertEquals(
            [
                'settingKey' => CKEditorJsConfig::SETTING_KEY,
                'userToolbar' => [
                    'semantics' => ['Format', 'Test'],
                    'basicstyles' => [
                        'Superscript',
                        'Italic',
                        'Bold',
                        'Underline',
                        'Strike',
                    ],
                    'test' => ['Test'],
                ],
            ],
            $this->jsConfig->getParameters()
        );
    }
}
