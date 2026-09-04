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

    /**
     * Resolves the identifier of the group a template key belongs to, falling back to
     * self::DEFAULT_GROUP if the template key is null or not part of any group.
     */
    public function resolveGroup(string $key, ?string $templateKey): string;
}
