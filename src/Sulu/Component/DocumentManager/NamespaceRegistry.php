<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * Central registry of roles to namespaces.
 */
class NamespaceRegistry
{
    private $roleMap = [];

    /**
     * @param array $roleMap
     */
    public function __construct(array $roleMap)
    {
        $this->roleMap = $roleMap;
    }

    /**
     * Return the namespace alias for the given role, e.g. "localized_content" => "lcont".
     *
     * @param $role
     *
     * @throws DocumentManagerException
     *
     * @return string
     */
    public function getPrefix($role)
    {
        if (!array_key_exists($role, $this->roleMap)) {
            throw new DocumentManagerException(sprintf(
                'Trying to get non-existant namespace alias role "%s", known roles: "%s"',
                $role, implode('", "', array_keys($this->roleMap))
            ));
        }

        return $this->roleMap[$role];
    }
}
