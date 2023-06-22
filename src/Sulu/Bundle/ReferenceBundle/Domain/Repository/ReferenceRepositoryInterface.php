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
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceLocale,
        string $referenceTitle,
        string $referenceProperty,
        array $referenceViewAttributes = []
    ): ReferenceInterface;

    public function add(ReferenceInterface $reference): void;

    public function remove(ReferenceInterface $reference): void;

    /**
     * @param array{
     *     referenceResourceKey?: string,
     *     referenceResourceId?: string,
     *     referenceLocale?: string,
     * } $filters
     */
    public function removeBy(array $filters): void;

    public function flush(): void;
}
