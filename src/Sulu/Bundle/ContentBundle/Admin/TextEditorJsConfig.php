<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Passes the text-editor toolbar into the js-config.
 */
class TextEditorJsConfig implements JsConfigInterface
{
    const SETTING_KEY = 'texteditor-toolbar';

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        if (null === $this->tokenStorage->getToken()) {
            return ['settingKey' => self::SETTING_KEY];
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            return ['settingKey' => self::SETTING_KEY];
        }

        $result = [];
        $exists = false;
        foreach ($user->getRoleObjects() as $role) {
            if (null === ($setting = $role->getSetting(self::SETTING_KEY))) {
                continue;
            }

            $result = array_merge_recursive($result, $setting->getValue());
            $exists = true;
        }

        if (!$exists) {
            return ['settingKey' => self::SETTING_KEY];
        }

        // array_merge_recursive accepts non-unique values they have to be removed
        foreach (array_keys($result) as $section) {
            $result[$section] = array_values(array_unique($result[$section]));
        }

        return ['settingKey' => self::SETTING_KEY, 'userToolbar' => $result];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_content.texteditor_toolbar';
    }
}
