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
    public function __construct(
        private AdminPool $adminPool,
        private string $suluSecuritySystem,
    ) {
    }

    public function getValues(): array
    {
        $values = [];
        foreach (\array_keys($this->adminPool->getSecurityContexts()) as $context) {
            $values[] = [
                'name' => $context,
                'title' => \ucfirst($context),
            ];
        }

        return $values;
    }

    public function getDefaultValue(): string
    {
        return $this->suluSecuritySystem;
    }
}
