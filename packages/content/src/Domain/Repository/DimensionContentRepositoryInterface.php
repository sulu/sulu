<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Domain\Repository;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal
 */
interface DimensionContentRepositoryInterface
{
    /**
     * Queries the database instead of the entity's dimension contents collection, which is often
     * only hydrated for the locale of the current request.
     *
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param array<string, mixed> $dimensionAttributes
     *
     * @return T|null
     */
    public function findOneBy(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): ?DimensionContentInterface;
}
