<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Admin\Helper;

use Sulu\Bundle\AdminBundle\Admin\AdminPool;

class SecuritySystemsSelect
{
    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var string
     */
    private $suluSecuritySystem;

    public function __construct(AdminPool $adminPool, string $suluSecuritySystem)
    {
        $this->adminPool = $adminPool;
        $this->suluSecuritySystem = $suluSecuritySystem;
    }

    public function getValues(): array
    {
        $values = [];
        foreach (array_keys($this->adminPool->getSecurityContexts()) as $context) {
            $values[] = [
                'name' => $context,
                'title' => ucfirst($context),
            ];
        }

        return $values;
    }

    public function getDefaultValue(): string
    {
        return $this->suluSecuritySystem;
    }
}
