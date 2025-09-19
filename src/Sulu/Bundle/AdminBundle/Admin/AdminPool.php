<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

/**
 * The AdminPool is a container for all the registered admin-objects.
 */
class AdminPool
{
    /**
     * @var Admin[]
     */
    private $pool = [];

    /**
     * @param iterable<Admin> $admins
     */
    public function __construct(iterable $admins = [])
    {
        $this->pool = [...$admins];
    }

    /**
     * @return Admin[]
     */
    public function getAdmins(): array
    {
        return $this->pool;
    }

    /**
     * Returns all the security contexts, which are available in the concrete bundle.
     *
     * @return array<string, array<string, array<string, string[]>>>
     */
    public function getSecurityContexts()
    {
        $contexts = [];
        $this->iterateAdmins(function(Admin $admin) use (&$contexts) {
            $contexts = \array_merge_recursive($contexts, $admin->getSecurityContexts());
        });

        return $contexts;
    }

    /**
     * Returns all the security contexts, which are available in the concrete bundle.
     *
     * @return array<string, array<string, array<string, string[]>>>
     */
    public function getSecurityContextsWithPlaceholder()
    {
        $contexts = [];
        $this->iterateAdmins(function(Admin $admin) use (&$contexts) {
            $contexts = \array_merge_recursive($contexts, $admin->getSecurityContextsWithPlaceholder());
        });

        return $contexts;
    }

    /**
     * Helper function to iterate over all available Admin objects.
     */
    private function iterateAdmins(callable $callback): void
    {
        foreach ($this->pool as $admin) {
            $callback($admin);
        }
    }
}
