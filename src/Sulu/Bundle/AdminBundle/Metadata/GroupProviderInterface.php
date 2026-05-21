<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;

interface GroupProviderInterface
{
    public const DEFAULT_GROUP = 'default';

    /**
     * @return array<string, FormGroup>
     */
    public function getGroups(string $key): array;
}
