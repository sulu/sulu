<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecurityConfig implements JsConfigInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        $name,
        AccessControlManagerInterface $accessControlManager,
        AdminPool $adminPool,
        TokenStorageInterface $tokenStorage
    ) {
        $this->name = $name;
        $this->accessControlManager = $accessControlManager;
        $this->adminPool = $adminPool;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        $parameters = [];
        foreach ($this->adminPool->getSecurityContexts() as $system => $sections) {
            foreach ($sections as $section => $contexts) {
                foreach ($contexts as $context => $permissionTypes) {
                    $parameters[$context] = $this->accessControlManager->getUserPermissions(
                        new SecurityCondition($context),
                        $this->tokenStorage->getToken()->getUser()
                    );
                }
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
