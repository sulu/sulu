<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

/**
 * Defines the interface for a role setting.
 */
interface RoleSettingInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set key.
     *
     * @param string $key
     *
     * @return RoleSettingInterface
     */
    public function setKey($key);

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey();

    /**
     * Set value.
     *
     * @param array $value
     *
     * @return RoleSettingInterface
     */
    public function setValue($value);

    /**
     * Get value.
     *
     * @return array
     */
    public function getValue();

    /**
     * Set role.
     *
     * @param RoleInterface $role
     *
     * @return RoleSettingInterface
     */
    public function setRole(RoleInterface $role = null);

    /**
     * Get role.
     *
     * @return RoleSettingInterface
     */
    public function getRole();
}
