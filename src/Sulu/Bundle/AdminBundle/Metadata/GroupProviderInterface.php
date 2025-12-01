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
    /**
     * @param string|null $resourceKey (optional) The resource key to get groups for
     * @param string|null $templateType (optional) The template type to get groups for
     *
     * @return array<string, FormGroup>
     */
    public function getGroups(/* ?string $resourceKey = null, ?string $templateType = null */): array;
}
