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

use Sulu\Bundle\ContentBundle\Admin\TextEditorJsConfig;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleSettingInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TextEditorJsConfigTest extends \PHPUnit_Framework_TestCase
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
     * @var TextEditorJsConfig
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

        $this->jsConfig = new TextEditorJsConfig($this->tokenStorage->reveal());
    }

    private function createRole($texteditorSetting = null)
    {
        $setting = null;
        if (null !== $texteditorSetting) {
            $setting = $this->prophesize(RoleSettingInterface::class);
            $setting->getKey()->willReturn(TextEditorJsConfig::SETTING_KEY);
            $setting->getValue()->willReturn($texteditorSetting);
            $setting = $setting->reveal();
        }

        $role = $this->prophesize(RoleInterface::class);
        $role->getSetting(TextEditorJsConfig::SETTING_KEY)->willReturn($setting);

        return $role;
    }

    public function testGetName()
    {
        $this->assertEquals('sulu_content.texteditor_toolbar', $this->jsConfig->getName());
    }

    public function testGetParametersNoToken()
    {
        $this->assertEquals(['settingKey' => TextEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
    }

    public function testGetParametersWrongUser()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn(new \stdClass());

        $this->assertEquals(['settingKey' => TextEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
    }

    public function testGetParametersNoRole()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->assertEquals(['settingKey' => TextEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
    }

    public function testGetParametersNoSetting()
    {
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());

        $this->roles[] = $this->createRole();

        $this->assertEquals(['settingKey' => TextEditorJsConfig::SETTING_KEY], $this->jsConfig->getParameters());
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
                'settingKey' => TextEditorJsConfig::SETTING_KEY,
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
                'settingKey' => TextEditorJsConfig::SETTING_KEY,
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
