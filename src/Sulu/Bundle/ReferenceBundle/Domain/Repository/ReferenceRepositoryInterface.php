<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Domain\Repository;

use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;

interface ReferenceRepositoryInterface
{
    /**
     * @param array<string, string> $referenceViewAttributes
     */
    public function create(
        string $resourceKey,
        string $resourceId,
        string $locale,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceTitle,
        string $referenceProperty,
        array $referenceViewAttributes = [],
        ?string $referenceSecurityContext = null,
        ?string $referenceSecurityObjectType = null,
        ?string $referenceSecurityObjectId = null
    ): ReferenceInterface;

    public function add(ReferenceInterface $reference): void;

    public function remove(ReferenceInterface $reference): void;

    public function removeByReferenceResourceKeyAndId(string $referenceResourceKey, string $referenceResourceId, string $locale): void;

    /**
     * @param mixed[] $criteria
     */
    public function getOneBy(array $criteria): ReferenceInterface;

    /**
     * @param mixed[] $criteria
     */
    public function findOneBy(array $criteria): ?ReferenceInterface;

    public function flush(): void;
}
